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
 * Admin settings and links for report_studentsummary.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Link to the report (own summary) under Site administration > Reports.
$ADMIN->add('reports', new admin_externalpage(
    'reportstudentsummarylink',
    get_string('pluginname', 'report_studentsummary'),
    new moodle_url('/report/studentsummary/index.php'),
    'report/studentsummary:viewown'
));

if ($ADMIN->fulltree && $settings) {
    $settings->add(new admin_setting_configduration(
        'report_studentsummary/idletimeout',
        get_string('idletimeout', 'report_studentsummary'),
        get_string('idletimeout_desc', 'report_studentsummary'),
        20 * MINSECS,
        MINSECS
    ));

    $settings->add(new admin_setting_configduration(
        'report_studentsummary/tailtime',
        get_string('tailtime', 'report_studentsummary'),
        get_string('tailtime_desc', 'report_studentsummary'),
        MINSECS,
        1
    ));

    $limit = \report_studentsummary\output\report_page::MAXCOURSES_LIMIT;
    $settings->add(new \report_studentsummary\admin\setting_maxcourses(
        'report_studentsummary/maxcourses',
        get_string('maxcourses', 'report_studentsummary'),
        get_string('maxcourses_desc', 'report_studentsummary', $limit),
        \report_studentsummary\output\report_page::DEFAULT_MAXCOURSES
    ));

    $settings->add(new admin_setting_configcheckbox(
        'report_studentsummary/showtime',
        get_string('showtime', 'report_studentsummary'),
        get_string('showtime_desc', 'report_studentsummary'),
        1
    ));

    // Colours: a palette, with optional per-box overrides.
    $swatches = '';
    foreach (\report_studentsummary\local\palette::presets() as $key => $preset) {
        $row = html_writer::span(get_string('palette_' . $key, 'report_studentsummary'), 'me-2');
        foreach ($preset['boxes'] as [$background, $border]) {
            $row .= html_writer::span('', 'd-inline-block me-1 align-middle', [
                'style' => "width:1.6em;height:1.1em;background:$background;border:2px solid $border;border-radius:3px;",
            ]);
        }
        $swatches .= html_writer::div($row, 'mb-1');
    }
    $settings->add(new admin_setting_heading(
        'report_studentsummary/coloursheading',
        get_string('colours', 'report_studentsummary'),
        get_string('colours_desc', 'report_studentsummary') . $swatches
    ));
    $settings->add(new admin_setting_configselect(
        'report_studentsummary/palette',
        get_string('palette', 'report_studentsummary'),
        get_string('palette_desc', 'report_studentsummary'),
        \report_studentsummary\local\palette::DEFAULT,
        \report_studentsummary\local\palette::choices()
    ));
    foreach (array_keys(\report_studentsummary\local\palette::BOXES) as $box) {
        $settings->add(new \report_studentsummary\admin\setting_boxcolour($box));
    }
}
