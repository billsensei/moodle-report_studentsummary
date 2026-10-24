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
 * Tests for the access rules.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

/**
 * Tests for the access rules.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\local\access
 */
final class access_test extends \advanced_testcase {
    /**
     * Students see only their own report.
     */
    public function test_student_own_and_other(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $a = $gen->create_and_enrol($course, 'student');
        $b = $gen->create_and_enrol($course, 'student');
        $this->assertTrue(access::can_view_user($a, $a->id));
        $this->assertFalse(access::can_view_user($b, $a->id));
        $this->assertFalse(access::can_view_user($a, $b->id));
    }

    /**
     * Teachers see students in their courses only.
     */
    public function test_teacher_scope(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $mine = $gen->create_course();
        $other = $gen->create_course();
        $teacher = $gen->create_and_enrol($mine, 'editingteacher');
        $student = $gen->create_and_enrol($mine, 'student');
        $outsider = $gen->create_and_enrol($other, 'student');
        $this->assertTrue(access::can_view_user($student, $teacher->id));
        $this->assertFalse(access::can_view_user($outsider, $teacher->id));

        // A student of both courses: only the teacher's course is included.
        $gen->enrol_user($student->id, $other->id, 'student');
        $this->assertSame([(int)$mine->id], array_map('intval', array_keys(access::get_courses($student, $teacher->id))));
        $this->assertCount(2, access::get_courses($student, $student->id));
    }

    /**
     * Admins can view anyone; deleted users and guests cannot be viewed or view.
     */
    public function test_admin_deleted_guest(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $student = $gen->create_user();
        $this->assertTrue(access::can_view_user($student, get_admin()->id));
        $this->assertFalse(access::can_view_user($student, guest_user()->id));
        $deleted = $gen->create_user();
        delete_user($deleted);
        $deleted = \core_user::get_user($deleted->id);
        $this->assertFalse(access::can_view_user($deleted, get_admin()->id));
    }

    /**
     * Separate groups limit teachers without accessallgroups.
     */
    public function test_separate_groups(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['groupmode' => SEPARATEGROUPS, 'groupmodeforce' => 1]);
        $teacher = $gen->create_and_enrol($course, 'teacher');
        $s1 = $gen->create_and_enrol($course, 'student');
        $s2 = $gen->create_and_enrol($course, 'student');
        $g1 = $gen->create_group(['courseid' => $course->id]);
        $g2 = $gen->create_group(['courseid' => $course->id]);
        $gen->create_group_member(['groupid' => $g1->id, 'userid' => $teacher->id]);
        $gen->create_group_member(['groupid' => $g1->id, 'userid' => $s1->id]);
        $gen->create_group_member(['groupid' => $g2->id, 'userid' => $s2->id]);
        $this->assertTrue(access::can_view_user($s1, $teacher->id));
        $this->assertFalse(access::can_view_user($s2, $teacher->id));
    }

    /**
     * Hidden courses are excluded for viewers who cannot see them.
     */
    public function test_hidden_course(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['visible' => 0]);
        $student = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'teacher');
        $this->assertCount(0, access::get_courses($student, $student->id));
        $this->assertCount(1, access::get_courses($student, $teacher->id));
    }

    /**
     * A teacher whose enrolment is suspended keeps the role assignment but loses access.
     */
    public function test_suspended_teacher_enrolment(): void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $student = $gen->create_and_enrol($course, 'student');
        $this->assertTrue(access::can_view_user($student, $teacher->id));

        $enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, ['enrolid' => $enrol->id, 'userid' => $teacher->id]);
        \core\session\manager::init_empty_session();
        accesslib_clear_all_caches_for_unit_testing();
        $this->assertFalse(access::can_view_user($student, $teacher->id));
        $this->assertCount(0, access::get_courses($student, $teacher->id));
    }

    /**
     * Grade permissions follow the user grade report.
     */
    public function test_can_view_grades(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $a = $gen->create_and_enrol($course, 'student');
        $b = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'teacher');
        $this->assertTrue(access::can_view_grades($course, $a, $a->id));
        $this->assertFalse(access::can_view_grades($course, $a, $b->id));
        $this->assertTrue(access::can_view_grades($course, $a, $teacher->id));
        update_course((object)['id' => $course->id, 'showgrades' => 0]);
        $course = get_course($course->id);
        $this->assertFalse(access::can_view_grades($course, $a, $a->id));
        $this->assertTrue(access::can_view_grades($course, $a, $teacher->id));
    }

    /**
     * Site-level activity is visible to yourself and to user-context or site viewers, not to a course teacher.
     */
    public function test_can_view_site_activity(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $student = $gen->create_and_enrol($course, 'student');
        $this->assertTrue(access::can_view_site_activity($student, $student->id));
        $this->assertTrue(access::can_view_site_activity($student, get_admin()->id));
        $this->assertFalse(access::can_view_site_activity($student, $teacher->id));
    }
}
