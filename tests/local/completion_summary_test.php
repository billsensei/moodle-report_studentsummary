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
 * Tests for the completion summary.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

/**
 * Tests for the completion summary.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\local\completion_summary
 */
final class completion_summary_test extends \advanced_testcase {
    /**
     * Resources and activities are counted separately by type.
     */
    public function test_counts(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['enablecompletion' => 1]);
        $user = $gen->create_and_enrol($course, 'student');
        $manual = ['completion' => COMPLETION_TRACKING_MANUAL];
        $page = $gen->create_module('page', ['course' => $course->id] + $manual);
        $page2 = $gen->create_module('page', ['course' => $course->id] + $manual);
        $forum = $gen->create_module('forum', ['course' => $course->id] + $manual);
        $untracked = $gen->create_module('page', ['course' => $course->id]);
        $hidden = $gen->create_module('forum', ['course' => $course->id, 'visible' => 0] + $manual);

        $this->setAdminUser();
        $completion = new \completion_info($course);
        // The untracked page has no completion configured, so it must not count.
        foreach ([$page, $forum, $hidden, $untracked] as $mod) {
            $completion->update_state(get_coursemodule_from_id('', $mod->cmid), COMPLETION_COMPLETE, $user->id, true);
        }

        $summary = completion_summary::get_summary($user->id, [$course]);
        $this->assertSame(1, $summary['resources']['total']);
        $this->assertSame(['page' => 1], $summary['resources']['bytype']);
        $this->assertSame(1, $summary['activities']['total']);
        $this->assertSame(['forum' => 1], $summary['activities']['bytype']);
        $this->assertNotEmpty($page2->cmid);
    }

    /**
     * Courses without completion enabled contribute nothing.
     */
    public function test_completion_disabled(): void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['enablecompletion' => 1]);
        $user = $gen->create_and_enrol($course, 'student');
        $gen->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $course->enablecompletion = 0;
        $this->assertTrue($DB->update_record('course', $course));
        \course_modinfo::clear_instance_cache();
        $summary = completion_summary::get_summary($user->id, [$course]);
        $this->assertSame(0, $summary['resources']['total']);
        $this->assertSame(0, $summary['activities']['total']);
    }

    /**
     * Resource classification follows the module purpose.
     */
    public function test_is_resource(): void {
        $this->assertTrue(completion_summary::is_resource('page'));
        $this->assertTrue(completion_summary::is_resource('url'));
        $this->assertFalse(completion_summary::is_resource('assign'));
        $this->assertFalse(completion_summary::is_resource('forum'));
    }
}
