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
 * Completion totals for a user.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/completionlib.php');

/**
 * Counts completed activities and resources, grouped by module type.
 *
 * Only courses with completion tracking enabled, and modules with completion
 * configured, are counted.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_summary {
    /**
     * Summarise completion across courses.
     *
     * @param int $userid The student.
     * @param \stdClass[] $courses Course records (id, enablecompletion, ...).
     * @return array ['activities' => ['total' => int, 'bytype' => [mod => n]], 'resources' => [...]]
     */
    public static function get_summary(int $userid, array $courses): array {
        $result = [
            'activities' => ['total' => 0, 'bytype' => []],
            'resources' => ['total' => 0, 'bytype' => []],
        ];
        foreach ($courses as $course) {
            foreach (self::get_completed_modnames($userid, $course) as $modname) {
                $key = self::is_resource($modname) ? 'resources' : 'activities';
                $result[$key]['total']++;
                $result[$key]['bytype'][$modname] = ($result[$key]['bytype'][$modname] ?? 0) + 1;
            }
        }
        foreach ($result as &$group) {
            arsort($group['bytype']);
        }
        return $result;
    }

    /**
     * Module names of the completed, visible, tracked modules in a course.
     *
     * @param int $userid The student.
     * @param \stdClass $course Course record.
     * @return string[] One module name per completed module.
     */
    private static function get_completed_modnames(int $userid, \stdClass $course): array {
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            return [];
        }
        $names = [];
        foreach (get_fast_modinfo($course, $userid)->get_cms() as $cm) {
            if (!$cm->uservisible || $cm->deletioninprogress || !$completion->is_enabled($cm)) {
                continue;
            }
            $state = $completion->get_data($cm, false, $userid)->completionstate;
            if ($state == COMPLETION_COMPLETE || $state == COMPLETION_COMPLETE_PASS) {
                $names[] = $cm->modname;
            }
        }
        return $names;
    }

    /**
     * Whether a module type is a resource (content purpose) rather than an activity.
     *
     * @param string $modname Module name, e.g. "page".
     * @return bool
     */
    public static function is_resource(string $modname): bool {
        return plugin_supports('mod', $modname, FEATURE_MOD_PURPOSE, MOD_PURPOSE_OTHER) === MOD_PURPOSE_CONTENT;
    }
}
