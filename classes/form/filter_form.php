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
 * Optional start and end date for the time-spent estimate (read-only GET form).
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_form extends \moodleform {
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
        $mform->addElement('date_selector', 'from', get_string('datefrom', 'report_studentsummary'), ['optional' => true]);
        $mform->addElement('date_selector', 'to', get_string('dateto', 'report_studentsummary'), ['optional' => true]);
        $this->add_action_buttons(false, get_string('applyfilter', 'report_studentsummary'));
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
