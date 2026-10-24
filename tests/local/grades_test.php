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
 * Tests for the grade summary and course grades.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

/**
 * Tests for the grade summary and course grades.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\local\grade_summary
 * @covers     \report_studentsummary\local\course_grades
 */
final class grades_test extends \advanced_testcase {
    /**
     * Create a graded module and grade a user.
     *
     * @param \stdClass $course Course.
     * @param \stdClass $user Student.
     * @param float|null $grade Raw grade, null for none.
     * @param array $extra Extra module settings.
     * @return \stdClass The module.
     */
    private function graded_assign(\stdClass $course, \stdClass $user, ?float $grade, array $extra = []): \stdClass {
        $mod = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'grade' => 100] + $extra);
        if ($grade !== null) {
            $item = \grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => 'assign', 'iteminstance' => $mod->id]);
            $item->update_final_grade($user->id, $grade);
        }
        return $mod;
    }

    /**
     * Mean of normalised grades, ignoring ungraded items.
     */
    public function test_average(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->graded_assign($course, $user, 80);
        $this->graded_assign($course, $user, 40);
        $this->graded_assign($course, $user, null);
        $summary = grade_summary::get_summary($user->id, [$course], get_admin()->id);
        $this->assertSame(2, $summary['count']);
        $this->assertEqualsWithDelta(60.0, $summary['average'], 0.001);
    }

    /**
     * No grades gives a null average.
     */
    public function test_average_none(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $summary = grade_summary::get_summary($user->id, [$course], get_admin()->id);
        $this->assertNull($summary['average']);
        $this->assertSame(0, $summary['count']);
    }

    /**
     * Hidden grades are excluded for viewers who cannot see them.
     */
    public function test_hidden_grades(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $user = $gen->create_and_enrol($course, 'student');
        $this->graded_assign($course, $user, 90);
        $hidden = $this->graded_assign($course, $user, 10);
        \grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => 'assign', 'iteminstance' => $hidden->id])->set_hidden(1, false);

        $asstudent = grade_summary::get_summary($user->id, [$course], $user->id);
        $this->assertSame(1, $asstudent['count']);
        $this->assertEqualsWithDelta(90.0, $asstudent['average'], 0.001);
        $asadmin = grade_summary::get_summary($user->id, [$course], get_admin()->id);
        $this->assertSame(2, $asadmin['count']);
    }

    /**
     * Course totals: graded, not graded and hidden.
     */
    public function test_course_grades(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $graded = $gen->create_course();
        $empty = $gen->create_course();
        $user = $gen->create_and_enrol($graded, 'student');
        $gen->enrol_user($user->id, $empty->id, 'student');
        $viewer = $gen->create_and_enrol($graded, 'student');
        $this->graded_assign($graded, $user, 50);

        $rows = course_grades::get_rows($user->id, [$graded, $empty], get_admin()->id);
        $this->assertSame(course_grades::STATUS_GRADED, $rows[0]['status']);
        $this->assertEqualsWithDelta(50.0, $rows[0]['percentage'], 0.001);
        $this->assertSame(course_grades::STATUS_NOTGRADED, $rows[1]['status']);

        \grade_item::fetch_course_item($graded->id)->set_hidden(1, false);
        $rows = course_grades::get_rows($user->id, [$graded], $viewer->id);
        $this->assertSame(course_grades::STATUS_HIDDEN, $rows[0]['status']);
        $rows = course_grades::get_rows($user->id, [$graded], get_admin()->id);
        $this->assertSame(course_grades::STATUS_GRADED, $rows[0]['status']);
    }

    /**
     * Grades are withheld from viewers without moodle/grade:viewall, and from students when the gradebook is hidden.
     */
    public function test_grade_visibility(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $user = $gen->create_and_enrol($course, 'student');
        $other = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $this->graded_assign($course, $user, 70);

        // Another student never sees the grades; the student and the teacher do.
        $this->assertSame(0, grade_summary::get_summary($user->id, [$course], $other->id)['count']);
        $this->assertSame(
            \report_studentsummary\local\course_grades::STATUS_HIDDEN,
            course_grades::get_rows($user->id, [$course], $other->id)[0]['status']
        );
        $this->assertSame(1, grade_summary::get_summary($user->id, [$course], $user->id)['count']);
        $this->assertSame(1, grade_summary::get_summary($user->id, [$course], $teacher->id)['count']);

        // Turning "Show gradebook to students" off hides them from the student, not from the teacher.
        update_course((object)['id' => $course->id, 'showgrades' => 0]);
        $course = get_course($course->id);
        $this->assertSame(0, grade_summary::get_summary($user->id, [$course], $user->id)['count']);
        $this->assertSame(
            \report_studentsummary\local\course_grades::STATUS_HIDDEN,
            course_grades::get_rows($user->id, [$course], $user->id)[0]['status']
        );
        $this->assertSame(1, grade_summary::get_summary($user->id, [$course], $teacher->id)['count']);
    }
}
