# Moodle tracker issue draft

**Summary:** Plugins overview page shows no "Settings" link for report plugins that have a settings page

**Component:** Plugin overview / Reports (`core_plugin`, `\core\plugininfo\report`)

**Affects:** 4.5.15, 5.0.11, 5.1.8+, 5.2.4+ and 5.3+ (build 20261021). In each of these `\core\plugininfo\report` has no `get_settings_section_name()`; checked in the source of each branch. The empty cell was not looked at on the page on every branch.

## Description

Site administration > Plugins > Plugins overview has a "Settings" column. The link in it comes from `\core\plugininfo\base::get_settings_url()`, which relies on the plugininfo class returning a section name from `get_settings_section_name()`.

Other plugin types do this: `block`, `enrol`, `auth`, `editor`, `format` and others. `\core\plugininfo\report` does not, so base returns `null` and the column stays empty for every report plugin.

The settings page does exist. `admin/settings/plugins.php` (line 590) creates an `admin_settingpage` named `'report' . $report` for each report plugin that has a `settings.php`. It is reachable at `/admin/settings.php?section=report<name>`. Plugin authors can't fix this themselves, because the plugininfo class is chosen by plugin type and a plugin can't replace it.

## Steps to reproduce

1. Install a report plugin that ships a `settings.php` with admin settings.
2. Go to Site administration > Plugins > Plugins overview.
3. Find the plugin's row.

**Expected:** a "Settings" link to `/admin/settings.php?section=report<name>`, as for blocks, enrolment plugins and so on.

**Actual:** the Settings cell is empty.

## Proposed fix

A section name alone is not enough. Most core reports register an `admin_externalpage` named `'report' . $name` that links to the report itself (for example `reportlog`). `locate()` finds it, and since external pages are also linkable, `get_settings_url()` would return the report's address as its "Settings" link. So the fix also overrides `get_settings_url()` to accept only a real settings page.

Add to `public/lib/classes/plugininfo/report.php`:

```php
#[\Override]
public function get_settings_section_name() {
    return 'report' . $this->name;
}

/**
 * Returns the URL of the plugin settings page.
 *
 * Reports often register an admin_externalpage with the same name that links to the report itself,
 * so only a real settings page counts here.
 *
 * @return null|moodle_url
 */
#[\Override]
public function get_settings_url(): ?moodle_url {
    $settings = admin_get_root()->locate($this->get_settings_section_name());
    if ($settings instanceof \admin_settingpage) {
        return $settings->get_settings_page_url();
    }

    return null;
}
```

Report plugins without a settings page still get `null`.

## Testing notes

New PHPUnit test `public/lib/tests/plugininfo/report_test.php`:

- `report_outline` with a settings page added to the admin tree returns `/admin/settings.php?section=reportoutline`. No core report has real settings, so the test adds the page itself.
- `report_log` (only an external page named `reportlog`) and `report_outline` (no admin page) return `null`.
- The section name test checks `report_eventlist` gives `reporteventlist`.

The `report_log` case fails without the `get_settings_url()` override, which is why it is needed: with only `get_settings_section_name()` added, `report_log` got `/report/log/index.php?id=0` as its Settings link.

Checked with the patch on 5.3: a third-party report with a settings page (`report_studentsummary`) gets a Settings link to `/admin/settings.php?section=reportstudentsummary` on the Plugins overview page (Behat). Still to do: run the new PHPUnit test and the full `lib/tests/plugininfo` group, and file the issue (tracker.moodle.org needs an account; not filed).
