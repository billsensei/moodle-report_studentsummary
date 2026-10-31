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
 * Settings link on the Plugins overview page.
 *
 * @module     report_studentsummary/plugins_overview
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const ROW = 'tr.name-report_studentsummary';

/**
 * Put a Settings link in this plugin's row, in the same cell and with the same class as core's own links.
 * Does nothing if core already shows a link there (a core fix), or if the row is not on the page.
 *
 * @param {String} url The address of the settings page.
 * @param {String} text The link text, core's own "Settings".
 */
export const init = (url, text) => {
    const row = document.querySelector(ROW);
    if (!row || row.cells.length < 4) {
        return;
    }
    const cell = row.cells[3];
    if (cell.querySelector('a')) {
        return;
    }
    const link = document.createElement('a');
    link.className = 'settings';
    link.href = url;
    link.textContent = text;
    cell.appendChild(link);
};
