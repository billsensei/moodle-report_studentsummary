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
 * The "maximum courses listed" admin setting.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\admin;

use report_studentsummary\output\report_page;

/**
 * A whole number of courses between 1 and {@see report_page::MAXCOURSES_LIMIT}.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_maxcourses extends \admin_setting_configtext {
    /**
     * Constructor.
     *
     * @param string $name Unique setting name, such as component/name.
     * @param string $visiblename Heading.
     * @param string $description Description shown under the field.
     * @param int $defaultsetting Default value.
     */
    public function __construct(string $name, string $visiblename, string $description, int $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, PARAM_INT, 4);
    }

    /**
     * Accept only whole numbers from 1 to the limit.
     *
     * @param string $data Submitted value.
     * @return true|string True if valid, otherwise an error message.
     */
    public function validate($data) {
        $result = parent::validate($data);
        if ($result !== true) {
            return $result;
        }
        if ((int)$data < 1 || (int)$data > report_page::MAXCOURSES_LIMIT) {
            return get_string('maxcoursesinvalid', 'report_studentsummary', report_page::MAXCOURSES_LIMIT);
        }
        return true;
    }
}
