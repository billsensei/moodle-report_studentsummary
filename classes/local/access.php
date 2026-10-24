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
 * Access rules for the student summary report.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

/**
 * Decides who may view which student's report, and which courses are included.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access {
    /**
     * Whether the viewer may open the report of the user.
     *
     * @param \stdClass $user Target user.
     * @param int|null $viewerid Viewer, null for the current user.
     * @return bool
     */
    public static function can_view_user(\stdClass $user, ?int $viewerid = null): bool {
        global $USER;

        $viewerid ??= (int)$USER->id;
        if (!$viewerid || isguestuser($viewerid) || $user->deleted) {
            return false;
        }
        if ((int)$user->id === $viewerid) {
            return has_capability('report/studentsummary:viewown', \context_system::instance(), $viewerid);
        }
        if (has_capability('report/studentsummary:viewall', \context_user::instance($user->id), $viewerid)) {
            return true;
        }
        return !empty(self::get_courses($user, $viewerid));
    }

    /**
     * Courses of the user that the viewer may see in the report.
     *
     * Excludes courses hidden from the viewer, courses where the viewer lacks
     * viewall (unless it is their own report) and, under separate groups,
     * courses where the viewer shares no group with the user.
     *
     * @param \stdClass $user Target user.
     * @param int|null $viewerid Viewer, null for the current user.
     * @return \stdClass[] Course records keyed by id.
     */
    public static function get_courses(\stdClass $user, ?int $viewerid = null): array {
        global $USER;

        $viewerid ??= (int)$USER->id;
        $own = (int)$user->id === $viewerid;
        // The core helper filters hidden courses by the student's permissions, so apply the viewer's rules below.
        $fields = 'id, fullname, shortname, visible, enablecompletion, groupmode, showgrades';
        $courses = enrol_get_all_users_courses($user->id, true, $fields);
        $allowed = [];
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $context, $viewerid)) {
                continue;
            }
            if (!$own && !self::can_view_in_course($course, $user, $viewerid)) {
                continue;
            }
            $allowed[$course->id] = $course;
        }
        return $allowed;
    }

    /**
     * Whether the viewer may see the user's grades in the course.
     *
     * Mirrors the user grade report: moodle/grade:viewall for other users; for
     * your own grades, moodle/grade:view and the course "Show gradebook to students" setting.
     *
     * @param \stdClass $course Course record (showgrades is read from the database if missing).
     * @param \stdClass $user The student.
     * @param int|null $viewerid Viewer, null for the current user.
     * @return bool
     */
    public static function can_view_grades(\stdClass $course, \stdClass $user, ?int $viewerid = null): bool {
        global $USER;

        $viewerid ??= (int)$USER->id;
        $context = \context_course::instance($course->id);
        if (has_capability('moodle/grade:viewall', $context, $viewerid)) {
            return true;
        }
        if ((int)$user->id !== $viewerid || !has_capability('moodle/grade:view', $context, $viewerid)) {
            return false;
        }
        $showgrades = $course->showgrades ?? get_course($course->id)->showgrades;
        return !empty($showgrades);
    }

    /**
     * Whether the viewer may see site-level activity (events outside any course) of the user.
     *
     * Allowed for your own report and for viewers holding viewall on the user or the whole site,
     * but not for a teacher whose viewall applies only inside a course.
     *
     * @param \stdClass $user The student.
     * @param int|null $viewerid Viewer, null for the current user.
     * @return bool
     */
    public static function can_view_site_activity(\stdClass $user, ?int $viewerid = null): bool {
        global $USER;

        $viewerid ??= (int)$USER->id;
        return (int)$user->id === $viewerid ||
            has_capability('report/studentsummary:viewall', \context_user::instance($user->id), $viewerid);
    }

    /**
     * Students of a course whose reports the viewer may open.
     *
     * Active students only (those holding moodle/grade:view, the gradebook student role), optionally limited to
     * one group. Each one must also pass the same per-course rules as a single report, which covers separate groups,
     * suspended accounts and the viewer's own course access.
     *
     * @param \stdClass $course Course record including groupmode.
     * @param int|null $viewerid Viewer, null for the current user.
     * @param int $groupid Group to limit to, 0 for everyone.
     * @return \stdClass[] User records keyed by id, sorted by last then first name.
     */
    public static function get_course_students(\stdClass $course, ?int $viewerid = null, int $groupid = 0): array {
        global $USER;

        $viewerid ??= (int)$USER->id;
        $context = \context_course::instance($course->id);
        if (
            (int)$course->id === SITEID || !$viewerid || isguestuser($viewerid) ||
            !has_capability('report/studentsummary:viewall', $context, $viewerid)
        ) {
            return [];
        }
        // Only the columns the report needs (name, picture, flags), not the whole user row.
        $fields = \core_user\fields::for_name()->with_userpic()->including('suspended', 'deleted')->get_required_fields();
        $users = get_enrolled_users(
            $context,
            'moodle/grade:view',
            $groupid,
            implode(',', array_map(fn($field) => 'u.' . $field, array_unique($fields))),
            'u.lastname, u.firstname, u.id',
            0,
            0,
            true
        );
        $allowed = array_filter(
            $users,
            fn($user) => !$user->deleted && !$user->suspended && self::can_view_in_course($course, $user, $viewerid)
        );
        return $allowed;
    }

    /**
     * Whether the viewer may see the user in the course, honouring group mode.
     *
     * @param \stdClass $course Course record including groupmode.
     * @param \stdClass $user Target user.
     * @param int $viewerid Viewer.
     * @return bool
     */
    private static function can_view_in_course(\stdClass $course, \stdClass $user, int $viewerid): bool {
        $context = \context_course::instance($course->id);
        if (!has_capability('report/studentsummary:viewall', $context, $viewerid)) {
            return false;
        }
        // A role assignment survives a suspended or expired enrolment, so check course access too.
        if (!can_access_course($course, $viewerid, '', true)) {
            return false;
        }
        $groupmode = groups_get_course_groupmode($course);
        if ($groupmode != SEPARATEGROUPS || has_capability('moodle/site:accessallgroups', $context, $viewerid)) {
            return true;
        }
        $viewergroups = array_keys(groups_get_all_groups($course->id, $viewerid, 0, 'g.id'));
        $usergroups = array_keys(groups_get_all_groups($course->id, $user->id, 0, 'g.id'));
        return !empty(array_intersect($viewergroups, $usergroups));
    }
}
