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
 * Tests for the time estimator.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

/**
 * Tests for the time estimator.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\local\time_estimator
 */
final class time_estimator_test extends \advanced_testcase {
    /**
     * Build an event object.
     *
     * @param int $time Timestamp.
     * @param int $courseid Course id.
     * @param int $level Context level.
     * @return \stdClass
     */
    private function event(int $time, int $courseid = 2, int $level = CONTEXT_COURSE): \stdClass {
        return (object)['courseid' => $courseid, 'contextlevel' => $level, 'contextinstanceid' => 1, 'timecreated' => $time];
    }

    /**
     * No events gives zero.
     */
    public function test_no_events(): void {
        $r = time_estimator::accumulate([], 1200, 60);
        $this->assertSame(0, $r['site']);
        $this->assertSame(0, $r['events']);
    }

    /**
     * A single event gets the tail time.
     */
    public function test_single_event(): void {
        $r = time_estimator::accumulate([$this->event(1000)], 1200, 60);
        $this->assertSame(60, $r['site']);
    }

    /**
     * Gaps up to the threshold count in full; larger ones get the tail.
     */
    public function test_gap_threshold(): void {
        $r = time_estimator::accumulate([$this->event(0), $this->event(1200), $this->event(2401)], 1200, 60);
        // 1200 (equal to threshold) + 60 (gap of 1201) + 60 (last event).
        $this->assertSame(1320, $r['site']);
    }

    /**
     * Module events are bucketed as activity time and by course.
     */
    public function test_buckets(): void {
        $events = [
            $this->event(0, 2, CONTEXT_MODULE),
            $this->event(100, 2, CONTEXT_COURSE),
            $this->event(200, 3, CONTEXT_MODULE),
            $this->event(300, SITEID, CONTEXT_SYSTEM),
        ];
        $r = time_estimator::accumulate($events, 1200, 60);
        $this->assertSame(100 + 100 + 100 + 60, $r['site']);
        $this->assertSame(200, $r['activities']);
        $this->assertSame([2 => 200, 3 => 100], $r['bycourse']);
    }

    /**
     * Only time in permitted courses is credited, with gaps still measured across all events.
     */
    public function test_course_filter(): void {
        $events = [
            $this->event(0, 2),
            $this->event(100, 3),
            $this->event(300, 2),
            $this->event(400, SITEID, CONTEXT_SYSTEM),
        ];
        $r = time_estimator::accumulate($events, 1200, 60, [2]);
        // Event 1 owns 100s and event 3 owns 100s; the course 3 and site events add nothing.
        $this->assertSame(200, $r['site']);
        $this->assertSame([2 => 200], $r['bycourse']);
        $this->assertSame(2, $r['events']);
        $this->assertSame(0, time_estimator::accumulate($events, 1200, 60, [])['site']);
        $this->assertSame(460, time_estimator::accumulate($events, 1200, 60)['site']);
    }

    /**
     * Log queries honour the date range and skip anonymous rows.
     */
    public function test_get_summary_from_log(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        get_log_manager(true);
        $user = $this->getDataGenerator()->create_user();
        $base = ['userid' => $user->id, 'courseid' => 0, 'contextlevel' => CONTEXT_SYSTEM, 'contextinstanceid' => 0,
            'contextid' => 1, 'eventname' => '\core\event\user_loggedin', 'component' => 'core', 'action' => 'loggedin',
            'target' => 'user', 'crud' => 'r', 'edulevel' => 0, 'origin' => 'web', 'ip' => '127.0.0.1', 'realuserid' => null,
            'other' => 'N;'];
        foreach ([[1000, 0], [1300, 0], [1400, 1], [5000, 0]] as [$time, $anon]) {
            $DB->insert_record('logstore_standard_log', (object)($base + ['timecreated' => $time, 'anonymous' => $anon]));
        }
        $all = time_estimator::get_summary($user->id, null, null, 1200, 60);
        // Events at 1000, 1300 and 5000 count; the anonymous row is skipped: 300 + 60 + 60.
        $this->assertSame(3, $all['events']);
        $this->assertSame(420, $all['site']);
        $ranged = time_estimator::get_summary($user->id, 1200, 2000, 1200, 60);
        $this->assertSame(1, $ranged['events']);
        set_config('enabled_stores', '', 'tool_log');
        get_log_manager(true);
    }

    /**
     * Unavailable logs give null rather than zero.
     */
    public function test_logs_unavailable(): void {
        $this->resetAfterTest();
        set_config('enabled_stores', '', 'tool_log');
        get_log_manager(true);
        $this->assertFalse(time_estimator::logs_available());
        $this->assertNull(time_estimator::get_summary(2));
    }

    /**
     * A repeated request is served from the session cache until it is purged.
     */
    public function test_summary_is_cached(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        get_log_manager(true);
        $user = $this->getDataGenerator()->create_user();
        $base = ['userid' => $user->id, 'courseid' => 0, 'contextlevel' => CONTEXT_SYSTEM, 'contextinstanceid' => 0,
            'contextid' => 1, 'eventname' => '\core\event\user_loggedin', 'component' => 'core', 'action' => 'loggedin',
            'target' => 'user', 'crud' => 'r', 'edulevel' => 0, 'origin' => 'web', 'ip' => '127.0.0.1', 'realuserid' => null,
            'other' => 'N;', 'anonymous' => 0];
        $DB->insert_record('logstore_standard_log', (object)($base + ['timecreated' => 1000]));
        $first = time_estimator::get_summary($user->id, null, null, 1200, 60, [0, 2]);
        $this->assertSame(1, $first['events']);

        // New log rows are not seen by the identical request, whatever order the course ids are in.
        $DB->insert_record('logstore_standard_log', (object)($base + ['timecreated' => 1100]));
        $this->assertSame($first, time_estimator::get_summary($user->id, null, null, 1200, 60, [2, 0, 2]));
        // A different scope or range is a different request.
        $this->assertSame(2, time_estimator::get_summary($user->id, null, null, 1200, 60, [0])['events']);
        $this->assertSame(2, time_estimator::get_summary($user->id, null, null, 1200, 60)['events']);

        \cache::make('report_studentsummary', 'timesummary')->purge();
        $this->assertSame(2, time_estimator::get_summary($user->id, null, null, 1200, 60, [0, 2])['events']);
        set_config('enabled_stores', '', 'tool_log');
        get_log_manager(true);
    }
}
