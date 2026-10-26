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
 * Selection toolbar of the course report: a select-all checkbox, the selected count and a print button.
 *
 * Mounted automatically via data-react-component="@moodle/lms/report_studentsummary/Toolbar"
 * by core/react_autoinit. It is presentational only: report_studentsummary/course owns the selection state and
 * the printing, and talks to this component with the events below.
 *
 * @module     report_studentsummary/Toolbar
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useState} from 'react';
import {Button, Checkbox} from '@moodlehq/design-system';

/** Sent by course.js with the current selection state. */
export const EVENT_STATE = 'report_studentsummary:state';
/** Sent by this component once it listens for the state. */
export const EVENT_READY = 'report_studentsummary:toolbarready';
/** Sent by this component when select all is toggled; detail.checked is the new value. */
export const EVENT_TOGGLEALL = 'report_studentsummary:toggleall';
/** Sent by this component when the print button is pressed. */
export const EVENT_PRINT = 'report_studentsummary:printselected';

interface ToolbarProps {
    selectalllabel: string;
    printlabel: string;
}

interface SelectionState {
    checked: boolean;
    indeterminate: boolean;
    counttext: string;
}

export default function Toolbar({selectalllabel, printlabel}: ToolbarProps) {
    const [state, setState] = useState<SelectionState>({checked: true, indeterminate: false, counttext: ''});

    useEffect(() => {
        const onState = (e: Event) => setState((e as CustomEvent<SelectionState>).detail);
        document.addEventListener(EVENT_STATE, onState);
        document.dispatchEvent(new CustomEvent(EVENT_READY));
        return () => document.removeEventListener(EVENT_STATE, onState);
    }, []);

    return (
        <div className="d-flex flex-wrap align-items-center gap-3">
            <Checkbox
                label={selectalllabel}
                checked={state.checked}
                indeterminate={state.indeterminate}
                onChange={(e) => document.dispatchEvent(
                    new CustomEvent(EVENT_TOGGLEALL, {detail: {checked: e.target.checked}})
                )}
            />
            <span className="text-muted" aria-live="polite">{state.counttext}</span>
            <Button
                className="ms-auto"
                variant="primary"
                label={printlabel}
                onClick={() => document.dispatchEvent(new CustomEvent(EVENT_PRINT))}
            />
        </div>
    );
}
