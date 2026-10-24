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
 * Print button for the student summary report.
 *
 * @module     report_studentsummary/print
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTOR = '[data-action="report_studentsummary-print"]';

/**
 * Open the browser print dialog when the print button is clicked.
 */
export const init = () => {
    document.querySelectorAll(SELECTOR).forEach((button) => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            window.print();
        });
    });
};
