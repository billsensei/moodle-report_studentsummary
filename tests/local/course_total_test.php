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
 * Tests for the course total when it contains hidden grade items.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

/**
 * Tests for the course total when it contains hidden grade items.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\local\course_grades
 */
final class course_total_test extends \advanced_testcase {
    /**
     * Create a graded module and grade a user.
     *
     * @param \stdClass $course Course.
     * @param \stdClass $user Student.
     * @param float|null $grade Raw grade, null for none.
     * @return \stdClass The module.
     */
    private function graded_assign(\stdClass $course, \stdClass $user, ?float $grade): \stdClass {
        $mod = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'grade' => 100]);
        if ($grade !== null) {
            $item = \grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => 'assign', 'iteminstance' => $mod->id]);
            $item->update_final_grade($user->id, $grade);
        }
        return $mod;
    }

    /**
     * Set up a course where the total contains a hidden item (80 visible, 20 hidden, each out of 100).
     *
     * @param int $mode The "show totals if they contain hidden items" setting for both reports.
     * @return array [course, student, teacher]
     */
    private function course_with_hidden_item(int $mode): array {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $user = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $this->graded_assign($course, $user, 80);
        $secret = $this->graded_assign($course, $user, 20);
        \grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => 'assign', 'iteminstance' => $secret->id])->set_hidden(1, false);
        grade_regrade_final_grades($course->id);
        set_config('grade_report_user_showtotalsifcontainhidden', $mode);
        set_config('grade_report_overview_showtotalsifcontainhidden', $mode);
        return [$course, $user, $teacher];
    }

    /**
     * Default setting: a student's course total is hidden when it contains a hidden item, as in the core reports.
     */
    public function test_total_hidden_when_it_contains_hidden_items(): void {
        $this->resetAfterTest();
        [$course, $user, $teacher] = $this->course_with_hidden_item(GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN);

        $row = course_grades::get_rows($user->id, [$course], $user->id)[0];
        $this->assertSame(course_grades::STATUS_HIDDEN, $row['status']);
        $this->assertSame('', $row['display']);
        $this->assertNull($row['percentage']);

        // Viewers who may see hidden grades still get the real total (100 out of 200).
        foreach ([$teacher->id, get_admin()->id] as $viewerid) {
            $row = course_grades::get_rows($user->id, [$course], $viewerid)[0];
            $this->assertSame(course_grades::STATUS_GRADED, $row['status']);
            $this->assertEqualsWithDelta(50.0, $row['percentage'], 0.001);
        }
    }

    /**
     * "Show totals excluding hidden items": the student sees 80 out of 100, never the hidden 20.
     */
    public function test_total_excludes_hidden_items(): void {
        $this->resetAfterTest();
        [$course, $user] = $this->course_with_hidden_item(GRADE_REPORT_SHOW_TOTAL_IF_CONTAINS_HIDDEN);

        $row = course_grades::get_rows($user->id, [$course], $user->id)[0];
        $this->assertSame(course_grades::STATUS_GRADED, $row['status']);
        $this->assertEqualsWithDelta(80.0, $row['percentage'], 0.001);
        $this->assertStringContainsString('80', $row['display']);
        $this->assertStringNotContainsString('100', $row['display']);
    }

    /**
     * "Show real totals": the setting allows the full total, so it is shown.
     */
    public function test_total_real_when_allowed(): void {
        $this->resetAfterTest();
        [$course, $user] = $this->course_with_hidden_item(GRADE_REPORT_SHOW_REAL_TOTAL_IF_CONTAINS_HIDDEN);

        $row = course_grades::get_rows($user->id, [$course], $user->id)[0];
        $this->assertSame(course_grades::STATUS_GRADED, $row['status']);
        $this->assertEqualsWithDelta(50.0, $row['percentage'], 0.001);
    }

    /**
     * The stricter of the user report and overview settings applies.
     */
    public function test_stricter_setting_wins(): void {
        $this->resetAfterTest();
        [$course, $user] = $this->course_with_hidden_item(GRADE_REPORT_SHOW_REAL_TOTAL_IF_CONTAINS_HIDDEN);
        set_config('grade_report_overview_showtotalsifcontainhidden', GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN);

        $row = course_grades::get_rows($user->id, [$course], $user->id)[0];
        $this->assertSame(course_grades::STATUS_HIDDEN, $row['status']);
    }

    /**
     * When every graded item behind the total is hidden, the recalculated total is empty and must not fall back to the real one.
     */
    public function test_total_hidden_when_all_items_are_hidden(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        foreach ([GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN, GRADE_REPORT_SHOW_TOTAL_IF_CONTAINS_HIDDEN] as $mode) {
            set_config('grade_report_user_showtotalsifcontainhidden', $mode);
            set_config('grade_report_overview_showtotalsifcontainhidden', $mode);
            $course = $gen->create_course();
            $user = $gen->create_and_enrol($course, 'student');
            $only = $this->graded_assign($course, $user, 75);
            \grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => 'assign', 'iteminstance' => $only->id])->set_hidden(1, false);
            grade_regrade_final_grades($course->id);

            $row = course_grades::get_rows($user->id, [$course], $user->id)[0];
            $this->assertSame(course_grades::STATUS_HIDDEN, $row['status'], "mode $mode");
            $this->assertSame('', $row['display']);
            $this->assertNull($row['percentage']);
        }
    }

    /**
     * A course with no hidden items is unaffected by the setting.
     */
    public function test_total_without_hidden_items(): void {
        $this->resetAfterTest();
        set_config('grade_report_user_showtotalsifcontainhidden', GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN);
        set_config('grade_report_overview_showtotalsifcontainhidden', GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->graded_assign($course, $user, 60);

        $row = course_grades::get_rows($user->id, [$course], $user->id)[0];
        $this->assertSame(course_grades::STATUS_GRADED, $row['status']);
        $this->assertEqualsWithDelta(60.0, $row['percentage'], 0.001);
    }
}
