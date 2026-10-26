import{useEffect as i,useState as l}from"react";import{Button as d,Checkbox as m}from"@moodlehq/design-system";import{jsx as n,jsxs as b}from"react/jsx-runtime";/**
 * Selection toolbar of the course report: a select-all checkbox, the selected count and a print button.
 *
 * Mounted automatically via data-react-component="@moodle/lms/report_studentsummary/Toolbar"
 * by core/react_autoinit. It is presentational only: report_studentsummary/course owns the selection state and
 * the printing, and talks to this component with the events below.
 *
 * @module     report_studentsummary/Toolbar
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const a="report_studentsummary:state",u="report_studentsummary:toolbarready",p="report_studentsummary:toggleall",E="report_studentsummary:printselected";function v({selectalllabel:o,printlabel:r}){const[t,s]=l({checked:!0,indeterminate:!1,counttext:""});return i(()=>{const e=c=>s(c.detail);return document.addEventListener(a,e),document.dispatchEvent(new CustomEvent(u)),()=>document.removeEventListener(a,e)},[]),b("div",{className:"d-flex flex-wrap align-items-center gap-3",children:[n(m,{label:o,checked:t.checked,indeterminate:t.indeterminate,onChange:e=>document.dispatchEvent(new CustomEvent(p,{detail:{checked:e.target.checked}}))}),n("span",{className:"text-muted","aria-live":"polite",children:t.counttext}),n(d,{className:"ms-auto",variant:"primary",label:r,onClick:()=>document.dispatchEvent(new CustomEvent(E))})]})}export{E as EVENT_PRINT,u as EVENT_READY,a as EVENT_STATE,p as EVENT_TOGGLEALL,v as default};
