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
 * Average activity grade for a user.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/gradelib.php');

/**
 * Unweighted mean of graded activity items, as percentages.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_summary {
    /**
     * Calculate the average.
     *
     * @param int $userid The student.
     * @param \stdClass[] $courses Course records.
     * @param int|null $viewerid Viewing user; null means the current user.
     * @return array ['average' => float|null, 'count' => int]
     */
    public static function get_summary(int $userid, array $courses, ?int $viewerid = null): array {
        $percentages = [];
        foreach ($courses as $course) {
            $percentages = array_merge($percentages, self::get_course_percentages($userid, $course, $viewerid));
        }
        $count = count($percentages);
        return [
            'average' => $count ? array_sum($percentages) / $count : null,
            'count' => $count,
        ];
    }

    /**
     * Percentages of the visible graded activity items in one course.
     *
     * @param int $userid The student.
     * @param \stdClass $course Course record.
     * @param int|null $viewerid Viewing user; null means the current user.
     * @return float[]
     */
    private static function get_course_percentages(int $userid, \stdClass $course, ?int $viewerid): array {
        $percentages = [];
        if (!access::can_view_grades($course, \core_user::get_user($userid, '*', MUST_EXIST), $viewerid)) {
            return $percentages;
        }
        $canviewhidden = has_capability('moodle/grade:viewhidden', \context_course::instance($course->id), $viewerid);
        $items = \grade_item::fetch_all(['courseid' => $course->id, 'itemtype' => 'mod']);
        foreach ($items ?: [] as $item) {
            $grade = \grade_grade::fetch(['itemid' => $item->id, 'userid' => $userid]);
            if (!self::is_countable($item, $grade, $canviewhidden)) {
                continue;
            }
            $percentages[] = self::normalise((float)$grade->finalgrade, (float)$item->grademin, (float)$item->grademax);
        }
        return $percentages;
    }

    /**
     * Whether a grade should enter the average.
     *
     * @param \grade_item $item The item.
     * @param \grade_grade|false $grade The user's grade, if any.
     * @param bool $canviewhidden Whether hidden grades may be included.
     * @return bool
     */
    private static function is_countable(\grade_item $item, $grade, bool $canviewhidden): bool {
        if ($item->gradetype != GRADE_TYPE_VALUE || $item->grademax == $item->grademin) {
            return false;
        }
        if (!$grade || $grade->finalgrade === null) {
            return false;
        }
        return $canviewhidden || !($item->is_hidden() || $grade->is_hidden());
    }

    /**
     * Convert a grade to a percentage of its range.
     *
     * @param float $grade The grade.
     * @param float $min Minimum grade.
     * @param float $max Maximum grade.
     * @return float
     */
    public static function normalise(float $grade, float $min, float $max): float {
        return ($grade - $min) / ($max - $min) * 100;
    }
}
