var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
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
import { useEffect, useState } from "react";
import { Button, Checkbox } from "@moodlehq/design-system";
const EVENT_STATE = "report_studentsummary:state";
const EVENT_READY = "report_studentsummary:toolbarready";
const EVENT_TOGGLEALL = "report_studentsummary:toggleall";
const EVENT_PRINT = "report_studentsummary:printselected";
function Toolbar({ selectalllabel, printlabel }) {
  const [state, setState] = useState({ checked: true, indeterminate: false, counttext: "" });
  useEffect(() => {
    const onState = /* @__PURE__ */ __name((e) => setState(e.detail), "onState");
    document.addEventListener(EVENT_STATE, onState);
    document.dispatchEvent(new CustomEvent(EVENT_READY));
    return () => document.removeEventListener(EVENT_STATE, onState);
  }, []);
  return /* @__PURE__ */ jsxDEV("div", { className: "d-flex flex-wrap align-items-center gap-3", children: [
    /* @__PURE__ */ jsxDEV(
      Checkbox,
      {
        label: selectalllabel,
        checked: state.checked,
        indeterminate: state.indeterminate,
        onChange: (e) => document.dispatchEvent(
          new CustomEvent(EVENT_TOGGLEALL, { detail: { checked: e.target.checked } })
        )
      },
      void 0,
      false,
      {
        fileName: "public/report/studentsummary/js/esm/src/Toolbar.tsx",
        lineNumber: 63,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV("span", { className: "text-muted", "aria-live": "polite", children: state.counttext }, void 0, false, {
      fileName: "public/report/studentsummary/js/esm/src/Toolbar.tsx",
      lineNumber: 71,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(
      Button,
      {
        className: "ms-auto",
        variant: "primary",
        label: printlabel,
        onClick: () => document.dispatchEvent(new CustomEvent(EVENT_PRINT))
      },
      void 0,
      false,
      {
        fileName: "public/report/studentsummary/js/esm/src/Toolbar.tsx",
        lineNumber: 72,
        columnNumber: 13
      },
      this
    )
  ] }, void 0, true, {
    fileName: "public/report/studentsummary/js/esm/src/Toolbar.tsx",
    lineNumber: 62,
    columnNumber: 9
  }, this);
}
__name(Toolbar, "Toolbar");
export {
  EVENT_PRINT,
  EVENT_READY,
  EVENT_STATE,
  EVENT_TOGGLEALL,
  Toolbar as default
};
//# sourceMappingURL=Toolbar.dev.js.map
