<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Current course totals for a user.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/gradelib.php');
require_once($GLOBALS['CFG']->dirroot . '/grade/querylib.php');

/**
 * One row per course with the current course total.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_grades {
    /** @var string Course total available. */
    public const STATUS_GRADED = 'graded';
    /** @var string No grade yet. */
    public const STATUS_NOTGRADED = 'notgraded';
    /** @var string Total hidden from the viewer. */
    public const STATUS_HIDDEN = 'hidden';

    /**
     * Get course totals.
     *
     * @param int $userid The student.
     * @param \stdClass[] $courses Course records.
     * @param int|null $viewerid Viewing user; null means the current user.
     * @return array[] Rows: courseid, status, display (string), percentage (float|null).
     */
    public static function get_rows(int $userid, array $courses, ?int $viewerid = null): array {
        $rows = [];
        $user = \core_user::get_user($userid, 'id', MUST_EXIST);
        foreach ($courses as $course) {
            $rows[] = self::get_row($user, $course, $viewerid);
        }
        return $rows;
    }

    /**
     * Build the row for one course.
     *
     * @param \stdClass $user The student.
     * @param \stdClass $course Course record.
     * @param int|null $viewerid Viewing user.
     * @return array
     */
    private static function get_row(\stdClass $user, \stdClass $course, ?int $viewerid): array {
        $userid = (int)$user->id;
        $row = ['courseid' => (int)$course->id, 'status' => self::STATUS_NOTGRADED, 'display' => '', 'percentage' => null];
        if (!access::can_view_grades($course, $user, $viewerid)) {
            $row['status'] = self::STATUS_HIDDEN;
            return $row;
        }
        $grade = grade_get_course_grade($userid, $course->id);
        if (!$grade) {
            return $row;
        }
        $total = self::get_visible_total($userid, $course, $viewerid, $grade);
        $row['status'] = $total['status'];
        if ($total['status'] === self::STATUS_GRADED) {
            $row['display'] = $total['display'];
            if ($total['grademax'] - $total['grademin'] > 0 && $grade->item->scaleid == 0) {
                $row['percentage'] = max(0.0, min(100.0, grade_summary::normalise(
                    $total['grade'],
                    $total['grademin'],
                    $total['grademax']
                )));
            }
        }
        return $row;
    }

    /**
     * The course total this viewer may see.
     *
     * @param int $userid The student.
     * @param \stdClass $course Course record.
     * @param int|null $viewerid Viewing user.
     * @param \stdClass $grade Result of grade_get_course_grade().
     * @return array The status and, when graded, grade, grademin, grademax and display.
     */
    private static function get_visible_total(int $userid, \stdClass $course, ?int $viewerid, \stdClass $grade): array {
        $canviewhidden = has_capability('moodle/grade:viewhidden', \context_course::instance($course->id), $viewerid);
        if ($grade->hidden && !$canviewhidden) {
            return ['status' => self::STATUS_HIDDEN];
        }
        if ($grade->grade === null || $grade->grade === false) {
            return ['status' => self::STATUS_NOTGRADED];
        }
        $shown = [
            'grade' => (float)$grade->grade,
            'grademin' => (float)$grade->item->grademin,
            'grademax' => (float)$grade->item->grademax,
            'display' => $grade->str_grade,
        ];
        if (!$canviewhidden) {
            $shown = self::without_hidden_items($userid, $course, $shown);
        }
        return $shown === null ? ['status' => self::STATUS_HIDDEN] : ['status' => self::STATUS_GRADED] + $shown;
    }

    /**
     * The course total as a viewer who may not see hidden grades is allowed to see it.
     *
     * Follows the core user and overview reports: depending on "Hide totals if they contain hidden items" the total
     * is withheld, recalculated without the hidden items, or left alone.
     *
     * @param int $userid The student.
     * @param \stdClass $course Course record.
     * @param array $shown The real total: grade, grademin, grademax and display.
     * @return array|null The total to show in the same form, or null when it must be hidden.
     */
    private static function without_hidden_items(int $userid, \stdClass $course, array $shown): ?array {
        $mode = self::get_hiding_mode($course->id);
        if ($mode == GRADE_REPORT_SHOW_REAL_TOTAL_IF_CONTAINS_HIDDEN) {
            return $shown;
        }
        $courseitem = \grade_item::fetch_course_item($course->id);
        $affected = self::get_hiding_affected($userid, $course->id);
        $id = $courseitem->id;
        [$regraded, $boundschanged] = self::depends_on_hidden($affected, $id);
        if (!$regraded && !$boundschanged) {
            return $shown;
        }
        if ($regraded && $mode == GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN) {
            return null;
        }
        if ($regraded) {
            $shown['grade'] = $affected['altered'][$id] ?? $affected['unknowngrades'][$id] ?? null;
            if ($shown['grade'] === null) {
                return null;
            }
            $shown['grade'] = (float)$shown['grade'];
        }
        $shown['grademin'] = (float)($affected['alteredgrademin'][$id] ?? $shown['grademin']);
        $shown['grademax'] = (float)($affected['alteredgrademax'][$id] ?? $shown['grademax']);
        $shown['display'] = grade_format_gradevalue($shown['grade'], $courseitem, true);
        return $shown;
    }

    /**
     * Whether an item's grade or bounds depend on hidden items.
     *
     * Tests for the key, not the value: a total whose every item is hidden is recalculated to null.
     *
     * @param array $affected The result of {@see \grade_grade::get_hiding_affected()}.
     * @param int $itemid Grade item id.
     * @return bool[] Whether the grade is recalculated, and whether only its bounds change.
     */
    private static function depends_on_hidden(array $affected, int $itemid): array {
        return [
            array_key_exists($itemid, $affected['altered']) || array_key_exists($itemid, $affected['unknowngrades']),
            array_key_exists($itemid, $affected['alteredgrademin']) || array_key_exists($itemid, $affected['alteredgrademax']),
        ];
    }

    /**
     * The "show totals if they contain hidden items" setting that applies to the course.
     *
     * The core user report and overview report each have one; if they differ the stricter applies, so this report
     * never shows more than either of them. A missing setting counts as "hide".
     *
     * @param int $courseid Course id.
     * @return int One of the GRADE_REPORT_*_TOTAL_IF_CONTAINS_HIDDEN constants.
     */
    private static function get_hiding_mode(int $courseid): int {
        $modes = [];
        foreach (['user', 'overview'] as $report) {
            $default = (int)get_config('moodle', "grade_report_{$report}_showtotalsifcontainhidden");
            $modes[] = (int)grade_get_setting($courseid, "report_{$report}_showtotalsifcontainhidden", $default);
        }
        return min($modes);
    }

    /**
     * Totals that depend on hidden items for this user in this course (core's own calculation).
     *
     * @param int $userid The student.
     * @param int $courseid Course id.
     * @return array The result of {@see \grade_grade::get_hiding_affected()}.
     */
    private static function get_hiding_affected(int $userid, int $courseid): array {
        $items = \grade_item::fetch_all(['courseid' => $courseid]) ?: [];
        $grades = [];
        foreach (array_keys($items) as $itemid) {
            $grade = \grade_grade::fetch(['userid' => $userid, 'itemid' => $itemid]);
            if (!$grade) {
                $grade = new \grade_grade(['userid' => $userid, 'itemid' => $itemid], false);
            }
            $grade->grade_item = $items[$itemid];
            $grades[$itemid] = $grade;
        }
        return \grade_grade::get_hiding_affected($grades, $items);
    }
}
