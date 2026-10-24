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
        foreach ($courses as $course) {
            $rows[] = self::get_row($userid, $course, $viewerid);
        }
        return $rows;
    }

    /**
     * Build the row for one course.
     *
     * @param int $userid The student.
     * @param \stdClass $course Course record.
     * @param int|null $viewerid Viewing user.
     * @return array
     */
    private static function get_row(int $userid, \stdClass $course, ?int $viewerid): array {
        $row = ['courseid' => (int)$course->id, 'status' => self::STATUS_NOTGRADED, 'display' => '', 'percentage' => null];
        if (!access::can_view_grades($course, \core_user::get_user($userid, '*', MUST_EXIST), $viewerid)) {
            $row['status'] = self::STATUS_HIDDEN;
            return $row;
        }
        $grade = grade_get_course_grade($userid, $course->id);
        if (!$grade) {
            return $row;
        }
        if ($grade->hidden && !has_capability('moodle/grade:viewhidden', \context_course::instance($course->id), $viewerid)) {
            $row['status'] = self::STATUS_HIDDEN;
        } else if ($grade->grade !== null && $grade->grade !== false) {
            $row['status'] = self::STATUS_GRADED;
            $row['display'] = $grade->str_grade;
            $range = $grade->item->grademax - $grade->item->grademin;
            if ($range > 0 && $grade->item->scaleid == 0) {
                $row['percentage'] = max(0.0, min(100.0, grade_summary::normalise(
                    (float)$grade->grade,
                    (float)$grade->item->grademin,
                    (float)$grade->item->grademax
                )));
            }
        }
        return $row;
    }
}
