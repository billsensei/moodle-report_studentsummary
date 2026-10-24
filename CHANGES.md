# Changelog

## 0.4.1 (2026-10-25) - beta

- Security hardening: the course page (Student summaries, all students) now loads only the user columns it needs (name, picture, status) instead of the whole user row, so password hashes and other unused fields are never read into memory.
- The select-student label is built in PHP and escaped by the template, so a name containing braces or commas cannot break the label.

## 0.4.0 (2026-10-24) - beta

- New: **Student summaries (all students)** for teachers. Open it from the course **Reports** page (or `report/studentsummary/course.php?id=<course id>`).
  - One summary per active student of the course, each on its own A4 page when printed.
  - Tick boxes choose who prints; **Print selected** prints the ticked students, **Print this one** prints a single student.
  - Group menu (respects separate groups), one date range for the whole class, and 10/25/50/100 students per page (default 25).
  - Needs `report/studentsummary:viewall` in the course. Each student is subject to the same rules as a single report (hidden grades, separate groups, suspended users).
  - New event: `\report_studentsummary\event\course_report_viewed`.
- Fixed: README had leftover merge-conflict markers.

## 0.3.0 (2026-10-24) - beta

- New: choose the report colours (Site administration > Plugins > Reports > Student summary > Report colours).
  - A ready-made palette: Default (soft pastels), High contrast, Greyscale (for black-and-white printing) or Earth tones.
  - Optional per-box background colours, as #rrggbb. The border is derived automatically.
  - Colours that would make the text hard to read (contrast below 4.5:1, WCAG AA) are refused on the settings page and ignored by the report. Non-hex values are refused.
  - The colours apply on screen and on the printed A4 page. With nothing configured the report looks exactly as before.

## 0.2.1 (2026-10-24) - beta

- Estimated time spent is now cached for 10 minutes in the viewer's session cache (new `db/caches.php`), so repeated views no longer rescan the log. The cache ends with the session; nothing is stored persistently.
- "Maximum courses listed" now accepts only whole numbers from 1 to 50 (checked on the settings page and again when the report is built).

## 0.2.0 (2026-10-24) - beta

- Security review (Phase 7):
  - Teachers whose enrolment is suspended or expired can no longer view reports.
  - Grades follow gradebook permissions (`moodle/grade:viewall`, and for students "Show gradebook to students").
  - Time spent is limited to courses the viewer may see.
  - A missing, deleted, suspended or unauthorised user now gives the same permission error.
- Added privacy provider test.

## 0.1.0 (2026-10-23) - alpha

- Initial development version: report page, A4 print styles, capabilities, settings, events, PHPUnit and Behat tests.
