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
 * Student summary report entry page.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$userid = optional_param('userid', 0, PARAM_INT);
$from = optional_param('fromts', 0, PARAM_INT);
$to = optional_param('tots', 0, PARAM_INT);

require_login(null, false);

$userid = $userid ?: $USER->id;
$isown = (int)$userid === (int)$USER->id;
$user = core_user::get_user($userid);
// One generic failure for a missing, deleted, suspended or unauthorised user, so ids cannot be probed.
if (
    !$user || $user->deleted || isguestuser($user) || ($user->suspended && !$isown) ||
    !\report_studentsummary\local\access::can_view_user($user)
) {
    throw new required_capability_exception(
        context_system::instance(),
        $isown ? 'report/studentsummary:viewown' : 'report/studentsummary:viewall',
        'nopermissions',
        ''
    );
}

$PAGE->set_context(context_user::instance($user->id));
$PAGE->set_url('/report/studentsummary/index.php', ['userid' => $user->id]);
$PAGE->set_pagelayout('report');
$PAGE->add_body_class('report-studentsummary-page');
$title = get_string('studentsummaryfor', 'report_studentsummary', fullname($user));
$PAGE->set_title($title);
$PAGE->set_heading($title);

// The filter is posted (so the session key never appears in the address bar, history or a printed footer),
// then redirected to a clean address that carries only the dates.
$mform = new \report_studentsummary\form\filter_form($PAGE->url, ['userid' => $user->id]);
if ($formdata = $mform->get_data()) {
    redirect(new moodle_url($PAGE->url, \report_studentsummary\form\filter_form::range_params($formdata)));
}
if ($from || $to) {
    $mform->set_data(\report_studentsummary\form\filter_form::range_defaults($from, $to));
}

\report_studentsummary\event\report_viewed::create([
    'context' => context_user::instance($user->id),
    'relateduserid' => $user->id,
])->trigger();

$page = new \report_studentsummary\output\report_page($user, (int)$USER->id, $from ?: null, $to ?: null);

echo $OUTPUT->header();
echo html_writer::div($mform->render(), 'ss-noprint');
echo $PAGE->get_renderer('report_studentsummary')->render($page);
echo $OUTPUT->footer();
