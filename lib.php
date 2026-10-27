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
 * Callbacks for report_studentsummary.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add a "Student summary" link to the Reports category of the user profile.
 *
 * @param \core_user\output\myprofile\tree $tree Profile tree.
 * @param stdClass $user The user whose profile is shown.
 * @param bool $iscurrentuser Whether the profile belongs to the viewer.
 * @param stdClass|null $course Course being viewed, if any.
 * @return bool
 */
function report_studentsummary_myprofile_navigation(
    \core_user\output\myprofile\tree $tree,
    $user,
    $iscurrentuser,
    $course
): bool {
    unset($iscurrentuser, $course); // Part of the fixed callback signature.
    if (!report_studentsummary_can_view($user)) {
        return false;
    }
    $url = new moodle_url('/report/studentsummary/index.php', ['userid' => $user->id]);
    $tree->add_node(new \core_user\output\myprofile\node(
        'reports',
        'studentsummary',
        get_string('pluginname', 'report_studentsummary'),
        null,
        $url
    ));
    return true;
}

/**
 * Whether the current user may view the report of the given user.
 *
 * @param stdClass $user Target user.
 * @return bool
 */
function report_studentsummary_can_view(stdClass $user): bool {
    return \report_studentsummary\local\access::can_view_user($user);
}

/**
 * A "Plugin settings" link for administrators, or an empty string for everyone else.
 *
 * Core does not show a Settings link for report plugins on the Plugins overview page, so the report offers one.
 *
 * @return string HTML, hidden when printing.
 */
function report_studentsummary_settings_link(): string {
    if (!has_capability('moodle/site:config', context_system::instance())) {
        return '';
    }
    $url = new moodle_url('/admin/settings.php', ['section' => 'reportstudentsummary']);
    return html_writer::div(
        html_writer::link($url, get_string('settingslink', 'report_studentsummary')),
        'ss-noprint text-end mb-2'
    );
}

/**
 * Add a "Student summaries" link to the course Reports page for teachers.
 *
 * @param navigation_node $navigation The Reports navigation node of the course.
 * @param stdClass $course The course.
 * @param context $context The course context.
 */
function report_studentsummary_extend_navigation_course($navigation, $course, $context): void {
    if ((int)$course->id === SITEID || !has_capability('report/studentsummary:viewall', $context)) {
        return;
    }
    $navigation->add(
        get_string('coursesummaries', 'report_studentsummary'),
        new moodle_url('/report/studentsummary/course.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/report', '')
    );
}
