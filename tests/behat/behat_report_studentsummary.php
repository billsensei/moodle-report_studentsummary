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
 * Behat steps and page resolvers for report_studentsummary.
 *
 * @package    report_studentsummary
 * @category   test
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat page resolvers for report_studentsummary.
 *
 * @package    report_studentsummary
 * @category   test
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_report_studentsummary extends behat_base {
    /**
     * Convert a page name into a url, for "I am on the ... page" steps.
     *
     * Recognised types: "report_studentsummary > course" (identifier is the course short name).
     *
     * @param string $type Page type.
     * @param string $identifier Identifier of the instance.
     * @return moodle_url
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch (strtolower($type)) {
            case 'course':
                return new moodle_url('/report/studentsummary/course.php', ['id' => $this->get_course_id($identifier)]);
            default:
                throw new Exception('Unrecognised report_studentsummary page type "' . $type . '".');
        }
    }

    /**
     * Checks that the current address does not contain the given text.
     *
     * @Then /^the current URL should not contain "(?P<text_string>[^"]*)"$/
     * @param string $text Text that must be absent.
     */
    public function the_current_url_should_not_contain(string $text): void {
        $url = $this->getSession()->getCurrentUrl();
        if (stripos($url, $text) !== false) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "The current URL '$url' contains '$text'",
                $this->getSession()
            );
        }
    }
}
