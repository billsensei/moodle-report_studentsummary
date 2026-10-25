# Changelog

## 0.4.4 (2026-10-25) - beta

- The course page (Student summaries, all students) now logs a `report_viewed` event for each student shown, so a search of the log for who viewed a student's report also finds views from the course page.
- Fewer database queries per report: the user is loaded once per report instead of once per course, and only the id is read (no password hash).
- `course.php` requires login before it looks up the course, so a visitor who is not logged in learns nothing about course ids.
- A failure while updating the "n selected" text on the course page is now reported instead of being an unhandled error.
- Declares the supported Moodle range (4.5 to 5.3).
- README: explains who counts as a "student" on the course page.

## 0.4.3 (2026-10-25) - beta

- **Security fix:** 0.4.2 still showed the real course total when every graded item behind it was hidden from the student. The total is now withheld (or shown as empty) in that case too, matching the core grade reports. Upgrading from 0.4.2 or earlier is recommended.
- The date filter no longer puts the session key in the address. The form is posted and then redirected to a clean address carrying only the dates (and the group and page size on the course page), so the key no longer appears in the address bar, browser history or the footer of a printed page.

## 0.4.2 (2026-10-25) - beta

- **Security fix:** the Current grades box showed a student the real course total even when it included grade items hidden from them, so a student could work out a hidden grade by subtraction. The total now follows the same rules as the core grade reports for viewers who may not see hidden grades:
  - "Hide totals if they contain hidden items" (the Moodle default): the total shows as Hidden.
  - "Show totals excluding hidden items": the total is recalculated without the hidden items.
  - "Show real totals": unchanged.
  - If the user report and overview report settings differ, the stricter one applies. Teachers and others who hold `moodle/grade:viewhidden` still see the real total.
  - Affects every earlier release (0.1.0 to 0.4.1); upgrading is recommended.
- The privacy reason string now mentions that report views are recorded in the standard log.

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
