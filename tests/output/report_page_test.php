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
 * Tests for the report page renderable.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\output;

/**
 * Tests for the report page renderable.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\output\report_page
 * @covers     \report_studentsummary\output\renderer
 */
final class report_page_test extends \advanced_testcase {
    /**
     * Durations are formatted as hours and minutes.
     */
    public function test_format_duration(): void {
        $this->assertSame('0h 0m', report_page::format_duration(59));
        $this->assertSame('1h 1m', report_page::format_duration(3661));
    }

    /**
     * The exported data and rendered HTML contain the expected boxes and escape names.
     */
    public function test_render(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['fullname' => 'Maths <b>101</b>', 'enablecompletion' => 1]);
        $user = $gen->create_and_enrol($course, 'student');
        $DB->set_field('user', 'firstname', '<i>Ada</i>', ['id' => $user->id]);
        $user = \core_user::get_user($user->id);
        $gen->create_module('page', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $this->setAdminUser();

        $page = new report_page($user, get_admin()->id);
        $data = $page->export_for_template($PAGE->get_renderer('core'));
        $this->assertCount(4, $data->kpis);
        $this->assertCount(2, $data->breakdowns);
        $this->assertSame(1, $data->coursecount);
        $this->assertFalse($data->hasmorecourses);
        $this->assertSame(get_string('alltime', 'report_studentsummary'), $data->range);

        $PAGE->set_url('/report/studentsummary/index.php');
        $html = $PAGE->get_renderer('report_studentsummary')->render($page);
        $this->assertStringContainsString('report-studentsummary', $html);
        $this->assertStringContainsString('ss-box-rose', $html);
        $this->assertStringNotContainsString('<i>Ada</i>', $html);
        $this->assertStringNotContainsString('<b>101</b>', $html);
        $this->assertStringContainsString('&lt;i&gt;Ada&lt;/i&gt;', $html);

        // The name is an h2 and the boxes are h3, so the order holds under the page's h1.
        $this->assertSame(1, preg_match_all('/<h2 class="ss-name">/', $html));
        $this->assertSame(0, preg_match_all('/<h2 class="ss-box-heading"/', $html));
        $this->assertSame(8, preg_match_all('/<h3 class="ss-box-heading"/', $html));
        // Both tables are named by their box heading.
        $this->assertStringContainsString('<table class="ss-table" aria-labelledby="ss-courses-heading">', $html);
        $this->assertStringContainsString('<table class="ss-table" aria-labelledby="ss-grades-heading">', $html);
    }

    /**
     * Only the chosen courses are summarised.
     */
    public function test_course_choice(): void {
        global $PAGE;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $first = $gen->create_course(['fullname' => 'First course']);
        $second = $gen->create_course(['fullname' => 'Second course']);
        $user = $gen->create_and_enrol($first, 'student');
        $gen->enrol_user($user->id, $second->id, 'student');
        $this->setAdminUser();
        $renderer = $PAGE->get_renderer('core');

        $all = (new report_page($user, get_admin()->id))->export_for_template($renderer);
        $this->assertSame(2, $all->coursecount);
        $empty = (new report_page($user, get_admin()->id, null, null, '', []))->export_for_template($renderer);
        $this->assertSame(2, $empty->coursecount);

        $chosen = (new report_page($user, get_admin()->id, null, null, '', [(int)$second->id]))->export_for_template($renderer);
        $this->assertSame(1, $chosen->coursecount);
        $this->assertSame('Second course', $chosen->courses[0]['name']);
        $this->assertSame('Second course', $chosen->grades[0]['name']);
    }

    /**
     * A course choice can only narrow the report: a course the viewer may not see stays out.
     */
    public function test_course_choice_cannot_widen(): void {
        global $PAGE;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $mine = $gen->create_course();
        $other = $gen->create_course();
        $user = $gen->create_and_enrol($mine, 'student');
        $gen->enrol_user($user->id, $other->id, 'student');
        $teacher = $gen->create_and_enrol($mine, 'editingteacher');
        $this->setUser($teacher);
        $renderer = $PAGE->get_renderer('core');

        $data = (new report_page($user, (int)$teacher->id, null, null, '', [(int)$other->id]))->export_for_template($renderer);
        $this->assertSame(0, $data->coursecount);
        $this->assertFalse($data->hascourses);

        $data = (new report_page($user, (int)$teacher->id, null, null, '', [(int)$mine->id, (int)$other->id]))
            ->export_for_template($renderer);
        $this->assertSame(1, $data->coursecount);
    }

    /**
     * Courses over the cap are summarised.
     */
    public function test_course_cap(): void {
        global $PAGE;
        $this->resetAfterTest();
        set_config('maxcourses', 1, 'report_studentsummary');
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $gen->enrol_user($user->id, $gen->create_course()->id, 'student');
        $gen->enrol_user($user->id, $gen->create_course()->id, 'student');
        $data = (new report_page($user, $user->id))->export_for_template($PAGE->get_renderer('core'));
        $this->assertSame(2, $data->coursecount);
        $this->assertCount(1, $data->courses);
        $this->assertTrue($data->hasmorecourses);
        $this->assertSame(1, $data->morecourses);
    }

    /**
     * Find a box by id in a list of box contexts.
     *
     * @param array $boxes Box contexts.
     * @param string $id Box id.
     * @return array
     */
    private function find_box(array $boxes, string $id): array {
        foreach ($boxes as $box) {
            if ($box['boxid'] === $id) {
                return $box;
            }
        }
        $this->fail("Box $id not found");
    }

    /**
     * A student with no courses gets a complete report with empty states.
     */
    public function test_no_courses(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $data = (new report_page($user, $user->id))->export_for_template($PAGE->get_renderer('core'));
        $this->assertSame(0, $data->coursecount);
        $this->assertFalse($data->hascourses);
        $this->assertSame('0', $this->find_box($data->kpis, 'ss-activities')['figure']);
        $this->assertSame(get_string('na', 'report_studentsummary'), $this->find_box($data->kpis, 'ss-averagegrade')['figure']);
        $PAGE->set_url('/report/studentsummary/index.php');
        $html = $PAGE->get_renderer('report_studentsummary')->render(new report_page($user, $user->id));
        $this->assertStringContainsString(get_string('nocourses', 'report_studentsummary'), $html);
    }

    /**
     * Without completion tracking the activity and resource boxes explain why they are empty.
     */
    public function test_no_completion_note(): void {
        global $PAGE;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['enablecompletion' => 0]);
        $user = $gen->create_and_enrol($course, 'student');
        $data = (new report_page($user, $user->id))->export_for_template($PAGE->get_renderer('core'));
        $note = get_string('nocompletion', 'report_studentsummary');
        $this->assertSame($note, $this->find_box($data->kpis, 'ss-activities')['note']);
        $this->assertSame($note, $this->find_box($data->kpis, 'ss-resources')['note']);
        $this->assertFalse($data->courses[0]['hasprogress']);
    }

    /**
     * With the standard log store off, the time box shows a notice instead of zero.
     */
    public function test_time_without_logs(): void {
        global $PAGE;
        $this->resetAfterTest();
        set_config('enabled_stores', '', 'tool_log');
        get_log_manager(true);
        $user = $this->getDataGenerator()->create_user();
        $box = $this->find_box(
            (new report_page($user, $user->id))->export_for_template($PAGE->get_renderer('core'))->kpis,
            'ss-timespent'
        );
        $this->assertSame(get_string('na', 'report_studentsummary'), $box['figure']);
        $this->assertSame(get_string('logsunavailable', 'report_studentsummary'), $box['note']);
    }

    /**
     * The administrator can hide the time box content.
     */
    public function test_time_disabled(): void {
        global $PAGE;
        $this->resetAfterTest();
        set_config('showtime', '0', 'report_studentsummary');
        $user = $this->getDataGenerator()->create_user();
        $box = $this->find_box(
            (new report_page($user, $user->id))->export_for_template($PAGE->get_renderer('core'))->kpis,
            'ss-timespent'
        );
        $this->assertSame(get_string('timedisabled', 'report_studentsummary'), $box['note']);
    }

    /**
     * An invalid course cap falls back to showing at least one course.
     */
    public function test_cap_clamped(): void {
        global $PAGE;
        $this->resetAfterTest();
        set_config('maxcourses', -5, 'report_studentsummary');
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $gen->enrol_user($user->id, $gen->create_course()->id, 'student');
        $gen->enrol_user($user->id, $gen->create_course()->id, 'student');
        $data = (new report_page($user, $user->id))->export_for_template($PAGE->get_renderer('core'));
        $this->assertCount(1, $data->courses);
    }

    /**
     * A very large course cap is limited so one request cannot list an unbounded number of courses.
     */
    public function test_cap_upper_limit(): void {
        global $PAGE;
        $this->resetAfterTest();
        set_config('maxcourses', 100000, 'report_studentsummary');
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $total = report_page::MAXCOURSES_LIMIT + 2;
        for ($i = 0; $i < $total; $i++) {
            $gen->enrol_user($user->id, $gen->create_course()->id, 'student');
        }
        $data = (new report_page($user, $user->id))->export_for_template($PAGE->get_renderer('core'));
        $this->assertSame($total, $data->coursecount);
        $this->assertCount(report_page::MAXCOURSES_LIMIT, $data->courses);
        $this->assertSame(2, $data->morecourses);
    }

    /**
     * The report carries the configured colours as custom properties on its wrapper.
     */
    public function test_colours_in_output(): void {
        global $PAGE;
        $this->resetAfterTest();
        set_config('palette', 'highcontrast', 'report_studentsummary');
        set_config('colour_courses', '#E6FFFA', 'report_studentsummary');
        $user = $this->getDataGenerator()->create_user();
        $page = new report_page($user, $user->id);
        $data = $page->export_for_template($PAGE->get_renderer('core'));
        $this->assertStringContainsString('--ss-ink:#000000;', $data->colourstyle);
        $this->assertStringContainsString('--ss-teal-bg:#e6fffa;', $data->colourstyle);
        $this->assertStringContainsString('--ss-blue-border:#0b3d91;', $data->colourstyle);
        $html = $PAGE->get_renderer('report_studentsummary')->render($page);
        $this->assertStringContainsString('class="report-studentsummary" style="--ss-ink:#000000;', $html);
    }
}
