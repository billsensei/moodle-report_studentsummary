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
 * Student summaries of every student in a course, to select and print one per A4 page.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/grouplib.php');

use report_studentsummary\output\course_report_page;

$courseid = required_param('id', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', course_report_page::DEFAULT_PERPAGE, PARAM_INT);
$from = optional_param('fromts', 0, PARAM_INT);
$to = optional_param('tots', 0, PARAM_INT);

$course = get_course($courseid);
if ((int)$course->id === SITEID) {
    throw new moodle_exception('invalidcourseid');
}
require_login($course);
$context = context_course::instance($course->id);
require_capability('report/studentsummary:viewall', $context);

$params = ['id' => $course->id];
$PAGE->set_context($context);
$PAGE->set_url('/report/studentsummary/course.php', $params);
$PAGE->set_pagelayout('report');
$PAGE->add_body_class('report-studentsummary-page');
$coursename = format_string($course->shortname, true, ['context' => $context]);
$title = get_string('studentsummariesfor', 'report_studentsummary', $coursename);
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));

// The group menu redirects on change, so it is resolved before anything is printed.
$groupid = groups_get_course_group($course, true);

// The filter is posted (so the session key never appears in the address bar, history or a printed footer),
// then redirected to a clean address that carries only the filters.
$mform = new \report_studentsummary\form\filter_form(
    $PAGE->url,
    ['id' => $course->id, 'group' => $groupid, 'perpage' => $perpage]
);
$baseurl = new moodle_url($PAGE->url, ['perpage' => $perpage]);
if ($groupid) {
    $baseurl->param('group', $groupid);
}
if ($formdata = $mform->get_data()) {
    redirect(new moodle_url($baseurl, \report_studentsummary\form\filter_form::range_params($formdata)));
}
if ($from || $to) {
    $mform->set_data(\report_studentsummary\form\filter_form::range_defaults($from, $to));
    $baseurl->params(['fromts' => $from ?: null, 'tots' => $to ?: null]);
}

$report = new course_report_page($course, (int)$USER->id, $baseurl, $groupid, $from ?: null, $to ?: null, $page, $perpage);

\report_studentsummary\event\course_report_viewed::create(['context' => $context])->trigger();

echo $OUTPUT->header();
echo $OUTPUT->heading($title, 3, 'ss-noprint');
echo html_writer::start_div('ss-noprint');
groups_print_course_menu($course, $PAGE->url);
echo $mform->render();
echo html_writer::end_div();
echo $PAGE->get_renderer('report_studentsummary')->render($report);
echo $OUTPUT->footer();
