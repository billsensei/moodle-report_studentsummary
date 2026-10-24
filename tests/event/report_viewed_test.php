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
 * Tests for the report_viewed event.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\event;

/**
 * Tests for the report_viewed event.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\event\report_viewed
 */
final class report_viewed_test extends \advanced_testcase {
    /**
     * Event data is as expected.
     */
    public function test_event(): void {
        $this->resetAfterTest();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->setUser($teacher);
        $sink = $this->redirectEvents();
        report_viewed::create(['context' => \context_user::instance($student->id), 'relateduserid' => $student->id])->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(report_viewed::class, $event);
        $this->assertSame('r', $event->crud);
        $this->assertSame(report_viewed::LEVEL_PARTICIPATING, $event->edulevel);
        $this->assertEquals($teacher->id, $event->userid);
        $this->assertEquals($student->id, $event->relateduserid);
        $this->assertEquals(\context_user::instance($student->id), $event->get_context());
        $this->assertStringContainsString('studentsummary/index.php', $event->get_url()->out_as_local_url());
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());
    }

    /**
     * The event must use the user context.
     */
    public function test_requires_user_context(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->expectException(\coding_exception::class);
        report_viewed::create(['context' => \context_system::instance(), 'relateduserid' => $user->id]);
    }
}
