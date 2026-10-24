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
 * Tests for the course report viewed event.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\event;

/**
 * Tests for the course report viewed event.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\event\course_report_viewed
 */
final class course_report_viewed_test extends \advanced_testcase {
    /**
     * The event is triggered in the course context and links back to the course page.
     */
    public function test_event(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        $sink = $this->redirectEvents();
        course_report_viewed::create(['context' => \context_course::instance($course->id)])->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(course_report_viewed::class, $events[0]);
        $this->assertSame((int)$course->id, (int)$events[0]->courseid);
        $this->assertStringContainsString('course.php?id=' . $course->id, $events[0]->get_url()->out(false));
        $this->assertNotEmpty($events[0]->get_description());
    }
}
