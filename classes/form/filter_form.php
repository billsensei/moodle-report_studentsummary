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
 * Date range filter form.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Optional courses and start and end date for the report (posted, then redirected to a clean address).
 *
 * Custom data: `courses` (id => name) offers a menu of courses on a single student's report; `courselist`
 * (id => name) offers a tick box per course on the course page.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_form extends \moodleform {
    /** @var string Name prefix of the course list tick boxes; the course id follows. */
    private const BOXPREFIX = 'course_';

    /**
     * Build the form.
     */
    protected function definition(): void {
        $mform = $this->_form;
        // Parameters that must survive a filter submission (the form is a plain GET form).
        foreach (['userid', 'id', 'group', 'perpage'] as $name) {
            if (isset($this->_customdata[$name])) {
                $mform->addElement('hidden', $name, $this->_customdata[$name]);
                $mform->setType($name, PARAM_INT);
            }
        }
        if (!empty($this->_customdata['courses']) && count($this->_customdata['courses']) > 1) {
            $mform->addElement(
                'autocomplete',
                'courseids',
                get_string('coursestoinclude', 'report_studentsummary'),
                $this->_customdata['courses'],
                ['multiple' => true, 'noselectionstring' => get_string('allcourses', 'report_studentsummary')]
            );
            $mform->setType('courseids', PARAM_INT);
        }
        if (!empty($this->_customdata['courselist'])) {
            $boxes = [];
            foreach ($this->_customdata['courselist'] as $id => $name) {
                $boxes[] = $mform->createElement('advcheckbox', self::BOXPREFIX . $id, '', $name);
            }
            $mform->addGroup($boxes, 'courseboxes', get_string('coursestoinclude', 'report_studentsummary'), '<br>', false);
            $mform->addElement('static', 'coursesnote', '', get_string('coursestoinclude_note', 'report_studentsummary'));
        }
        $mform->addElement('date_selector', 'from', get_string('datefrom', 'report_studentsummary'), ['optional' => true]);
        $mform->addElement('date_selector', 'to', get_string('dateto', 'report_studentsummary'), ['optional' => true]);
        $this->add_action_buttons(false, get_string('applyfilter', 'report_studentsummary'));
    }

    /**
     * The address parameters for a submitted range (the end date covers the whole day).
     *
     * @param \stdClass $formdata Submitted data.
     * @return array fromts and tots, omitted when empty.
     */
    public static function range_params(\stdClass $formdata): array {
        return [
            'fromts' => empty($formdata->from) ? null : (int)$formdata->from,
            'tots' => empty($formdata->to) ? null : (int)$formdata->to + DAYSECS - 1,
        ];
    }

    /**
     * The address parameter for the submitted course choice.
     *
     * Takes the courses picked in the menu (courseids) and the ticked boxes of the course list (course_N).
     *
     * @param \stdClass $formdata Submitted data.
     * @return array {@see self::courseids_url_params()}, or nothing when every course is wanted (the names are left
     *     out, as an empty value would reach the address as a bare name that is not an array).
     */
    public static function courses_params(\stdClass $formdata): array {
        $ids = array_map('intval', $formdata->courseids ?? []);
        foreach ($formdata as $name => $value) {
            if ($value && preg_match('/^' . self::BOXPREFIX . '(\d+)$/', $name, $match)) {
                $ids[] = (int)$match[1];
            }
        }
        return self::courseids_url_params(array_unique(array_filter($ids)));
    }

    /**
     * Address parameters for a list of course ids.
     *
     * Moodle 4.5's moodle_url refuses array values, so each id gets its own name (courseids[0], courseids[1], ...),
     * which PHP reads back as the array courseids.
     *
     * @param int[] $ids Course ids.
     * @return array Parameter name => id, empty for no courses.
     */
    public static function courseids_url_params(array $ids): array {
        $params = [];
        foreach (array_values($ids) as $index => $id) {
            $params['courseids[' . $index . ']'] = (int)$id;
        }
        return $params;
    }

    /**
     * Form values for a range taken from the address.
     *
     * @param int $from Start timestamp, 0 for none.
     * @param int $to End timestamp (end of the last day), 0 for none.
     * @return array
     */
    public static function range_defaults(int $from, int $to): array {
        return ['from' => $from, 'to' => $to ? $to - DAYSECS + 1 : 0];
    }

    /**
     * Form values for the course choice taken from the address.
     *
     * @param int[] $courseids Chosen course ids, empty for all.
     * @return array The menu value and a ticked box per chosen course.
     */
    public static function courses_defaults(array $courseids): array {
        $defaults = ['courseids' => $courseids];
        foreach ($courseids as $id) {
            $defaults[self::BOXPREFIX . $id] = 1;
        }
        return $defaults;
    }

    /**
     * The end date must not be before the start date.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors keyed by element.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!empty($data['from']) && !empty($data['to']) && $data['to'] < $data['from']) {
            $errors['to'] = get_string('rangeinvalid', 'report_studentsummary');
        }
        return $errors;
    }
}
