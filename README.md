# Student Summary Report (report_studentsummary)

A Moodle report that summarises one student's engagement across the site on a single, printable A4 page.

## What it shows

| Box | Content |
|-----|---------|
| Header | Student name, picture, site name, report date, date range |
| Activities completed | Total and breakdown by activity type |
| Resources completed | Total and breakdown by resource type |
| Average grade | Unweighted mean of graded activity items, as a percentage |
| Time spent | Estimated time on site and in activities (can be switched off) |
| Courses enrolled | Count and completion progress per course |
| Current grades | Course total per enrolled course |

### All students of a course

Teachers can open **Course > Reports > Student summaries (all students)** to see one summary per active student. Each prints on its own A4 page. Tick the students you want and press **Print selected**, or use **Print this one** above a single summary. Choose a group (in group courses), a date range that applies to the whole class, and how many students to show per page (25 by default; paging links appear for larger classes, and printing covers the students on the current page).

On this page "student" means an active (not suspended) enrolled user who holds `moodle/grade:view` in the course. If a site removes that capability from the student role the page is empty; if it grants it to teachers, they are listed too.

### Single student

Use the **Print** button, or the browser's print dialog. The page prints on one A4 portrait sheet with colours kept and navigation hidden. A start and end date can limit the time estimate; completion and grades are always current.

## Requirements

Moodle 4.5 or later. Release 0.4.5 was tested on 4.5.15, 5.0.11, 5.1.8 and 5.3 (0.4.4 also on 5.2.4).

## Installation

Download the zip from the [latest release](https://github.com/billsensei/moodle-report_studentsummary/releases/latest) (currently [0.4.5](https://github.com/billsensei/moodle-report_studentsummary/releases/tag/v0.4.5), beta). It contains one folder, `studentsummary`.

Unpack it into `report/` (Moodle 4.5) or `public/report/` (Moodle 5.1 and later), so the plugin ends up in `report/studentsummary`, then visit **Site administration > Notifications**, or run `php admin/cli/upgrade.php`.

## Who can see what

| Capability | Default roles | Meaning |
|------------|---------------|---------|
| `report/studentsummary:viewown` | Authenticated user | Own report |
| `report/studentsummary:viewall` | Teacher, Non-editing teacher, Manager | Reports of users enrolled in the courses where the role is held |

Beyond the capability, the report only includes data the viewer may see:

- Courses where the viewer has active access to the course, and hidden courses only with `moodle/course:viewhiddencourses`.
- In separate-groups courses, only users who share a group with the viewer, unless the viewer has `moodle/site:accessallgroups`.
- Grades only with `moodle/grade:viewall`. Students see their own grades only when the course shows the gradebook to students. Hidden grades need `moodle/grade:viewhidden`. A course total that contains hidden items follows the gradebook setting "Hide totals if they contain hidden items" (shown as Hidden, or recalculated without the hidden items) for viewers without that capability.
- Time in a course only for courses the viewer may see. Time outside any course is shown on your own report and to viewers with `viewall` on the user or the site.

The report is entered from the user's profile (Reports section) or **Site administration > Reports > Student summary**.

## Settings

Under **Site administration > Plugins > Reports > Student summary**:

| Setting | Default | Purpose |
|---------|---------|---------|
| Idle timeout | 20 minutes | A gap between log entries up to this long counts as time spent |
| Tail time | 60 seconds | Time credited for a longer gap, and for the last entry |
| Maximum courses listed | 12 | More courses show as "+N more" so the page stays on one sheet. Allowed range 1 to 50 |
| Show time spent | Yes | Turns the estimate off |
| Colour palette | Default | Ready-made colours for every box: Default (soft pastels), High contrast, Greyscale (for black-and-white printing) or Earth tones |
| Box colours (one per box) | empty | Optional `#rrggbb` background for the header, activities, resources, average grade, time, courses and current grades boxes. Empty uses the palette. The border is derived from the background |

Colour choices are checked so that the text on them keeps a contrast ratio of at least 4.5:1 (WCAG AA); a colour that is too dark is refused. The printed page keeps the chosen colours.

## Known limitations

- Time spent is an **estimate** from the standard log store. It needs that store enabled and depends on log retention, so purged logs reduce it.
- Completion counts need activity completion enabled and configured in each course.
- The average grade is an unweighted mean of item percentages and can differ from course totals.
- The time estimate scans the user's log rows. The result is cached in the viewer's session for 10 minutes, so it can lag behind very recent activity.
- PDF export is not included; use the browser's print to PDF.

## Privacy

The plugin stores no personal data (it implements the null privacy provider). The only copy of any figure is a 10 minute cache of the estimated time totals in the viewer's own session, discarded when the session ends. Each view of a report is recorded by the standard log store as a `report_viewed` event (a course page as `course_report_viewed`, plus one `report_viewed` for each student shown on it).

## Licence

GNU GPL v3 or later.
