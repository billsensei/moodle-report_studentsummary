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

/**
 * Tests for the filter form and settings.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\form;

/**
 * Tests for the filter form and settings.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\form\filter_form
 */
final class filter_form_test extends \advanced_testcase {
    /**
     * The end date cannot precede the start date.
     */
    public function test_validation(): void {
        $this->resetAfterTest();
        $form = new filter_form(null, ['userid' => 2]);
        $this->assertArrayHasKey('to', $form->validation(['from' => 2000, 'to' => 1000], []));
        $this->assertSame([], $form->validation(['from' => 1000, 'to' => 2000], []));
        $this->assertSame([], $form->validation(['from' => 0, 'to' => 1000], []));
    }

    /**
     * The filter is posted, so the session key never goes into the address bar, history or a printed footer.
     */
    public function test_form_is_posted(): void {
        $this->resetAfterTest();
        $html = (new filter_form('/report/studentsummary/index.php', ['userid' => 2]))->render();
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringNotContainsString('method="get"', $html);
    }

    /**
     * A submitted range becomes clean address parameters, with the end date covering the whole day.
     */
    public function test_range_params(): void {
        $this->assertSame(
            ['fromts' => 1000, 'tots' => 5000 + DAYSECS - 1],
            filter_form::range_params((object)['from' => 1000, 'to' => 5000])
        );
        $this->assertSame(['fromts' => null, 'tots' => null], filter_form::range_params((object)['from' => 0, 'to' => 0]));
        $this->assertSame(['fromts' => 1000, 'tots' => null], filter_form::range_params((object)['from' => 1000]));
    }

    /**
     * Address parameters give back the form values the user chose.
     */
    public function test_range_defaults_round_trip(): void {
        $params = filter_form::range_params((object)['from' => 1000, 'to' => 5000]);
        $this->assertSame(['from' => 1000, 'to' => 5000], filter_form::range_defaults($params['fromts'], $params['tots']));
        $this->assertSame(['from' => 0, 'to' => 0], filter_form::range_defaults(0, 0));
    }

    /**
     * The admin settings exist with the documented defaults.
     */
    public function test_settings_defaults(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $root = admin_get_root(true, true);
        $page = $root->locate('reportstudentsummary');
        $this->assertInstanceOf(\admin_settingpage::class, $page);
        $names = array_keys((array)$page->settings);
        foreach (['idletimeout', 'tailtime', 'maxcourses', 'showtime'] as $name) {
            $this->assertContains('report_studentsummary' . $name, $names);
        }
        $idle = $page->settings->report_studentsummaryidletimeout->get_defaultsetting();
        $this->assertSame(1200, (int)$idle['v'] * (int)$idle['u']);
        $this->assertSame(12, (int)$page->settings->report_studentsummarymaxcourses->get_defaultsetting());
    }

    /**
     * The course cap setting only accepts whole numbers from 1 to the limit.
     */
    public function test_maxcourses_validation(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $setting = admin_get_root(true, true)->locate('reportstudentsummary')->settings->report_studentsummarymaxcourses;
        $limit = \report_studentsummary\output\report_page::MAXCOURSES_LIMIT;
        foreach (['0', '-3', (string)($limit + 1), '1000000', 'abc', '2.5', ''] as $bad) {
            $this->assertNotSame('', $setting->write_setting($bad), "'$bad' should be rejected");
        }
        $this->assertSame('', $setting->write_setting('1'));
        $this->assertSame('', $setting->write_setting((string)$limit));
        $this->assertSame((string)$limit, get_config('report_studentsummary', 'maxcourses'));
    }

    /**
     * Colour settings take empty or readable hex colours only, and the palette setting offers every preset.
     */
    public function test_colour_settings(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $settings = admin_get_root(true, true)->locate('reportstudentsummary')->settings;
        $this->assertSame(
            array_keys(\report_studentsummary\local\palette::presets()),
            array_keys($settings->report_studentsummarypalette->choices)
        );
        foreach (array_keys(\report_studentsummary\local\palette::BOXES) as $box) {
            $name = 'report_studentsummarycolour_' . $box;
            $this->assertObjectHasProperty($name, $settings, $name);
        }
        $setting = $settings->report_studentsummarycolour_activities;
        foreach (['red', 'rgb(0,0,0)', '#12', 'zzz', '#222222', '#000000', '#7f8fa6'] as $bad) {
            $this->assertNotSame('', $setting->write_setting($bad), "'$bad' should be rejected");
        }
        $this->assertSame('', $setting->write_setting('#DBEAFE'));
        $this->assertSame('#dbeafe', get_config('report_studentsummary', 'colour_activities'));
        $this->assertSame('', $setting->write_setting(''));
        $this->assertSame('', get_config('report_studentsummary', 'colour_activities'));
    }
}
