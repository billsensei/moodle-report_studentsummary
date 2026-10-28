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
 * Renderable for the student summary page.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\output;

use report_studentsummary\local\access;
use report_studentsummary\local\completion_summary;
use report_studentsummary\local\course_grades;
use report_studentsummary\local\grade_summary;
use report_studentsummary\local\palette;
use report_studentsummary\local\time_estimator;

/**
 * Gathers the report data and exports it for the Mustache templates.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_page implements \renderable, \templatable {
    /** @var int Default number of courses listed. */
    public const DEFAULT_MAXCOURSES = 12;
    /** @var int Largest number of courses listed, whatever the setting says. */
    public const MAXCOURSES_LIMIT = 50;

    /** @var \stdClass The student. */
    private $user;
    /** @var int The viewing user's id. */
    private $viewerid;
    /** @var int|null Range start. */
    private $from;
    /** @var int|null Range end. */
    private $to;
    /** @var \stdClass[] Courses the viewer may see for this student (set when exported). */
    private $courses = [];
    /** @var string Prefix that keeps element ids unique when several reports share a page. */
    private $idprefix;
    /** @var int[]|null Courses to include, null for every course the viewer may see. */
    private $courseids;

    /**
     * Constructor.
     *
     * @param \stdClass $user The student.
     * @param int $viewerid The viewing user's id.
     * @param int|null $from Optional range start timestamp.
     * @param int|null $to Optional range end timestamp.
     * @param string $idprefix Prefix for element ids, needed when several reports are on one page.
     * @param int[]|null $courseids Courses to include, null or empty for all. Courses the viewer may not see stay out.
     */
    public function __construct(
        \stdClass $user,
        int $viewerid,
        ?int $from = null,
        ?int $to = null,
        string $idprefix = '',
        ?array $courseids = null
    ) {
        $this->user = $user;
        $this->viewerid = $viewerid;
        $this->from = $from;
        $this->to = $to;
        $this->idprefix = preg_replace('/[^a-z0-9-]/i', '', $idprefix);
        $this->courseids = $courseids ? array_map('intval', $courseids) : null;
    }

    /**
     * Export data for the report template.
     *
     * @param \renderer_base $output The renderer.
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        global $PAGE, $SITE;

        unset($output); // Part of the templatable signature.
        $courses = access::get_courses($this->user, $this->viewerid);
        if ($this->courseids !== null) {
            // Only narrows the list: a course the viewer may not see is already missing from it.
            $courses = array_intersect_key($courses, array_flip($this->courseids));
        }
        $this->courses = $courses;
        $completion = completion_summary::get_summary($this->user->id, $courses);
        $grades = grade_summary::get_summary($this->user->id, $courses, $this->viewerid);
        $picture = new \user_picture($this->user);
        $picture->size = 100;

        $data = new \stdClass();
        $data->idprefix = $this->idprefix;
        $data->fullname = fullname($this->user);
        $data->pictureurl = $picture->get_url($PAGE)->out(false);
        $data->sitename = format_string($SITE->fullname, true, ['context' => \context_system::instance()]);
        $data->reportdate = userdate(time(), get_string('strftimedatefullshort', 'langconfig'));
        $data->hasrange = $this->from !== null || $this->to !== null;
        $data->range = $this->describe_range();
        $data->colourstyle = palette::style();
        $nocompletion = array_filter($courses, fn($course) => $course->enablecompletion) ?
            '' : get_string('nocompletion', 'report_studentsummary');
        $data->kpis = [
            $this->box(
                'blue',
                'activities',
                'activitiescompleted',
                (string)$completion['activities']['total'],
                'completed',
                [],
                $nocompletion
            ),
            $this->box(
                'green',
                'resources',
                'resourcescompleted',
                (string)$completion['resources']['total'],
                'completed',
                [],
                $nocompletion
            ),
            $this->grade_box($grades),
            $this->time_box(),
        ];
        $data->breakdowns = [
            array_merge(
                $this->box('blue', 'activitytypes', 'activitytypes', null, '', $this->types($completion['activities'])),
                ['wide' => true]
            ),
            array_merge(
                $this->box('green', 'resourcetypes', 'resourcetypes', null, '', $this->types($completion['resources'])),
                ['wide' => true]
            ),
        ];
        $this->export_courses($data, $courses);
        return $data;
    }

    /**
     * Build a box context for the box template.
     *
     * @param string $colour Colour name.
     * @param string $id Unique id fragment.
     * @param string $headingstring Lang string identifier of the heading.
     * @param string|null $figure Key figure, null for none.
     * @param string $figurelabel Lang string identifier for the figure label, empty for none.
     * @param array $items Breakdown rows.
     * @param string $note Optional note.
     * @return array
     */
    private function box(
        string $colour,
        string $id,
        string $headingstring,
        ?string $figure,
        string $figurelabel,
        array $items = [],
        string $note = ''
    ): array {
        return [
            'boxclass' => 'ss-box-' . $colour,
            'boxid' => 'ss-' . $this->idprefix . $id,
            'heading' => get_string($headingstring, 'report_studentsummary'),
            'hasfigure' => $figure !== null,
            'figure' => $figure,
            'figurelabel' => $figurelabel === '' ? '' : get_string($figurelabel, 'report_studentsummary'),
            'hasitems' => !empty($items),
            'items' => $items,
            'wide' => false,
            'note' => $note,
        ];
    }

    /**
     * Breakdown rows for a completion group.
     *
     * @param array $group Group from {@see completion_summary::get_summary()}.
     * @return array
     */
    private function types(array $group): array {
        $items = [];
        foreach ($group['bytype'] as $modname => $count) {
            $items[] = ['label' => get_string('modulenameplural', $modname), 'value' => $count];
        }
        return $items ?: [['label' => get_string('nonecompleted', 'report_studentsummary'), 'value' => 0]];
    }

    /**
     * Average grade box.
     *
     * @param array $grades Result of {@see grade_summary::get_summary()}.
     * @return array
     */
    private function grade_box(array $grades): array {
        $figure = $grades['average'] === null ? get_string('na', 'report_studentsummary') :
            format_float($grades['average'], 1) . '%';
        $note = get_string('gradeditems', 'report_studentsummary', $grades['count']);
        return $this->box('amber', 'averagegrade', 'averagegrade', $figure, '', [], $note);
    }

    /**
     * Estimated time box.
     *
     * @return array
     */
    private function time_box(): array {
        if (get_config('report_studentsummary', 'showtime') === '0') {
            return $this->box(
                'purple',
                'timespent',
                'timespent',
                get_string('na', 'report_studentsummary'),
                '',
                [],
                get_string('timedisabled', 'report_studentsummary')
            );
        }
        $summary = time_estimator::get_summary(
            $this->user->id,
            $this->from,
            $this->to,
            null,
            null,
            $this->time_courseids()
        );
        if ($summary === null) {
            return $this->box(
                'purple',
                'timespent',
                'timespent',
                get_string('na', 'report_studentsummary'),
                '',
                [],
                get_string('logsunavailable', 'report_studentsummary')
            );
        }
        $items = [['label' => get_string('timeinactivities', 'report_studentsummary'),
            'value' => self::format_duration($summary['activities'])]];
        return $this->box(
            'purple',
            'timespent',
            'timespent',
            self::format_duration($summary['site']),
            'timeonsite',
            $items,
            get_string('timeestimated', 'report_studentsummary')
        );
    }

    /**
     * Add the course progress and current grade rows.
     *
     * @param \stdClass $data Template data (modified).
     * @param \stdClass[] $courses Visible courses.
     */
    private function export_courses(\stdClass $data, array $courses): void {
        $configured = (int)(get_config('report_studentsummary', 'maxcourses') ?: self::DEFAULT_MAXCOURSES);
        $max = min(self::MAXCOURSES_LIMIT, max(1, $configured));
        $data->coursecount = count($courses);
        $shown = array_slice($courses, 0, $max, true);
        $data->hasmorecourses = count($courses) > $max;
        $data->morecourses = count($courses) - count($shown);

        $gradeinfo = [];
        foreach (course_grades::get_rows($this->user->id, $shown, $this->viewerid) as $row) {
            $gradeinfo[$row['courseid']] = $row;
        }
        $data->courses = [];
        $data->grades = [];
        foreach ($shown as $course) {
            $name = format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]);
            $progress = $course->enablecompletion ?
                \core_completion\progress::get_course_progress_percentage($course, $this->user->id) : null;
            $data->courses[] = [
                'name' => $name,
                'hasprogress' => $progress !== null,
                'progress' => $progress === null ? 0 : (int)round($progress),
            ];
            $row = $gradeinfo[$course->id];
            $data->grades[] = [
                'name' => $name,
                'graded' => $row['status'] === course_grades::STATUS_GRADED,
                'hidden' => $row['status'] === course_grades::STATUS_HIDDEN,
                'notgraded' => $row['status'] === course_grades::STATUS_NOTGRADED,
                'display' => $row['display'],
                'haspercentage' => $row['percentage'] !== null,
                'percentage' => $row['percentage'] === null ? 0 : (int)round($row['percentage']),
            ];
        }
        $data->hascourses = !empty($data->courses);
    }

    /**
     * Course ids whose logged time the viewer may see.
     *
     * @return int[]
     */
    private function time_courseids(): array {
        $ids = array_map('intval', array_keys($this->courses));
        // Time outside any course does not belong in a summary of chosen courses.
        if ($this->courseids === null && access::can_view_site_activity($this->user, $this->viewerid)) {
            $ids[] = SITEID;
        }
        return $ids;
    }

    /**
     * Describe the date range, or an empty string.
     *
     * @return string
     */
    private function describe_range(): string {
        $format = get_string('strftimedatefullshort', 'langconfig');
        if ($this->from !== null && $this->to !== null) {
            return get_string(
                'rangebetween',
                'report_studentsummary',
                ['from' => userdate($this->from, $format), 'to' => userdate($this->to, $format)]
            );
        }
        if ($this->from !== null) {
            return get_string('rangefrom', 'report_studentsummary', userdate($this->from, $format));
        }
        if ($this->to !== null) {
            return get_string('rangeto', 'report_studentsummary', userdate($this->to, $format));
        }
        return get_string('alltime', 'report_studentsummary');
    }

    /**
     * Format seconds as "Hh Mm".
     *
     * @param int $seconds Duration.
     * @return string
     */
    public static function format_duration(int $seconds): string {
        $minutes = intdiv($seconds, 60);
        return get_string('durationhm', 'report_studentsummary', ['h' => intdiv($minutes, 60), 'm' => $minutes % 60]);
    }
}
