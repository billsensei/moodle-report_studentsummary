# Changes since 0.5.2

The full history is in `changelog.md`.

## 0.6.0 (2026-11-12) - beta

- Each box can now have its own **text colour**, next to its background colour, under Site administration > Plugins > Reports > Student summary. Empty uses the palette's text colours. The text colour applies to everything in the box, including the labels, notes and progress bars.
- A box's background and text colour are checked together for contrast (at least 4.5:1, WCAG AA). A dark background is now accepted when a light text colour is chosen for the box, and a text colour that is too close to its background is refused. If a saved pair does not meet the minimum when the report is shown, the report uses the background alone, then the text colour alone, then the palette.
- New lang strings: `textcolour_*` (one per box), `textcolour_desc`, `textcolourlowcontrast`. New PHPUnit tests for the text colours and the settings check.
- No database changes, so there is no upgrade step. Purge caches after updating: Moodle keeps the plugin's stylesheet in the theme cache, and without a purge the new text colours are not applied.

## 0.5.5 (2026-11-12) - beta

- Fixes the Behat scenario for the Settings link on the Plugins overview page (`tests/behat/settings.feature`): it used a step Moodle does not define. Moodle Plugin CI now passes on Moodle 4.5 to 5.3. Also, the CI workflow can be started by hand.
- No code or database changes, so there is no upgrade step.

## 0.5.4 (2026-11-12) - beta

- The built React files in `js/esm/build` (`Toolbar.js`, `Toolbar.dev.js`) now start with the standard Moodle license header, which core's build step drops. New `tools/esm-banner.py` puts it back; run it after `grunt esm`. The component's behaviour is unchanged.
- New GitHub Actions workflow (`.github/workflows/ci.yml`) running Moodle Plugin CI on Moodle 4.5 to 5.3.
- No code or database changes, so there is no upgrade step.

## 0.5.3 (2026-10-31) - beta

- The Plugins overview page (Site administration > Plugins > Plugins overview) now shows a **Settings** link in this plugin's row. Core leaves that cell empty for every report plugin, so a Hooks API callback (`before_footer_html_generation`, only on `/admin/plugins.php`, only for users with `moodle/site:config`) loads a small AMD module, `report_studentsummary/plugins_overview`, which adds the link. If core ever shows a link there itself, the module does nothing.
- The "Plugin settings" link on the report pages stays.
- New file `db/hooks.php` and class `report_studentsummary\local\hook_callbacks`. New Behat scenario for the link.
- No database changes, so there is no upgrade step. Purge caches after updating so Moodle reads the new hook.
