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
 * Selecting and printing student summaries on the course page.
 *
 * @module     report_studentsummary/course
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import Notification from 'core/notification';

const ROOT = '[data-region="report_studentsummary-course"]';
const SHEET = '[data-region="report_studentsummary-sheet"]';
const SELECT = '[data-action="report_studentsummary-select"]';
const SELECTALL = '[data-action="report_studentsummary-selectall"]';
const COUNT = '[data-region="report_studentsummary-selectedcount"]';

/**
 * Mark the first sheet that will print, so it does not start with a page break (which would leave page one blank).
 *
 * @param {HTMLElement[]} printing Sheets that will be printed, in page order.
 * @param {HTMLElement} root The course report container.
 */
const markFirst = (printing, root) => {
    root.querySelectorAll(SHEET).forEach((sheet) => sheet.classList.remove('ss-first'));
    if (printing.length) {
        printing[0].classList.add('ss-first');
    }
};

/**
 * Hide unticked sheets from the printout and update the "n selected" text.
 *
 * @param {HTMLElement} root The course report container.
 */
const refresh = async(root) => {
    const boxes = [...root.querySelectorAll(SELECT)];
    let selected = 0;
    boxes.forEach((box) => {
        box.closest(SHEET).classList.toggle('ss-unselected', !box.checked);
        selected += box.checked ? 1 : 0;
    });
    const all = root.querySelector(SELECTALL);
    markFirst(boxes.filter((box) => box.checked).map((box) => box.closest(SHEET)), root);
    all.checked = selected === boxes.length;
    all.indeterminate = selected > 0 && selected < boxes.length;
    root.querySelector(COUNT).textContent = await getString(
        'selectedcount', 'report_studentsummary', {selected, total: boxes.length});
};

/**
 * Refresh without leaving a rejected promise unhandled (for example when a string cannot be loaded).
 *
 * @param {HTMLElement} root The course report container.
 */
const update = (root) => refresh(root).catch(Notification.exception);

/**
 * Print with only the given sheets, then restore the page.
 *
 * @param {HTMLElement[]} sheets Sheets to print.
 */
const printSheets = (sheets) => {
    const body = document.body;
    const root = document.querySelector(ROOT);
    markFirst(sheets, root);
    sheets.forEach((sheet) => sheet.classList.add('ss-print-only'));
    body.classList.add('ss-printing-subset');
    const restore = () => {
        body.classList.remove('ss-printing-subset');
        sheets.forEach((sheet) => sheet.classList.remove('ss-print-only'));
        update(root);
        window.removeEventListener('afterprint', restore);
    };
    window.addEventListener('afterprint', restore);
    window.print();
};

/**
 * Wire up the checkboxes and print buttons.
 */
export const init = () => {
    const root = document.querySelector(ROOT);
    if (!root) {
        return;
    }
    root.addEventListener('change', (e) => {
        if (e.target.matches(SELECTALL)) {
            root.querySelectorAll(SELECT).forEach((box) => {
                box.checked = e.target.checked;
            });
        }
        if (e.target.matches(SELECT) || e.target.matches(SELECTALL)) {
            update(root);
        }
    });
    root.addEventListener('click', (e) => {
        const one = e.target.closest('[data-action="report_studentsummary-printone"]');
        if (one) {
            e.preventDefault();
            printSheets([one.closest(SHEET)]);
            return;
        }
        if (e.target.closest('[data-action="report_studentsummary-printselected"]')) {
            e.preventDefault();
            printSheets([...root.querySelectorAll(SHEET + ':not(.ss-unselected)')]);
        }
    });
    update(root);
};
