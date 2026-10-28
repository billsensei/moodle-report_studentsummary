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
 * Language strings for report_studentsummary.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitiescompleted'] = 'Activities completed';
$string['activitytypes'] = 'Activity types';
$string['allcourses'] = 'All courses';
$string['alltime'] = 'All time';
$string['applyfilter'] = 'Apply';
$string['averagegrade'] = 'Average grade';
$string['cachedef_timesummary'] = 'Estimated time spent for a report viewed in the current session';
$string['colour_activities'] = 'Activities completed box colour';
$string['colour_courses'] = 'Courses enrolled box colour';
$string['colour_desc'] = 'Background colour of this box, as #rrggbb. Leave empty to use the palette. The border is derived from it, and the text on it must keep a contrast ratio of at least {$a}:1.';
$string['colour_grade'] = 'Average grade box colour';
$string['colour_grades'] = 'Current grades box colour';
$string['colour_header'] = 'Header box colour';
$string['colour_resources'] = 'Resources completed box colour';
$string['colour_time'] = 'Time spent box colour';
$string['colourinvalid'] = 'Enter a colour as #rrggbb (for example #dbeafe), or leave the field empty.';
$string['colourlowcontrast'] = 'This colour is too dark for the text on it (contrast {$a->ratio}:1; at least {$a->min}:1 is required). Choose a lighter colour.';
$string['colours'] = 'Report colours';
$string['colours_desc'] = 'Choose a ready-made palette, then optionally override the background of individual boxes. Colours are checked so that text stays readable (WCAG AA), and the printed page keeps them. Palettes:';
$string['completed'] = 'completed';
$string['coursesenrolled'] = 'Courses enrolled';
$string['coursestoinclude'] = 'Courses to include';
$string['coursestoinclude_note'] = 'Tick the courses to include in every summary. Leave all unticked to include every course.';
$string['coursesummaries'] = 'Student summaries (all students)';
$string['currentgrades'] = 'Current grades';
$string['datefrom'] = 'From';
$string['daterange'] = 'Date range';
$string['dateto'] = 'To';
$string['durationhm'] = '{$a->h}h {$a->m}m';
$string['eventcoursereportviewed'] = 'Student summary reports of a course viewed';
$string['eventreportviewed'] = 'Student summary report viewed';
$string['grade'] = 'Grade';
$string['gradeditems'] = 'Based on {$a} graded items';
$string['hiddengrade'] = 'Hidden';
$string['idletimeout'] = 'Idle timeout';
$string['idletimeout_desc'] = 'Largest gap between two log entries that still counts as time spent. Longer gaps count as the tail time only.';
$string['logsunavailable'] = 'Time spent is unavailable because the standard log store is not enabled.';
$string['maxcourses'] = 'Maximum courses listed';
$string['maxcourses_desc'] = 'Courses beyond this number are summarised as "+N more" so the report stays on one printed page. Between 1 and {$a}.';
$string['maxcoursesinvalid'] = 'Enter a whole number from 1 to {$a}.';
$string['morecourses'] = '+{$a} more courses not shown';
$string['na'] = 'n/a';
$string['nocompletion'] = 'Activity completion is not enabled in any of these courses, so nothing can be counted.';
$string['nocourses'] = 'No courses to show.';
$string['nonecompleted'] = 'None completed';
$string['nostudents'] = 'There are no students to show in this course or group.';
$string['notgraded'] = 'Not graded';
$string['palette'] = 'Colour palette';
$string['palette_default'] = 'Default (soft pastels)';
$string['palette_desc'] = 'Ready-made colour set used by every report. Individual boxes can be overridden below.';
$string['palette_earth'] = 'Earth tones';
$string['palette_greyscale'] = 'Greyscale (for black-and-white printing)';
$string['palette_highcontrast'] = 'High contrast';
$string['pluginname'] = 'Student summary';
$string['printreport'] = 'Print';
$string['printselected'] = 'Print selected';
$string['printthisone'] = 'Print this one';
$string['privacy:metadata'] = 'The Student summary report only displays existing data and does not store any personal data of its own. Each view of a report is recorded by Moodle\'s standard log (see the log store privacy information). A short-lived (10 minute) copy of the estimated time totals for the viewed student is kept in the viewer\'s own session cache and is discarded when the session ends.';
$string['progress'] = 'Completion progress';
$string['rangebetween'] = '{$a->from} to {$a->to}';
$string['rangefrom'] = 'From {$a}';
$string['rangeinvalid'] = 'The end date cannot be before the start date.';
$string['rangeto'] = 'Until {$a}';
$string['reportdate'] = 'Report date';
$string['resourcescompleted'] = 'Resources completed';
$string['resourcetypes'] = 'Resource types';
$string['selectall'] = 'Select all';
$string['selectedcount'] = '{$a->selected} of {$a->total} selected';
$string['selectstudent'] = 'Print {$a}';
$string['settingslink'] = 'Plugin settings';
$string['showtime'] = 'Show time spent';
$string['showtime_desc'] = 'Display the estimated time spent. The estimate is based on the standard log store.';
$string['studentsummariesfor'] = 'Student summaries: {$a}';
$string['studentsummary:viewall'] = 'View student summary reports of other users';
$string['studentsummary:viewown'] = 'View own student summary report';
$string['studentsummaryfor'] = 'Student summary for {$a}';
$string['tailtime'] = 'Tail time';
$string['tailtime_desc'] = 'Time credited for the last action of a visit, and for an action followed by a gap longer than the idle timeout.';
$string['timedisabled'] = 'Time spent display is disabled by the administrator.';
$string['timeestimated'] = 'Estimated from activity logs';
$string['timeinactivities'] = 'In activities and resources';
$string['timeonsite'] = 'on site';
$string['timespent'] = 'Time spent';
