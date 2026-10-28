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
 * Tests for the course report page.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\output;

/**
 * Tests for the course report page renderable.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\output\course_report_page
 */
final class course_report_page_test extends \advanced_testcase {
    /**
     * One sheet per student, with ids that do not repeat, split into pages.
     */
    public function test_sheets_and_paging(): void {
        global $PAGE;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        for ($i = 0; $i < 11; $i++) {
            $gen->create_and_enrol($course, 'student');
        }
        $gen->create_and_enrol($course, 'student', ['firstname' => 'A}}, {{x}}']);
        $this->setUser($teacher);
        $PAGE->set_url('/report/studentsummary/course.php', ['id' => $course->id]);
        $url = new \moodle_url('/report/studentsummary/course.php', ['id' => $course->id]);

        $page = new course_report_page($course, (int)$teacher->id, $url, 0, null, null, 0, 10);
        $this->assertSame(12, $page->get_count());
        $this->assertCount(10, $page->get_page_students());
        $data = $page->export_for_template($PAGE->get_renderer('core'));
        $this->assertCount(10, $data->students);
        $this->assertNotSame('', $data->pagingbar);

        $second = new course_report_page($course, (int)$teacher->id, $url, 0, null, null, 1, 10);
        $this->assertCount(2, $second->get_page_students());
        // A page number past the end shows the last page.
        $last = new course_report_page($course, (int)$teacher->id, $url, 0, null, null, 99, 10);
        $this->assertCount(2, $last->get_page_students());

        $html = $PAGE->get_renderer('report_studentsummary')->render($page);
        $this->assertSame(10, substr_count($html, 'data-region="report_studentsummary-sheet"'));
        $this->assertStringContainsString('>Print A}}, {{x}} ', $html);
        $ids = [];
        preg_match_all('/ id="(ss-[^"]+)"/', $html, $ids);
        $this->assertSame(count($ids[1]), count(array_unique($ids[1])));
    }

    /**
     * The course list offers each course of the students shown, by name, and the summaries follow the choice.
     */
    public function test_course_choices(): void {
        global $PAGE;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['fullname' => 'Zoology']);
        $other = $gen->create_course(['fullname' => 'Art']);
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $student = $gen->create_and_enrol($course, 'student');
        $gen->enrol_user($student->id, $other->id, 'student');
        $this->setUser($teacher);
        $PAGE->set_url('/report/studentsummary/course.php', ['id' => $course->id]);
        $url = new \moodle_url('/report/studentsummary/course.php', ['id' => $course->id]);

        // A teacher is offered only the course they may see, an administrator both, sorted by name.
        $page = new course_report_page($course, (int)$teacher->id, $url);
        $this->assertSame(['Zoology'], array_values($page->get_course_choices()));
        $this->setAdminUser();
        $page = new course_report_page($course, get_admin()->id, $url);
        $this->assertSame(['Art', 'Zoology'], array_values($page->get_course_choices()));
        $this->assertSame([(int)$other->id, (int)$course->id], array_keys($page->get_course_choices()));

        $this->setUser($teacher);
        $chosen = new course_report_page($course, (int)$teacher->id, $url, 0, null, null, 0, 25, [(int)$course->id]);
        $data = $chosen->export_for_template($PAGE->get_renderer('core'));
        $this->assertSame(1, $data->students[0]['summary']->coursecount);
    }

    /**
     * A course with no students renders the empty message, and an unknown page size falls back to the default.
     */
    public function test_empty_course(): void {
        global $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $PAGE->set_url('/report/studentsummary/course.php', ['id' => $course->id]);
        $url = new \moodle_url('/report/studentsummary/course.php', ['id' => $course->id]);
        $page = new course_report_page($course, get_admin()->id, $url, 0, null, null, 0, 7);
        $data = $page->export_for_template($PAGE->get_renderer('core'));
        $this->assertFalse($data->hasstudents);
        $this->assertSame('', $data->pagingbar);
        $html = $PAGE->get_renderer('report_studentsummary')->render($page);
        $this->assertStringContainsString(get_string('nostudents', 'report_studentsummary'), $html);
    }
}
