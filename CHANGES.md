# Changes since 0.5.2

The full history is in `changelog.md`.

## 0.5.3 (2026-10-31) - beta

- The Plugins overview page (Site administration > Plugins > Plugins overview) now shows a **Settings** link in this plugin's row. Core leaves that cell empty for every report plugin, so a Hooks API callback (`before_footer_html_generation`, only on `/admin/plugins.php`, only for users with `moodle/site:config`) loads a small AMD module, `report_studentsummary/plugins_overview`, which adds the link. If core ever shows a link there itself, the module does nothing.
- The "Plugin settings" link on the report pages stays.
- New file `db/hooks.php` and class `report_studentsummary\local\hook_callbacks`. New Behat scenario for the link.
- No database changes, so there is no upgrade step. Purge caches after updating so Moodle reads the new hook.
