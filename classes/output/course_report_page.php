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
 * All student summaries of a course, one page of students at a time.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\output;

use report_studentsummary\local\access;

/**
 * Gathers one report per student of a course and exports them for the course template.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_report_page implements \renderable, \templatable {
    /** @var int[] Allowed numbers of students per page. */
    public const PERPAGE_OPTIONS = [10, 25, 50, 100];
    /** @var int Default number of students per page. */
    public const DEFAULT_PERPAGE = 25;

    /** @var \stdClass[] Every student the viewer may see, keyed by id. */
    private $students;
    /** @var int The viewing user's id. */
    private $viewerid;
    /** @var int|null Range start. */
    private $from;
    /** @var int|null Range end. */
    private $to;
    /** @var int Zero-based page number. */
    private $page;
    /** @var int Students per page. */
    private $perpage;
    /** @var \moodle_url Base url for the paging bar. */
    private $baseurl;
    /** @var int[]|null Courses each summary is limited to, null for all. */
    private $courseids;

    /**
     * Constructor.
     *
     * @param \stdClass $course The course (with groupmode).
     * @param int $viewerid The viewing user's id.
     * @param \moodle_url $baseurl Url of this page including the filters, used for paging links.
     * @param int $groupid Group to limit to, 0 for everyone.
     * @param int|null $from Optional range start timestamp.
     * @param int|null $to Optional range end timestamp.
     * @param int $page Zero-based page number.
     * @param int $perpage Students per page.
     * @param int[]|null $courseids Courses each summary is limited to, null for all.
     */
    public function __construct(
        \stdClass $course,
        int $viewerid,
        \moodle_url $baseurl,
        int $groupid = 0,
        ?int $from = null,
        ?int $to = null,
        int $page = 0,
        int $perpage = self::DEFAULT_PERPAGE,
        ?array $courseids = null
    ) {
        $this->students = access::get_course_students($course, $viewerid, $groupid);
        $this->viewerid = $viewerid;
        $this->from = $from;
        $this->to = $to;
        $this->perpage = in_array($perpage, self::PERPAGE_OPTIONS, true) ? $perpage : self::DEFAULT_PERPAGE;
        $this->page = max(0, min($page, (int)ceil(count($this->students) / $this->perpage) - 1));
        $this->baseurl = $baseurl;
        $this->courseids = $courseids;
    }

    /**
     * Number of students across all pages.
     *
     * @return int
     */
    public function get_count(): int {
        return count($this->students);
    }

    /**
     * The students shown on the current page.
     *
     * @return \stdClass[]
     */
    public function get_page_students(): array {
        return array_slice($this->students, $this->page * $this->perpage, $this->perpage, true);
    }

    /**
     * The courses to choose from: every course the viewer may see for a student on the current page.
     *
     * @return string[] Course names keyed by course id, sorted by name.
     */
    public function get_course_choices(): array {
        $choices = [];
        foreach ($this->get_page_students() as $user) {
            foreach (access::get_courses($user, $this->viewerid) as $course) {
                $choices[$course->id] ??= format_string($course->fullname, true, [
                    'context' => \context_course::instance($course->id),
                ]);
            }
        }
        \core_collator::asort($choices);
        return $choices;
    }

    /**
     * The selection toolbar as a React component, or an empty string where core cannot render it.
     *
     * Moodle 5.3 and later provide React, the design system and a build for plugin components. Elsewhere, or until
     * js/esm has been built, the template falls back to its plain Bootstrap toolbar.
     *
     * @return string HTML of the mount point.
     */
    private static function react_toolbar(): string {
        if (
            !interface_exists(\core\output\react_component_renderable::class) ||
            !file_exists(__DIR__ . '/../../js/esm/build/Toolbar.js')
        ) {
            return '';
        }
        return \html_writer::react_component('@moodle/lms/report_studentsummary/Toolbar', [
            'selectalllabel' => get_string('selectall', 'report_studentsummary'),
            'printlabel' => get_string('printselected', 'report_studentsummary'),
        ]);
    }

    /**
     * Export data for the course template.
     *
     * @param \renderer_base $output The renderer.
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $data = new \stdClass();
        $data->count = $this->get_count();
        $data->students = [];
        foreach ($this->get_page_students() as $user) {
            $report = new report_page($user, $this->viewerid, $this->from, $this->to, 'u' . $user->id . '-', $this->courseids);
            $data->students[] = [
                'id' => (int)$user->id,
                'fullname' => fullname($user),
                'selectlabel' => get_string('selectstudent', 'report_studentsummary', fullname($user)),
                'summary' => $report->export_for_template($output),
            ];
        }
        $data->hasstudents = !empty($data->students);
        $data->reacttoolbar = $data->hasstudents ? self::react_toolbar() : '';
        $data->pagingbar = $data->count > $this->perpage ?
            $output->render(new \paging_bar($data->count, $this->page, $this->perpage, $this->baseurl)) : '';
        return $data;
    }
}
