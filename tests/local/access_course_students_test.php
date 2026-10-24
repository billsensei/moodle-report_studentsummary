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
 * Tests for the course student list.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

/**
 * Tests for the course student list.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\local\access
 */
final class access_course_students_test extends \advanced_testcase {
    /**
     * A teacher gets the active students of the course only.
     */
    public function test_course_students(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $other = $gen->create_course();
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $ada = $gen->create_and_enrol($course, 'student', ['lastname' => 'Adams']);
        $zed = $gen->create_and_enrol($course, 'student', ['lastname' => 'Zed']);
        $gen->create_and_enrol($course, 'student', ['suspended' => 1]);
        $gen->create_and_enrol($course, 'teacher');
        $gen->create_and_enrol($other, 'student');
        $suspended = $gen->create_and_enrol($course, 'student');
        $gen->enrol_user($suspended->id, $course->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $students = access::get_course_students($course, $teacher->id);
        $this->assertSame([(int)$ada->id, (int)$zed->id], array_map('intval', array_keys($students)));
        // Only the columns the report needs are loaded, never the password hash.
        $this->assertFalse(property_exists($students[$ada->id], 'password'));
        $this->assertSame('Adams', $students[$ada->id]->lastname);
    }

    /**
     * Students, guests and teachers of another course get nothing.
     */
    public function test_course_students_requires_viewall(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $other = $gen->create_course();
        $student = $gen->create_and_enrol($course, 'student');
        $gen->create_and_enrol($course, 'student');
        $outsider = $gen->create_and_enrol($other, 'editingteacher');
        $this->assertSame([], access::get_course_students($course, $student->id));
        $this->assertSame([], access::get_course_students($course, $outsider->id));
        $this->assertSame([], access::get_course_students(get_course(SITEID), get_admin()->id));
    }

    /**
     * Under separate groups a teacher gets their own group's students; the group filter narrows further.
     */
    public function test_course_students_groups(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['groupmode' => SEPARATEGROUPS, 'groupmodeforce' => 1]);
        $teacher = $gen->create_and_enrol($course, 'teacher');
        $mine = $gen->create_and_enrol($course, 'student');
        $theirs = $gen->create_and_enrol($course, 'student');
        $g1 = $gen->create_group(['courseid' => $course->id]);
        $g2 = $gen->create_group(['courseid' => $course->id]);
        $gen->create_group_member(['groupid' => $g1->id, 'userid' => $teacher->id]);
        $gen->create_group_member(['groupid' => $g1->id, 'userid' => $mine->id]);
        $gen->create_group_member(['groupid' => $g2->id, 'userid' => $theirs->id]);
        $course = get_course($course->id);

        $this->assertSame([(int)$mine->id], array_map('intval', array_keys(access::get_course_students($course, $teacher->id))));
        $this->assertSame([], access::get_course_students($course, $teacher->id, (int)$g2->id));
        $this->assertCount(2, access::get_course_students($course, get_admin()->id));
        $bygroup = access::get_course_students($course, get_admin()->id, (int)$g2->id);
        $this->assertSame([(int)$theirs->id], array_map('intval', array_keys($bygroup)));
    }
}
