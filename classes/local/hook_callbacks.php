<?php
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

namespace report_studentsummary\local;

use core\hook\output\before_footer_html_generation;

/**
 * Hook callbacks for report_studentsummary.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Add a Settings link to this plugin's row on the Plugins overview page.
     *
     * Core leaves the Settings cell empty for every report plugin (\core\plugininfo\report has no settings section),
     * and a plugin cannot change that, so a small AMD module fills in the cell for administrators.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;

        if (!$PAGE->has_set_url() || $PAGE->url->get_path() !== '/admin/plugins.php') {
            return;
        }
        if (!has_capability('moodle/site:config', \context_system::instance())) {
            return;
        }
        $url = new \moodle_url('/admin/settings.php', ['section' => 'reportstudentsummary']);
        $PAGE->requires->js_call_amd('report_studentsummary/plugins_overview', 'init', [
            $url->out(false),
            get_string('settings', 'core_plugin'),
        ]);
    }
}
