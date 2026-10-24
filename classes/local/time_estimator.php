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
 * Estimated time on site from the standard log.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

/**
 * Estimates time using the gap (sessionisation) method.
 *
 * For consecutive events, a gap up to the idle timeout counts in full; a
 * longer gap, and the final event, count as the tail time.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class time_estimator {
    /** @var int Default idle timeout in seconds. */
    public const DEFAULT_IDLETIMEOUT = 1200;
    /** @var int Default tail time in seconds. */
    public const DEFAULT_TAILTIME = 60;

    /**
     * Whether the standard log store is enabled and readable.
     *
     * @return bool
     */
    public static function logs_available(): bool {
        $readers = get_log_manager()->get_readers('\core\log\sql_reader');
        return isset($readers['logstore_standard']);
    }

    /**
     * Estimate time for a user from the log.
     *
     * @param int $userid The student.
     * @param int|null $from Start timestamp (inclusive), null for no limit.
     * @param int|null $to End timestamp (inclusive), null for no limit.
     * @param int|null $idletimeout Seconds, null to use the plugin setting.
     * @param int|null $tailtime Seconds, null to use the plugin setting.
     * @param int[]|null $courseids Course ids (SITEID for site-level events) whose time is credited, null for all.
     * @return array|null Null if logs are unavailable, else as for {@see self::accumulate()}.
     */
    public static function get_summary(
        int $userid,
        ?int $from = null,
        ?int $to = null,
        ?int $idletimeout = null,
        ?int $tailtime = null,
        ?array $courseids = null
    ): ?array {
        if (!self::logs_available()) {
            return null;
        }
        $idletimeout ??= (int)(get_config('report_studentsummary', 'idletimeout') ?: self::DEFAULT_IDLETIMEOUT);
        $tailtime ??= (int)(get_config('report_studentsummary', 'tailtime') ?: self::DEFAULT_TAILTIME);

        // Scanning the log is the expensive part, so keep the result in the viewer's session for a few minutes.
        $cache = \cache::make('report_studentsummary', 'timesummary');
        $key = self::cache_key([$userid, $from, $to, $idletimeout, $tailtime, $courseids]);
        $cached = $cache->get($key);
        if (is_array($cached)) {
            return $cached;
        }
        $result = self::scan($userid, $from, $to, $idletimeout, $tailtime, $courseids);
        $cache->set($key, $result);
        return $result;
    }

    /**
     * Cache key for a set of arguments; the course ids are order-insensitive.
     *
     * @param array $args Arguments that determine the result.
     * @return string
     */
    private static function cache_key(array $args): string {
        if (is_array($args[5])) {
            $ids = array_unique(array_map('intval', $args[5]));
            sort($ids);
            $args[5] = $ids;
        }
        return md5(json_encode($args));
    }

    /**
     * Read the log and sum the time.
     *
     * @param int $userid The student.
     * @param int|null $from Start timestamp (inclusive).
     * @param int|null $to End timestamp (inclusive).
     * @param int $idletimeout Seconds.
     * @param int $tailtime Seconds.
     * @param int[]|null $courseids Courses whose time is credited, null for all.
     * @return array As for {@see self::accumulate()}.
     */
    private static function scan(int $userid, ?int $from, ?int $to, int $idletimeout, int $tailtime, ?array $courseids): array {
        global $DB;

        $where = 'userid = :userid AND anonymous = 0';
        $params = ['userid' => $userid];
        if ($from !== null) {
            $where .= ' AND timecreated >= :from';
            $params['from'] = $from;
        }
        if ($to !== null) {
            $where .= ' AND timecreated <= :to';
            $params['to'] = $to;
        }
        $rs = $DB->get_recordset_select(
            'logstore_standard_log',
            $where,
            $params,
            'timecreated ASC, id ASC',
            'id, courseid, contextlevel, contextinstanceid, timecreated'
        );
        try {
            return self::accumulate($rs, $idletimeout, $tailtime, $courseids);
        } finally {
            $rs->close();
        }
    }

    /**
     * Sum time over events ordered by timecreated.
     *
     * @param iterable $events Objects with courseid, contextlevel, contextinstanceid, timecreated.
     * @param int $idletimeout Largest gap, in seconds, counted in full.
     * @param int $tailtime Seconds credited for a gap over the timeout and for the last event.
     * @param int[]|null $courseids Only time of events in these courses is credited, null for all. Gaps are still
     *      measured across every event, so time spent in other courses is never attributed to these.
     * @return array ['site' => int, 'activities' => int, 'bycourse' => [courseid => seconds], 'events' => int]
     */
    public static function accumulate(iterable $events, int $idletimeout, int $tailtime, ?array $courseids = null): array {
        $result = ['site' => 0, 'activities' => 0, 'bycourse' => [], 'events' => 0];
        $allowed = $courseids === null ? null : array_flip(array_map('intval', $courseids));
        $previous = null;
        foreach ($events as $event) {
            if ($previous !== null && self::is_allowed($previous, $allowed)) {
                self::credit($result, $previous, $event->timecreated - $previous->timecreated, $idletimeout, $tailtime);
            }
            $previous = (object)[
                'courseid' => (int)$event->courseid,
                'contextlevel' => (int)$event->contextlevel,
                'timecreated' => (int)$event->timecreated,
            ];
            $result['events'] += self::is_allowed($previous, $allowed) ? 1 : 0;
        }
        if ($previous !== null && self::is_allowed($previous, $allowed)) {
            self::credit($result, $previous, null, $idletimeout, $tailtime);
        }
        return $result;
    }

    /**
     * Whether an event's course is one the caller may see.
     *
     * @param \stdClass $event Event with a courseid.
     * @param array|null $allowed Course ids as keys, null for all.
     * @return bool
     */
    private static function is_allowed(\stdClass $event, ?array $allowed): bool {
        return $allowed === null || isset($allowed[$event->courseid]);
    }

    /**
     * Add the time owed to one event.
     *
     * @param array $result Running result (modified).
     * @param \stdClass $event The earlier event.
     * @param int|null $gap Seconds to the next event, null for the last event.
     * @param int $idletimeout Idle timeout.
     * @param int $tailtime Tail time.
     */
    private static function credit(array &$result, \stdClass $event, ?int $gap, int $idletimeout, int $tailtime): void {
        $seconds = ($gap !== null && $gap <= $idletimeout) ? max(0, $gap) : $tailtime;
        $result['site'] += $seconds;
        if ($event->contextlevel === CONTEXT_MODULE) {
            $result['activities'] += $seconds;
        }
        if ($event->courseid > SITEID) {
            $result['bycourse'][$event->courseid] = ($result['bycourse'][$event->courseid] ?? 0) + $seconds;
        }
    }
}
