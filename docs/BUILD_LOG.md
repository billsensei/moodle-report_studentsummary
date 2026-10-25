# BUILD LOG

## Phase 1 - Scaffold (2026-10-23)

Files: version.php, lang/en, db/access.php, lib.php, index.php (login + capability checks only), classes/privacy/provider.php (null_provider).
Dev repo: /home/bill/test2/report_studentsummary, symlinked to /home/bill/test2/moodle45/report/studentsummary.

### Moodle 4.5.15 (/home/bill/test2/moodle45)
```


-->report_studentsummary
++ Success (0.16 seconds) ++
== Setting new default values ==
Command line upgrade from 4.5.15 (Build: 20261005) (2024100715) to 4.5.15 (Build: 20261005) (2024100715) completed successfully.
exit=0


purge exit=0


version	2026102300


2026102300
```
Capabilities registered (mdl_capabilities / mdl_role_capabilities): viewown (system, user archetype), viewall (course, RISK_PERSONAL, editingteacher/teacher/manager).

Quality: phplint clean (6 files); validate OK; phpmd clean after suppressing fixed-signature callback params.
Not run / failing (tooling, not plugin): phpcs fatal "Cannot redeclare class ... AbstractTestCaseSniff" (moodle-plugin-ci vendor reachable via both /home/bill/test and /home/bill/test2 paths); phpdoc and savepoints default to ../moodle (the uninstalled 5.0 tree).

### Second tree (/home/bill/test2/moodle) - BLOCKED
- It is Moodle 5.0.11, not 5.1, and has no public/ directory.
- Its database 'moodle' contains only phpu_/bht_ tables; no mdl_ install, so upgrade.php/cfg.php fail with "Error reading from database".

## Phase 2 - Data classes + PHPUnit (2026-10-23)

Second tree: installed Moodle 5.0.11 site via admin/cli/install_database.php (db moodle, prefix mdl_); plugin installed (version 2026102300). Built en_AU.UTF-8 locale into ~/.local/locale (run PHPUnit with LOCPATH=/home/bill/.local/locale). Ran phpunit init + util --buildconfig in both trees.

### moodle-plugin-ci phplint
```
 RUN  PHP Lint on report_studentsummary
PHP 8.4.26 | 10 parallel jobs
.............                                                13/13 (100%)
Checked 13 files in 0.3 seconds
No syntax error found
```
### moodle-plugin-ci phpmd
```
 RUN  PHP Mess Detector on report_studentsummary
FILE: classes/local/completion_summary.php
FOUND 0 ERRORS AND 1 VIOLATIONS
 ==== =========== =================================================================================================================== 
  49   VIOLATION   The method get_summary() has a Cyclomatic Complexity of 11. The configured cyclomatic complexity threshold is 10.  
 ==== =========== =================================================================================================================== 
FILE: classes/local/course_grades.php
FOUND 0 ERRORS AND 1 VIOLATIONS
 ==== =========== ================================================================================================================ 
  55   VIOLATION   The method get_rows() has a Cyclomatic Complexity of 10. The configured cyclomatic complexity threshold is 10.  
 ==== =========== ================================================================================================================ 
FILE: classes/local/grade_summary.php
FOUND 0 ERRORS AND 1 VIOLATIONS
 ==== =========== =================================================================================================================== 
  47   VIOLATION   The method get_summary() has a Cyclomatic Complexity of 12. The configured cyclomatic complexity threshold is 10.  
 ==== =========== =================================================================================================================== 
```
### phpunit --testsuite report_studentsummary_testsuite (moodle45)
```
Moodle 4.5.15 (Build: 20261005), 215f44380bdc3ccf852a3a22e34f8423ff64033a
Php: 8.4.26, mariadb: 11.8.6, OS: Linux 6.12.111+deb13-amd64 x86_64
PHPUnit 9.6.34 by Sebastian Bergmann and contributors.
R
............                                                     13 / 13 (100%)
Time: 00:10.807, Memory: 72.50 MB
There was 1 risky test:
1) report_studentsummary\local\completion_summary_test::test_counts
Test code or tested code printed unexpected output: 
OK, but incomplete, skipped, or risky tests!
Tests: 13, Assertions: 36, Risky: 1.
```
### phpunit --testsuite report_studentsummary_testsuite (moodle)
```
Moodle 5.0.11 (Build: 20261005), 744cc0c19013e7be831dca24fafd6501d7eb8348
Php: 8.4.26, mariadb: 11.8.6, OS: Linux 6.12.111+deb13-amd64 x86_64
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.4.26
Configuration: /home/bill/test2/moodle/phpunit.xml
.............                                                     13 / 13 (100%)
Time: 00:11.037, Memory: 72.50 MB
OK, but there were issues!
Tests: 13, Assertions: 36, PHPUnit Deprecations: 3.
```
Notes: the single "risky" first test on 4.5 is core PHP 8.4 deprecation output (php-di, E_STRICT) printed during first DB reset; not plugin code. 5.0 run is clean (3 PHPUnit deprecation notices from core).

### Update after refactor
Extracted helper methods to clear phpmd complexity violations. Re-ran: phpmd clean (no violations); phpunit 13 tests / 36 assertions pass on 4.5.15 (1 risky = core PHP 8.4 deprecation output) and 5.0.11 (3 core PHPUnit deprecations).

## Phase 3 - Renderable, templates, renderer, event, access (2026-10-23)

New: classes/local/access.php, classes/event/report_viewed.php, classes/output/{report_page,renderer}.php, templates/{report,box}.mustache, lang strings, index.php rendering + event, tools/deploy.sh.

Deployment: symlinks break `__DIR__ . '/../../config.php'` under a web server (PHP resolves the real path), so the plugin is now rsynced into both trees with `tools/deploy.sh` (run after every edit, before tests/web checks).

Bug found and fixed: `enrol_get_users_courses()` hides hidden courses using the *student's* permissions, so a teacher would never see them. access::get_courses() now uses `enrol_get_all_users_courses()` and applies the viewer's own visibility/capability/group rules.

### PHPUnit (23 tests, 77 assertions: access, event, report page, data classes)
- Moodle 4.5.15: `OK, but incomplete, skipped, or risky tests! Tests: 23, Assertions: 77, Risky: 2` (risky = core PHP 8.4 deprecation output from php-di/mustache on first load; confirmed by running the test alone)
- Moodle 5.0.11: `OK, but there were issues! Tests: 23, Assertions: 77, PHPUnit Deprecations: 6` (core deprecations)
- phplint clean (20 files); phpmd 0 violations.

### Checkpoint: HTTP check (php -S, curl login) on 4.5 (:8002) and 5.0 (:8000)
Seeded users: sstudenta (id 3, course ss1), sstudentb (id 4, course ss2), steacher (id 5, teacher in ss1).
Identical on both versions:
```
sstudenta  index.php             -> HTTP 200, 9 boxes, shows "Summary Course 1"
sstudenta  index.php?userid=4    -> HTTP 404, "Sorry, but you do not currently have permissions to do that (View student summary reports of other users)."
steacher   index.php?userid=3    -> HTTP 200, 9 boxes
steacher   index.php?userid=4    -> HTTP 404, same permission error (student outside teacher's course)
admin      index.php?userid=3    -> HTTP 200, 9 boxes
```
Not yet done: styling (Phase 4), date-range filter form (params `from`/`to` accepted, no form), settings page (Phase 5; code already reads idletimeout/tailtime/maxcourses/showtime with defaults).

## Phase 4 - Styling, A4 print, print JS (2026-10-23)

New: styles.css, amd/src/print.js, amd/build/print.min.js(+map), print button + progress bars in templates/report.mustache, body class `report-studentsummary-page`, lang string `printreport`.

Deviation from PLAN 6.2: a plain `@page { size: A4 }` in plugin styles.css is compiled into the theme CSS for **every** Moodle page and would change printing site-wide. Used a named page `@page ss-a4` applied to `body.report-studentsummary-page` instead (applying `page:` to the nested wrapper forced a blank extra page - found in testing). Chrome-hiding rules are scoped to that body class too.

### Commands and results
- `grunt amd --root=report/studentsummary` (run in the 5.0 tree, build copied to repo): eslint clean, rollup Done; amd/build identical to deployed copy.
- `grunt stylelint:css --root=report/studentsummary`: 0 errors, 0 warnings (scoped `stylelint-disable` for pt units, !important and the `page` property in the print block, with reasons in the file).
- Contrast (text on box tints, WCAG): body text 11.9-13.4:1, secondary text 7.1-8.0:1 (AA needs 4.5:1).
- Print to PDF with headless Chromium (authenticated HTML fetched with curl, `--print-to-pdf`, `pdfinfo`):
```
Moodle 4.5.15  1 course  : Pages: 1, Page size 594.96 x 841.92 pts (A4)
Moodle 4.5.15  12 courses: Pages: 1, A4  (12 courses, 84 activities/60 resources, 12 graded items, all 7 activity + 5 resource types)
Moodle 4.5.15  maxcourses=5 (cap): Pages: 1, A4, text "+7 more courses not shown"
Moodle 5.0.11  1 course  : Pages: 1, A4
Moodle 5.0.11  12 courses: Pages: 1, A4
Other Moodle page (login) printed: letter, unchanged -> no leak
```
Inspected the 12-course PDF render: colours kept, navbar/drawer/footer hidden, report uses about two thirds of the page.
- PHPUnit unchanged: 23 tests/77 assertions pass on 4.5 and 5.0; phplint, phpmd clean.

Not verified: Firefox print preview (no scriptable PDF print in headless Firefox; manual check outstanding), RTL language rendering (logical CSS properties used, no RTL language pack installed), dark preset (Boost core has none; boxes use fixed light tints with dark text).
Test data added: user `sheavy` (id 6, 12 courses `hv1`-`hv12`) on both sites.

## Phase 5 - Settings, caching decision, edge states (2026-10-24)

New: settings.php (idletimeout, tailtime, maxcourses, showtime + link under Site administration > Reports), classes/form/filter_form.php (optional From/To date range, GET form, shown above the report, hidden in print), `nocompletion` note on the activity/resource boxes, lang strings, tests for edge states.
Note: report plugins' settings page is pre-created by core as `reportstudentsummary`, so the Reports link uses the id `reportstudentsummarylink`. maxcourses is clamped to at least 1. The earlier `from`/`to` URL params were removed (they clash with the form's array fields); the date range now comes only from the form.

### Caching: not added (decision)
Synthetic benchmark on Moodle 4.5.15, 300,000 log rows for one user (about 3 years), then deleted:
```
estimate: events=300000 site=24999h in 0.81s, peak memory delta 9.4 MB
```
The recordset streams in constant memory and uses the (userid, ...) prefix of the log store's index, so no MUC cache is needed and the privacy provider stays a null_provider. Revisit only if a site reports slow loads.

### Tests: 30 tests / 99 assertions, green on both trees
- 4.5.15: `OK, but incomplete, skipped, or risky tests! Tests: 30, Assertions: 99, Risky: 2` (risky = core PHP 8.4 deprecation output)
- 5.0.11: `OK, but there were issues! Tests: 30, Assertions: 99, PHPUnit Deprecations: 7`
Edge cases covered: no courses, no completion enabled (note shown), logs unavailable (notice not zero), time display disabled, course cap clamping, anonymised log rows, hidden grades/activities/courses/course totals, no grades, deleted users, date-range validation, settings present with defaults.

### Web checks (4.5 :8002 and 5.0 :8000, identical)
```
Settings page admin/settings.php?section=reportstudentsummary: HTTP 200, shows Idle timeout, Tail time, Maximum courses listed, Show time spent
Filter 1 Jan 2026 - 31 Dec 2026: HTTP 200, "Date range: 1/01/26 to 31/12/26"
Filter end before start: HTTP 200, "The end date cannot be before the start date."
Print to PDF, 12 courses, with the filter form present: Pages: 1, A4 (both versions)
```
(The form is a GET form and core adds a sesskey to it; submissions without one are rejected, which is expected.)
phplint, phpmd clean. Long lines only in lang strings (allowed).

## Phase 6 - Behat features (2026-10-24)

New: `tests/behat/own_report.feature` (4 scenarios: open own report from profile, no-courses empty state, date filter, end-before-start error), `tests/behat/access.feature` (5: teacher in course, teacher outside course denied, student cannot open another student, admin, separate groups with accessallgroups prohibited), `tests/behat/settings.feature` (3: settings page under Plugins > Reports, showtime off, maxcourses cap). All tagged `@report @report_studentsummary`.

### Environment notes
- Port 8001 was already held by an unrelated long-running server (`-t /home/bill/test/moodle`, started Oct 20, serves another Moodle without this plugin). The first Behat runs hit that site by mistake and failed ("Link ... not found"). That server was left alone; `behat_wwwroot` in this project's trees was changed to `http://127.0.0.1:8003` (4.5) and `http://127.0.0.1:8004` (5.0).
- PHP 8.4 deprecations from vendored Behat and Moodle code are fatal/corrupt the environment JSON unless masked: Behat run as `php -d error_reporting=22527 -d display_errors=0 vendor/bin/behat ...`, test sites served by `php -d display_errors=0 -d error_reporting=22527 -S 127.0.0.1:<port> -t <tree>` (PHP_CLI_SERVER_WORKERS=4). chromedriver on :9515 with headless Chrome profile from config.php.
- Step fixes found by the first real run: page heading text is outside `region-main`; the `region` selector did not match the box headings, so box text is asserted without a region; the settings page is `Plugins > Reports > Student summary` (`Reports > Student summary` is the report page link).

### Results (`behat --tags @report_studentsummary`)
```
Moodle 4.5.15 (Build: 20261005)  Php: 8.4.26, mariadb: 11.8.6
12 scenarios (12 passed)
96 steps (96 passed)
1m32.87s

Moodle 5.0.11 (Build: 20261005)  Php: 8.4.26, mariadb: 11.8.6
12 scenarios (12 passed)
96 steps (96 passed)
1m26.99s
```
Not covered by Behat: activity completion counts and time estimates (covered by PHPUnit), print layout (covered by the headless PDF checks in Phase 4). Behat was not run through `moodle-plugin-ci behat`; the trees' own `vendor/bin/behat` was used. The 5.0.11 tree stands in for 5.1, as in earlier phases.

## Phase 7 - Security review (2026-10-24)

Audited every PHP file, template and the AMD module against the moodle-security-audit checklist. Initial result: 0 CRITICAL, 2 HIGH, 1 MEDIUM, 3 LOW. All but the last LOW were fixed in one patch.

### Findings and fixes
| Severity | Finding | Fix |
|---|---|---|
| HIGH | `access::can_view_in_course()` checked only the `viewall` role capability, so a teacher with a suspended or expired enrolment could still open reports | added `can_access_course($course, $viewerid, '', true)`. The first attempt used the default `$onlyactive = false` and the new test caught it |
| HIGH | Grades shown without gradebook permission: any `viewall` holder saw other users' grades, and students saw their own when "Show gradebook to students" was off | new `access::can_view_grades()` mirrors `grade/report/user/index.php` (`moodle/grade:viewall`, or own + `moodle/grade:view` + `showgrades`); used by `grade_summary` (course skipped) and `course_grades` (row shows "Hidden") |
| MEDIUM | Time spent counted the user's logs from every course, leaking activity in courses the viewer cannot see | `time_estimator::get_summary()/accumulate()` take `$courseids`; time is credited only to permitted courses (gaps are still measured across all events). Site-level events need the new `access::can_view_site_activity()` (own report, or `viewall` on the user/system context) |
| LOW | `index.php` distinguished missing, deleted, suspended and unauthorised users (user id probing) | one generic `required_capability_exception` for all of them |
| LOW | `viewall` on the user context alone (mentor) gives a header-only report | left as is (behaviour is consistent: no course data is shown) |
| LOW | Every view rescans the user's log rows | not changed. A MUC cache is optional in the plan and would need a privacy provider change; deferred |

Also: `tests/local/grades_test.php::test_hidden_grades` used another *student* as the viewer, which only passed because of the leak above; it now views the user's own report.

### Tests added
`access_test`: suspended teacher enrolment, `can_view_grades`, `can_view_site_activity`. `grades_test::test_grade_visibility`. `time_estimator_test::test_course_filter`. New `tests/privacy/provider_test.php`.

### Verification (after the patch)
```
phpunit --testsuite report_studentsummary_testsuite
  Moodle 4.5.15: Tests: 36, Assertions: 124, Risky: 2 (core PHP 8.4 deprecation output in filter_form_test)
  Moodle 5.0.11: OK, Tests: 36, Assertions: 124, PHPUnit Deprecations: 8 (core)
behat --tags @report_studentsummary
  Moodle 4.5.15: 12 scenarios (12 passed), 96 steps (96 passed)
  Moodle 5.0.11: 12 scenarios (12 passed), 96 steps (96 passed)
moodle-plugin-ci phplint: 24 files, no syntax error
moodle-plugin-ci phpmd: no violations (exit 0)
```
phpcs was not re-run (known tooling fault, see Phase 1 notes).

### Checklist
- [x] Every entry point calls `require_login()` and a capability check first
- [x] userid tampering blocked (student A requests student B) - access_test, access.feature
- [x] Teacher cannot see students outside their courses/groups, or with a suspended enrolment
- [x] Hidden activities, hidden grades, hidden courses and gradebook-hidden courses excluded
- [x] All params typed with PARAM_*; no superglobals
- [x] All SQL uses placeholders
- [x] All output escaped; no triple mustache
- [x] No data written; the only form is a GET filter with sesskey
- [x] Privacy provider present and tested
- [x] No debug output
- [x] Deleted/suspended users handled. A deleted course is not tested separately
- Open: LOW log-scan caching (deferred); phpcs not run

Sign-off is conditional: the two HIGH findings are fixed and tested; phpcs and the deleted-course case remain for Phase 8.

## Phase 8 - Release prep (2026-10-24)

### Release changes
- `version.php`: version 2026102401, release 0.2.0, maturity ALPHA -> BETA, requires unchanged (2024100700, Moodle 4.5).
- Added `README.md` (features, install paths for 4.5 and 5.1+, capabilities and what each viewer can see, settings, limitations, privacy) and `CHANGES.md`.
- Code style: `phpcbf` fixed 50 formatting errors (report_page.php, grades_test.php). Replaced the invalid `@SuppressWarnings(PHPMD...)` docblock tags with `unset()` of the fixed-signature parameters (lib.php, report_page.php). Removed `defined('MOODLE_INTERNAL') || die();` from files without side effects (the Moodle checker flags it); files that `require_once` keep it. Wrapped one long line in access.php.
- Deployed to both trees and ran `php admin/cli/upgrade.php --non-interactive`: completed successfully on 4.5.15 and 5.0.11.

### Tooling fixes (not plugin problems)
- phpcs "Cannot redeclare class AbstractTestCaseSniff": `/home/bill/test2/moodle-plugin-ci/vendor/squizlabs/php_codesniffer/CodeSniffer.conf` points at the copy under `/home/bill/test/moodle-plugin-ci`, so the sniffs loaded twice. phpcs was run from the `/home/bill/test/moodle-plugin-ci` copy instead; the test2 config was not edited.
- grunt on 4.5: that tree had no `node_modules`; ran `npm ci` there (node v22.23.3, 1164 packages). `amd/build/print.min.js` and `.map` built by grunt are identical to the committed files in both trees.
- PHPUnit and Behat environments re-initialised in both trees after the version bump (`admin/tool/phpunit/cli/init.php`, `admin/tool/behat/cli/init.php`).

### Results (plugin path = the deployed copy in each tree)
```
                                     Moodle 4.5.15   Moodle 5.0.11
phplint (24 files)                   exit 0          exit 0
phpmd                                exit 0          exit 0
phpcs --max-warnings 0               exit 0          exit 0   (24/24 files, no output)
phpdoc                               exit 0          exit 0
validate                             exit 0          exit 0
savepoints                           exit 0          exit 0   ("No relevant files found": no upgrade.php)
mustache                             exit 0          exit 0
grunt --max-lint-warnings 0          exit 0          exit 0
phpunit --fail-on-warning            exit 0          exit 0   36 tests, 124 assertions
                                     (2 risky: core PHP 8.4    (8 PHPUnit deprecations, core)
                                      deprecation output)
behat @report_studentsummary         12 scen / 96 steps passed on both (1m28s / 1m26s)
```
Deviations from the plan's commands: `phpunit` was run as `vendor/bin/phpunit --testsuite report_studentsummary_testsuite --fail-on-warning` in each tree and `behat` with each tree's own `vendor/bin/behat`, not via `moodle-plugin-ci phpunit/behat`. The 5.0.11 tree stands in for 5.1 (no 5.1 tree is installed), so the 5.1 `public/` layout is untested.

### Release artefact
`/home/bill/test2/dist/report_studentsummary_2026102401.zip` (49 KB, 35 files, top-level folder `studentsummary/`; excludes `docs/` and `tools/`).

## Fresh-site install test of the release zip (2026-10-24)

Site: `/home/bill/test2/fresh45` (code copied from the 4.5.15 tree without the plugin and without `config.php`), database `moodle45` with prefix `fresh_` (the `moodle` DB user cannot create databases), dataroot `/home/bill/test/fresh45data`, served on http://localhost:8005. `admin/cli/install_database.php` completed successfully before the plugin was added (`report/studentsummary` absent).

Then: `unzip dist/report_studentsummary_2026102401.zip -d fresh45/report/` and `php admin/cli/upgrade.php --non-interactive`:
```
-->report_studentsummary
++ Success (0.13 seconds) ++
New setting: report_studentsummary/idletimeout, tailtime, maxcourses, showtime
Command line upgrade from 4.5.15 ... completed successfully.
cfg: idletimeout 1200, maxcourses 12, showtime 1, tailtime 60, version 2026102401
purge_caches exit 0
capabilities: viewown (system, risk 0), viewall (course, risk 8)
roles: user=viewown; editingteacher, teacher, manager=viewall
```
HTTP smoke test as admin: login -> /report/studentsummary/index.php 200 "Student summary for Admin User" with all seven boxes; settings page shows the four settings; Plugins overview lists the plugin; anonymous request redirects to the login page (303).

## Walk-through (2026-10-24)

`/home/bill/test2/walk-thru.pdf` (25 pages, A4): 18 screenshots taken with headless Chrome (chromedriver) on the fresh45 site, as administrator, teacher and students, plus the real A4 print output. Demo data (7 users, 6 named courses, 14 electives, completion, grades, ~1,400 log rows) was generated with a throwaway CLI script; screenshots and the HTML source are in `/home/bill/test2/dist/walkthrough/`.
Observed while capturing: teacher view of Alice shows 4 of 6 courses and 10h 59m vs 13h 18m for the admin (Chemistry time excluded); a course with the gradebook hidden shows "Hidden" and is left out of the student's own average (80.0% vs 83.0% for the teacher); the date filter changes only the time estimate (3h 34m for the last 30 days); printing gives 1 page, A4.

## Hardening release 0.2.1 (2026-10-24): the two LOW findings

| Finding | Fix |
|---|---|
| Every view rescans the user's log rows | `time_estimator::get_summary()` keeps its result in a new session-scoped MUC cache (`db/caches.php`, definition `timesummary`, TTL 600 s, key = md5 of user, range, idle/tail settings and the sorted course-id scope). Session mode means it is private to the viewer, ends with the session, and a teacher and an admin never share an entry. The log scan moved to a private `scan()` method. `privacy:metadata` text now mentions the short-lived session copy; the provider stays a `null_provider` because nothing persists beyond the session |
| "Maximum courses listed" had no upper bound | new `report_studentsummary\admin\setting_maxcourses` (extends `admin_setting_configtext`, overrides `validate()`): whole numbers 1-50 only. `report_page::MAXCOURSES_LIMIT = 50` is also enforced when the report is built, so a value written directly with `set_config()` cannot exceed it |

Mistake found and fixed on the way: the first version called `set_validate_function()` on a text setting, but that method only exists on select and duration settings. It made `settings.php` throw (visible as a failed PHPUnit init). The setting subclass replaced it. A failed init left half-built `phpu_` tables in both trees' databases, which I dropped by hand: moodle45 DB (own) and the `moodle` DB. NOTE the `moodle` DB, the `phpu_` prefix and the PHPUnit dataroot are shared by `/home/bill/test2/moodle` and the other project at `/home/bill/test/moodle`; that project's PHPUnit environment needs `php admin/tool/phpunit/cli/init.php` again. The `mdl_` tables were not touched (498 and 494 tables before and after).

Version 2026102402, release 0.2.1. New tests: `test_summary_is_cached`, `test_cap_upper_limit`, `test_maxcourses_validation`, and a Behat scenario "An unreasonable maximum courses value is rejected".

### Results
```
                              Moodle 4.5.15      Moodle 5.0.11
phplint/phpmd/phpcs 0 warn/phpdoc/validate/savepoints/mustache/grunt     all exit 0
phpunit --fail-on-warning     39 tests, 142 assertions (2 risky, core)   39 tests, 142 assertions (8 core deprecations)
behat @report_studentsummary  13 scenarios, 101 steps passed             13 scenarios, 101 steps passed
```
Fresh-site upgrade 0.2.0 -> 0.2.1 (zip `dist/report_studentsummary_2026102402.zip`, 53 KB): `upgrade.php` completed, version 2026102402; cache round-trip works; setting accepts 1 and 50, rejects 0, 51, 500, abc, 2.5 and empty.

### Privacy provider test strengthened (2026-10-24)
Added `provider_test::test_component_is_compliant`: `\core_privacy\manager::component_is_compliant('report_studentsummary')` must be true and `get_null_provider_reason()` must return `privacy:metadata` with a non-empty string. It fails if the plugin starts storing user data without moving to a full provider. PHPUnit: 40 tests, 145 assertions on 4.5.15 (2 risky, core) and 5.0.11 (8 core deprecations), `--fail-on-warning` exit 0; phpcs 0 warnings and phpdoc clean on both. Test-only change: version stays 2026102402 and `dist/report_studentsummary_2026102402.zip` was not rebuilt.

### Walk-through regenerated for 0.2.1 (2026-10-24)
`/home/bill/test2/walk-thru.pdf` rebuilt: 26 pages, 19 screenshots retaken on the fresh45 site (now at 0.2.1, version 2026102402). New step "Setting validation (0.2.1)" shows 500 being rejected ("Enter a whole number from 1 to 50."; nothing stored, maxcourses still 12). Install page now shows the 0.2.0 install followed by the 0.2.1 upgrade; summary updated to 40 PHPUnit tests / 145 assertions, 13 Behat scenarios / 101 steps, and the 10-minute session cache.

## Release 0.3.0: choose the report colours (2026-10-24)

Requested feature, decisions taken with the user: set **site-wide by the admin**, **presets plus per-box overrides**.

- New `classes/local/palette.php`: four presets (Default soft pastels, High contrast, Greyscale, Earth tones), per-box override resolution, WCAG contrast ratio, `style()` that returns the colours as CSS custom properties. Only validated `#rrggbb` values (and one fixed `rgba()` built from the ink colour) are ever emitted.
- New `classes/admin/setting_boxcolour.php`, class `setting_boxcolour` (extends `admin_setting_configcolourpicker`): empty or a hex colour that keeps >= 4.5:1 contrast against the weakest text colour (`#3e4c59`); non-hex values (names, rgb(), hsl()) are refused. Seven pickers (header, activities, resources, average grade, time, courses, current grades) plus a `palette` select and a swatch preview in the settings page.
- Colours are applied as an inline `style` on the `.report-studentsummary` wrapper, so they carry into the printed A4 page. `styles.css` keeps the default values as fallbacks (a unit test keeps them equal to the default preset). The header border became `--ss-header-border`.
- At render time an override that is not hex, or too dark, is ignored even if written straight into the config, so a bad value cannot break the page or inject CSS.
- Border of an overridden box = background scaled to 55%.

Version 2026102403, release 0.3.0. README and CHANGES updated; zip `dist/report_studentsummary_2026102403.zip` (62 KB, 40 files).

### Results
```
                                   Moodle 4.5.15          Moodle 5.0.11
phplint/phpmd/phpcs 0 warn/phpdoc/validate/savepoints/mustache/grunt/stylelint   all exit 0
phpunit --fail-on-warning          50 tests, 323 assertions (2 risky)    50 tests, 323 assertions (9 PHPUnit deprecations)
behat @report_studentsummary       15 scenarios, 113 steps passed        15 scenarios, 113 steps passed
```
New tests: `palette_test` (8 tests: preset readability, default == stylesheet, contrast formula, hex normalising, darken, palette/override resolution, bad overrides ignored, style string safe), `test_colours_in_output`, `test_colour_settings`; Behat: palette and override applied, too-dark colour rejected.

Fresh-site upgrade 0.2.1 -> 0.3.0 from the zip worked (8 new settings). All four palettes and an override set were rendered and compared (`/home/bill/test2/dist/palettes.png`); settings were reset to defaults afterwards.

CORRECTION to earlier entries: the "PHPUnit deprecations" on 5.0 (8, now 9) are NOT core. There is one per test class of ours: PHPUnit 11 warns about `@covers` doc-comment metadata, which Moodle's coding standard still requires. Harmless today; PHPUnit 12 would need attributes (not usable on 4.5's PHPUnit 9). The "2 risky" on 4.5 remain core PHP 8.4 deprecation output.

Not re-verified: Firefox/greyscale printer output of the new palettes, and the walk-through PDF (still describes 0.2.1).

### Walk-through regenerated for 0.3.0 (2026-10-24)
`/home/bill/test2/walk-thru.pdf` rebuilt: 30 pages, 22 screenshots retaken on the fresh45 site (0.3.0, version 2026102403). New: "Choosing colours" and "Colour contrast check" (a near-black colour for the Average grade box refused with "contrast 1.8:1; at least 4.5:1 is required"), an Earth tones report, and a page comparing all palettes side by side (`dist/palettes.png`). The install page shows the 0.2.0 install and the 0.2.1 and 0.3.0 upgrades; the summary reports 50 PHPUnit tests / 323 assertions and 15 Behat scenarios / 113 steps. The long settings screenshot was cropped to its top section so the colour section is shown once. Demo site left on defaults (palette default, no overrides, maxcourses 12).

## Verified on real Moodle 5.1 (2026-10-24)

Until now the 5.0.11 tree stood in for 5.1. A real 5.1 tree was cloned: `git clone --depth 1 -b MOODLE_501_STABLE` into `/home/bill/test2/moodle51` (note: the 5.1 branch is named `MOODLE_501_STABLE`; `MOODLE_500_STABLE` is 5.0). Release `5.1.8+ (Build: 20261021)`, version 2025100608.01, PHP 8.4.26, MariaDB 11.8.6 (user-space, port 3307, database `moodle51`, prefixes `mdl_`/`phpu_`/`bht_`, data roots `/home/bill/test/m51*`). 5.1 has the `public/` web root: plugin deployed to `moodle51/public/report/studentsummary`; `config.php` requires `public/lib/setup.php`; CLI scripts live under `public/admin/...`.

- Plugin installed by `install_database.php` at version 2026102403 (release 0.3.0), no errors.
- Results (plugin 0.3.0):
```
phpunit --fail-on-warning   OK, 50 tests, 323 assertions, 9 PHPUnit deprecations (the @covers notes, as on 5.0), exit 0
behat @report_studentsummary 15 scenarios (15 passed), 113 steps (113 passed)
moodle-plugin-ci            phplint, phpmd, phpcs --max-warnings 0, phpdoc, validate, savepoints, mustache, grunt: all clean
amd/build                   identical to the repo copy after `grunt amd` on 5.1
print (headless Chromium)   report as admin: Pages: 1, A4 (594.96 x 841.92 pts)
```
- moodle-plugin-ci needs `MOODLE_DIR=/home/bill/test2/moodle51` (it has no `--moodle` option for these commands). `phpdoc` needs `local_moodlecheck` installed in the site; it was installed, run, then uninstalled and removed from the tree. `grunt` needed `npm ci` in the 5.1 tree.
- The print check used the admin's own report (a site with no demo data). The 12-course layout and the "+N more" cap were not re-run on 5.1; they are unchanged CSS/templates verified on 4.5 and 5.0.
- Not verified: Firefox/greyscale printing (as before).
- Test site admin password was reset to a throwaway value for the check; the site is only for local testing.

### Walk-through built for Moodle 5.1 (2026-10-24)
`/home/bill/test2/walk-thru.pdf` (30 pages, A4; replaces the 4.5 version, whose HTML and images remain in `dist/walkthrough/`). Built on the real 5.1.8+ site (`/home/bill/test2/moodle51`, main site `mdl_`, served on :8005, database `moodle51`) with the same demo data as the 4.5 walk-through (7 users, 6 named courses and 14 electives, completion, grades, 423 log rows copied from the 4.5 site including module contexts). Numbers match the 4.5 walk-through: Alice 11 activities / 3 resources, 83.0% (80.0% for herself), 13h 18m (teacher 10h 59m), 162.00 (54%) Biology; Maya "+2 more".
- 22 screenshots retaken with headless Chrome (chromedriver, W3C protocol driven from a small throwaway Python client) as admin, teacher, Alice and Chloe; palettes grid recomposed from five live renders; the A4 print comes from Chrome's print engine on Alice's own report: Pages 1, 594.96 x 841.92 pts.
- Plugin files on the site are identical to `dist/report_studentsummary_2026102403.zip` (`diff -rq` clean). The install page now describes the 5.1 `public/report/` layout; the 4.5-only upgrade history (0.2.0 -> 0.2.1 -> 0.3.0) was dropped. The summary table has a 5.1.8+ column.
- Site left on defaults (palette default, no colour overrides, maxcourses 12). Demo users' password: `Passw0rd-51x`. HTML and images: `/home/bill/test2/dist/walkthrough51/`. The capture and build scripts were throwaway and are not kept.

### Firefox print check (2026-10-24)
Firefox ESR 153.4.0 (`/usr/bin/firefox-esr`, headless) driven through geckodriver 0.37.1 (downloaded to `/home/bill/test2/scratch51/gecko`, outside the project), WebDriver `/print` with A4 and background printing on, against the Moodle 5.1.8+ site. Three reports, each printed with shrink-to-fit on and off:
```
Alice (as herself)         Pages: 1, A4   (5 courses)
Maya (as admin, 14 courses) Pages: 1, A4  ("+2 more courses not shown")
Chloe (no courses)         Pages: 1, A4
```
Rasterised pages inspected: all seven boxes keep their colours, the navigation bar, filter form and Print button are not printed, nothing is cut off. Limits: this is Firefox's print engine through WebDriver, not the interactive print-preview dialog, and the Print button's `window.print()` was not clicked. Greyscale/black-and-white printer output and RTL languages are still unchecked.

## 0.4.0: course report, all students (2026-10-24)
New `course.php` (teacher page: one A4 summary per active student, tick boxes, Print selected / Print this one, group menu, one date range, 10/25/50/100 per page), `access::get_course_students()`, `output\course_report_page`, `course_report_viewed` event, course Reports-page link, templates `summary` (shared sheet) / `course`, AMD `course`, `.ss-sheet` print rules, `behat_report_studentsummary` page resolver. Single-report template split into `report` + `summary`; element ids now carry a per-student prefix so they stay unique on the course page. Version 2026102404, release 0.4.0; zip `dist/report_studentsummary_2026102404.zip`.
```
phpunit --fail-on-warning   5.1.8+: OK, 56 tests, 347 assertions (deprecations: the @covers notes)
                            4.5.15:  OK, 56 tests, 347 assertions, 2 risky (core PHP 8.4 deprecation output, as before)
behat @report_studentsummary 5.1.8+: 19 scenarios (19 passed), 140 steps (incl. @javascript tick-box scenario)
moodle-plugin-ci (5.1 tree) phplint, phpmd, phpcs --max-warnings 0, validate, mustache: clean
grunt amd / stylelint:css   clean; amd/build/course.min.js(.map) identical to repo copy
print (headless Chromium, real 5.1 site, teacher Tessa, BIO101: 2 students)  2 pages, A4, one student per page, no trailing blank page; with one student unticked: 1 page
```
- Theme CSS is cached: after changing `styles.css`, run `php admin/cli/purge_caches.php` before checking print output.
- Behat on 4.5.15 (0.4.0): 19 scenarios (19 passed), 140 steps (140 passed).
- phpdoc (0.4.0, Moodle 5.1 tree): clean, 36 PHP files checked, exit 0. `local_moodlecheck` was installed temporarily, run, then uninstalled and removed from the tree.
- Not re-run for 0.4.0: Firefox print of the course page, the real Print button / print-preview dialog (the print rules were checked with the selection classes applied by hand; the JS selection itself was checked by Behat), classes larger than 2 students and the paging bar in a browser (paging is covered by PHPUnit).
- Behat quirk: a permission exception on the very first "I am on the ... page" step is reported as a step failure, so the student-refused case is covered by PHPUnit (`access_course_students_test`) rather than Behat.

### Walk-through updated for 0.4.0 (2026-10-24)
`/home/bill/test2/walk-thru.pdf` is now 35 pages (the 0.3.0 version is kept as `dist/walkthrough51/walk-thru-0.3.0.pdf`). Steps 23 to 27 were added: course Reports link, all-students page, tick-box selection, and the real print output for the whole class (2 pages) and for a selection (1 page). Screenshots were taken on the 5.1.8+ site as Tessa (headless Chrome through chromedriver; print output from the WebDriver `/print` endpoint). Cover, contents, summary and quality table updated to 0.4.0 (quality rows not re-run for 0.4.0 are marked as such). Source: `dist/walkthrough51/walkthru.html`.

## 0.4.1: course page loads only needed user columns (2026-10-25)
Version 2026102500, release 0.4.1. `get_course_students()` selects name, picture and status columns only (no password hash); select-student label built in PHP. Zip `dist/report_studentsummary_2026102500.zip`.
```
phpunit --fail-on-warning   5.1.8+: OK, 56 tests, 350 assertions   4.5.15: OK, 56 tests, 350 assertions, 2 risky (core PHP 8.4 deprecation output)
behat @report_studentsummary 5.1.8+: 19 scenarios (19 passed), 140 steps   4.5.15: 19 scenarios (19 passed), 140 steps
moodle-plugin-ci (5.1 tree) phplint, phpmd, phpcs --max-warnings 0, validate: clean (phpdoc and mustache last run on 0.4.0; no template or docblock-structure change since)
```

### Walk-through updated for 0.4.1 (2026-10-25)
`/home/bill/test2/walk-thru.pdf` (35 pages) and `docs/walk-thru.pdf` now describe release 0.4.1 (version 2026102500, zip `report_studentsummary_2026102500.zip`; the files on the 5.1 site are identical to the zip). Step 1 (plugins overview) was retaken and shows 0.4.1; the quality table lists the 0.4.1 results. Steps 23 to 27 keep their "(0.4.0)" labels because that is when the course report was introduced. The test site's admin password was reset again (local test site only) to take the screenshot.

## Moodle 5.2 check, release 0.4.1 (2026-10-25)
New tree `/home/bill/test2/moodle52`: `git clone --depth 1 -b MOODLE_502_STABLE`, release `5.2.4+ (Build: 20261021)`, PHP 8.4.26 (5.2 needs PHP 8.3+), MariaDB 11.8.6, database `moodle52` (port 3307, prefixes `mdl_`/`phpu_`/`bht_`), data roots `/home/bill/test/m52*`, Behat site on :8008. Layout is the same as 5.1 (`public/` web root, CLI scripts at the tree root). Composer from `moodle51/composer.phar`. The plugin was copied with rsync (not `tools/deploy.sh`, which only knows 4.5, 5.0 and 5.1) and installed by `admin/cli/upgrade.php` without errors.
```
phpunit --fail-on-warning   OK, 56 tests, 350 assertions, 12 PHPUnit deprecations (the @covers notes)
behat @report_studentsummary 19 scenarios (19 passed), 140 steps (140 passed)
moodle-plugin-ci (MOODLE_DIR=moodle52) phplint, phpmd, phpcs --max-warnings 0, validate, mustache, savepoints: clean
```
- Not run on 5.2: phpdoc, grunt (`npm ci` not done in this tree), the print-to-A4 check and the 60-student class.
- `moodle` DB user needed `GRANT ALL` on `moodle52` by the MariaDB root user (socket `/home/bill/.local/var/mariadb/mysqld.sock`).

## Moodle 5.3 check, release 0.4.1 (2026-10-25)
New tree `/home/bill/test2/moodle53`: `git clone --depth 1 -b MOODLE_503_STABLE`, release `5.3+ (Build: 20261021)`, PHP 8.4.26 (5.3 needs PHP 8.3+), MariaDB 11.8.6, database `moodle53` (port 3307, prefixes `mdl_`/`phpu_`/`bht_`), data roots `/home/bill/test/m53*`, Behat site on :8010. Set up as for 5.2 (composer from `moodle51/composer.phar`, `GRANT ALL` by the MariaDB root user, plugin copied with rsync and installed by `admin/cli/upgrade.php` without errors).
```
phpunit --fail-on-warning   OK, 56 tests, 350 assertions, 12 PHPUnit deprecations (the @covers notes)
behat @report_studentsummary 19 scenarios (19 passed), 140 steps (140 passed)
moodle-plugin-ci (MOODLE_DIR=moodle53) phplint, phpmd, phpcs --max-warnings 0, validate, mustache, savepoints: clean
```
- Not run on 5.3 in that pass: phpdoc, grunt, the print-to-A4 check and the 60-student class (the print check follows below).

### Print check on Moodle 5.3 (2026-10-25)
The 5.3 main site (`/home/bill/test2/moodle53`, database `moodle53`, served on :8009) got a small demo set: teacher Tessa, students Alice, Ben, Cara, Dan, Eve (BIO101, 6 completion pages, 120 log rows each, except that Alice's rows were later deleted by a test cleanup and re-inserted with different times; Alice and Ben also in HIS101) and Maya (14 electives). Chrome (chromedriver, WebDriver `/print`, A4, backgrounds on) as Tessa, each printed with margin 0 and shrink-to-fit, with 1 cm margins and shrink-to-fit, and with 1 cm margins and no shrink:
```
single report Alice / Ben / Maya (14 courses, "+2 more courses not shown")   1 page each, all three modes
course page, 5 students, all ticked                                          5 pages, all three modes
course page, 2 of 5 un-ticked (3 of 5 selected)                              3 pages
course page, Print this one (same classes the JS sets)                       1 page
```
Rasterised Maya and a course-page sheet: all seven boxes keep their colours, nothing is cut off, no blank page. Not covered: Firefox, the interactive print dialog, the real Print button click, greyscale printers, RTL languages.

## 0.4.2: course total no longer leaks hidden grades (2026-10-25)
Found by the 0.4.1 audit: `course_grades` showed the real course total even when it included items hidden from the student (80 visible + 20 hidden showed 100.00 where the core user report showed "-"), so a student could work out a hidden grade. Affects 0.1.0 to 0.4.1. Fix: for viewers without `moodle/grade:viewhidden` the total follows "Hide totals if they contain hidden items" (hide / recalculate without hidden items / real total), using core's `grade_grade::get_hiding_affected()`; the stricter of the user report and overview settings applies. Version 2026102501, release 0.4.2, zip `dist/report_studentsummary_2026102501.zip`. Also in this release: the new privacy reason string.
```
new PHPUnit cases (5): hidden / excluded / real / stricter-setting-wins / no hidden items; the first three failed before the fix and pass after
live check (5.3): student's total hidden, matching the core user report
phpunit --fail-on-warning   4.5.15, 5.1.8+, 5.2.4+, 5.3+: OK, 61 tests, 366 assertions (4.5: 2 risky, core PHP 8.4 deprecation output)
behat @report_studentsummary 4.5.15, 5.1.8+, 5.2.4+, 5.3+: 19 scenarios (19 passed), 140 steps each
moodle-plugin-ci (5.3 tree) phplint, phpmd, phpcs --max-warnings 0: clean
```
- Not re-run for 0.4.2: validate, mustache, phpdoc, grunt, print checks (no template, docblock-structure, JS or CSS change). No Behat scenario for the hidden-total rule (hidden grade items are impractical to set up in Behat); PHPUnit covers it.
- Still open from the audit: filter form puts `sesskey` in the URL (and in the printed page footer); course page logs no per-student event; the walk-through and README text for step 19 should be re-read against the new behaviour.

## 0.4.3: all-hidden total leak and sesskey in the address (2026-10-25)
Version 2026102502, release 0.4.3, zip `dist/report_studentsummary_2026102502.zip`.
- 0.4.2's hidden-total fix used `isset()`/`??` on core's `get_hiding_affected()` result, so a total whose every item is hidden (recalculated to null) fell back to the real total. Found by the automated review of the 0.4.2 commit; reproduced with a failing PHPUnit test (a student with one hidden graded item saw a graded total), fixed with `array_key_exists()` as core does. Grade-total tests moved to `tests/local/course_total_test.php`.
- The date filter was a GET moodleform, so Apply put `sesskey` and `_qf__...` in the address, history and (with browser headers/footers on) the printed page footer. It is now posted and redirected to a clean address (`fromts`/`tots`, plus group, perpage on the course page); `filter_form::range_params()` / `range_defaults()`. The viewed-event is logged after the redirect. Printed footer re-checked on 5.3 (Chrome `Page.printToPDF` with header/footer on): no `sesskey`, no `_qf__`.
```
phpunit --fail-on-warning   4.5.15, 5.1.8+, 5.2.4+, 5.3+: OK, 65 tests, 379 assertions (4.5: 2 risky, core PHP 8.4 deprecation output)
behat @report_studentsummary 4.5.15, 5.1.8+, 5.2.4+, 5.3+: 19 scenarios (19 passed), 143 steps each (new step: the current URL should not contain "...")
moodle-plugin-ci (5.3 tree) phplint, phpmd, phpcs --max-warnings 0: clean
```
- Not re-run for 0.4.3: validate, mustache, phpdoc, grunt, the print-to-A4 page-count checks (no template, docblock-structure, JS or CSS change).
- Still open from the 0.4.1 audit: per-student view events on the course page (LOW), duplicated per-student queries (LOW), `course.php` course lookup before login (LOW), `lib.php` MOODLE_INTERNAL guard (LOW); the walk-through still says 0.4.1 and its step 19 / the README sentence should be re-read against the new grade behaviour.

### Walk-through updated for 0.4.3 (2026-10-25)
`/home/bill/test2/walk-thru.pdf` and `docs/walk-thru.pdf` (35 pages) now describe release 0.4.3 (version 2026102502; the files on the 5.1 site are identical to the zip). Step 1 was retaken (shows 0.4.3). Step 14 now shows the clean address (`fromts`/`tots`) instead of the old `sesskey` address, and its caption explains the post-and-redirect. Step 19 no longer says hidden grades "never leak"; it describes the hidden-total rule. The summary lists releases 0.4.1 to 0.4.3 and the 0.4.3 quality table: PHPUnit 65 tests / 379 assertions and Behat 19 scenarios / 143 steps (4.5, 5.1, 5.2, 5.3); phplint, phpmd, phpcs run on 5.3; validate, mustache, phpdoc and the A4 page-count check were not re-run (marked as such). Source: `dist/walkthrough51/walkthru.html`.

## LOW audit items fixed (2026-10-25, not yet released)
- L1: `course.php` now also triggers `report_viewed` (with `relateduserid`) for each student shown on the page.
- L3: `grade_summary` and `course_grades` load the user once per call instead of once per course; `report_page` reuses the course list for the time box.
- L4: `course.php` calls `require_login()` before `get_course()`.
- `amd/src/course.js`: `refresh()` is wrapped so a rejected promise goes to `Notification.exception`; `amd/build/course.min.js(.map)` rebuilt with grunt on 5.1 (eslint clean).
- `version.php`: `$plugin->supported = [405, 503]`. README: definition of "student" on the course page, and the extra log events.
- L6 not changed: the Moodle coding standard (phpcs) flags a `MOODLE_INTERNAL` guard in a functions-only `lib.php` ("Unexpected MOODLE_INTERNAL check"), so the audit item was a false positive. L5 (`{{{pagingbar}}}`) stays as justified. L2 (demo password in public git history) is the owner's decision.
```
phpunit --fail-on-warning   4.5.15, 5.1.8+, 5.2.4+, 5.3+: 65 tests, 379 assertions (4.5: 2 risky, core PHP 8.4 deprecation output)
behat @report_studentsummary 4.5, 5.1, 5.2, 5.3: 19 scenarios (19 passed), 143 steps
phplint, phpmd, phpcs --max-warnings 0, validate, mustache (5.3 tree): clean
```
No test asserts the new per-student events; Behat only shows the course page still loads with them. Version not bumped.
- Follow-up from the security audit of this change: the grade classes loaded the whole user row (`'*'`, including the password hash) only to read the id. They now load `id` only (as the course page already did). PHPUnit re-run on 5.3: 65 tests, 379 assertions; phpcs clean. Not re-run on the other trees (one-line change).

## Release 0.4.4 (2026-10-25)
Version 2026102503, release 0.4.4: the LOW audit fixes above (per-student log events on the course page, user loaded once per call by id only, login before course lookup, `course.js` error handling, `$plugin->supported = [405, 503]`, README definition of "student"). Zip `dist/report_studentsummary_2026102503.zip` (same file list as 0.4.3). Upgrade ran cleanly on 4.5, 5.1, 5.2, 5.3.
```
phpunit --fail-on-warning   4.5.15, 5.3+ re-run on the 0.4.4 files: 65 tests, 379 assertions (4.5: 2 risky, core PHP 8.4 deprecation output)
behat @report_studentsummary 4.5, 5.3 re-run: 19 scenarios (19 passed), 143 steps
phplint, phpmd, phpcs --max-warnings 0, validate (5.3 tree): clean
```
The same code (before the version bump) passed PHPUnit and Behat on 5.1 and 5.2 as well; those two trees were not re-run after the bump. Not re-run: mustache, phpdoc, print page-count checks. The walk-through still describes 0.4.3.
