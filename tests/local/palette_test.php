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
 * Tests for the colour palettes.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

/**
 * Tests for the colour palettes.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\local\palette
 */
final class palette_test extends \advanced_testcase {
    /**
     * Every ready-made palette keeps its text readable on every box.
     */
    public function test_presets_are_readable(): void {
        $this->assertArrayHasKey(palette::DEFAULT, palette::presets());
        foreach (palette::presets() as $key => $preset) {
            $this->assertSame(array_keys(palette::BOXES), array_keys($preset['boxes']), $key);
            foreach ($preset['boxes'] as $box => [$background, $border]) {
                $this->assertNotNull(palette::normalise_hex($background), "$key $box background");
                $this->assertNotNull(palette::normalise_hex($border), "$key $box border");
                $this->assertTrue(palette::is_readable($background, [$preset['ink'], $preset['soft']]), "$key $box");
            }
        }
    }

    /**
     * The default palette matches the colours in styles.css, which remain the fallback.
     */
    public function test_default_matches_stylesheet(): void {
        $css = file_get_contents(__DIR__ . '/../../styles.css');
        $default = palette::presets()['default'];
        foreach (palette::BOXES as $box => $stem) {
            $this->assertSame(1, preg_match("/--ss-$stem-bg:\\s*(#[0-9a-f]{6});/i", $css, $bg), "$stem bg");
            $this->assertSame($default['boxes'][$box][0], strtolower($bg[1]), "$stem bg");
            $this->assertSame(1, preg_match("/--ss-$stem-border:\\s*(#[0-9a-f]{6});/i", $css, $border), "$stem border");
            $this->assertSame($default['boxes'][$box][1], strtolower($border[1]), "$stem border");
        }
        $this->assertSame(1, preg_match('/--ss-ink:\\s*(#[0-9a-f]{6});/i', $css, $ink));
        $this->assertSame($default['ink'], $ink[1]);
    }

    /**
     * Contrast ratios follow the WCAG formula.
     */
    public function test_contrast(): void {
        $this->assertEqualsWithDelta(21.0, palette::contrast('#000000', '#ffffff'), 0.001);
        $this->assertEqualsWithDelta(1.0, palette::contrast('#336699', '#336699'), 0.001);
        $this->assertEqualsWithDelta(
            palette::contrast('#ffffff', '#767676'),
            palette::contrast('#767676', '#ffffff'),
            0.0001
        );
        // The well-known AA boundary: #767676 on white is just over 4.5.
        $this->assertGreaterThan(4.5, palette::contrast('#767676', '#ffffff'));
        $this->assertLessThan(4.5, palette::contrast('#777777', '#ffffff'));
    }

    /**
     * Hex colours are normalised; anything else is refused.
     */
    public function test_normalise_hex(): void {
        $this->assertSame('#aabbcc', palette::normalise_hex('#AABBCC'));
        $this->assertSame('#aabbcc', palette::normalise_hex('abc'));
        $this->assertSame('#112233', palette::normalise_hex(' 123 '));
        $this->assertSame('', palette::normalise_hex(''));
        foreach (['red', 'rgb(0,0,0)', '#12', '#1234', '#gggggg', 'red;background:url(x)', '#fff;x', 'url(#fff)'] as $bad) {
            $this->assertNull(palette::normalise_hex($bad), $bad);
        }
    }

    /**
     * A border is the background scaled towards black.
     */
    public function test_darken(): void {
        $this->assertSame('#000000', palette::darken('#ffffff', 0.0));
        $this->assertSame('#ffffff', palette::darken('#ffffff', 1.0));
        $this->assertSame('#808080', palette::darken('#ffffff', 0.5));
    }

    /**
     * The chosen palette is used, an unknown one falls back, and valid overrides win.
     */
    public function test_colours(): void {
        $this->resetAfterTest();
        $presets = palette::presets();
        $this->assertSame($presets['default']['boxes'], palette::colours()['boxes']);

        set_config('palette', 'earth', 'report_studentsummary');
        $this->assertSame($presets['earth']['boxes'], palette::colours()['boxes']);
        set_config('palette', 'nonsense', 'report_studentsummary');
        $this->assertSame($presets['default']['boxes'], palette::colours()['boxes']);

        set_config('palette', 'greyscale', 'report_studentsummary');
        set_config('colour_activities', '#E0F2FE', 'report_studentsummary');
        $boxes = palette::colours()['boxes'];
        $this->assertSame('#e0f2fe', $boxes['activities'][0]);
        $this->assertSame(palette::darken('#e0f2fe', 0.55), $boxes['activities'][1]);
        $this->assertSame($presets['greyscale']['boxes']['resources'], $boxes['resources']);
    }

    /**
     * Overrides that are not hex, or too dark for the text, are ignored when configuration is written directly.
     */
    public function test_bad_overrides_are_ignored(): void {
        $this->resetAfterTest();
        $default = palette::presets()['default']['boxes'];
        foreach (['red', 'rgb(0,0,0)', '#222222', '#000', 'red;background:url(x)', '#999999'] as $bad) {
            set_config('colour_time', $bad, 'report_studentsummary');
            $this->assertSame($default['time'], palette::colours()['boxes']['time'], $bad);
        }
    }

    /**
     * The style string holds only custom properties with hex or rgba values, whatever is configured.
     */
    public function test_style_is_safe(): void {
        $this->resetAfterTest();
        set_config('colour_header', 'red;background:url(//evil)', 'report_studentsummary');
        set_config('colour_grades', '#fdf2f8', 'report_studentsummary');
        $style = palette::style();
        $this->assertMatchesRegularExpression('/^(--ss-[a-z-]+:(#[0-9a-f]{6}|rgba\\(\\d+,\\d+,\\d+,0\\.15\\));)+$/', $style);
        $this->assertStringContainsString('--ss-rose-bg:#fdf2f8;', $style);
        $this->assertStringContainsString('--ss-header-bg:#e4e7eb;', $style);
        $this->assertStringNotContainsString('evil', $style);
    }

    /**
     * A box can have its own text colour, which also allows a dark background.
     */
    public function test_text_colour_overrides(): void {
        $this->resetAfterTest();
        $default = palette::presets()['default'];

        // A dark background alone is refused, but works with a light text colour.
        set_config('colour_time', '#1e3a8a', 'report_studentsummary');
        $this->assertSame($default['boxes']['time'], palette::colours()['boxes']['time']);
        set_config('textcolour_time', '#ffffff', 'report_studentsummary');
        $colours = palette::colours();
        $this->assertSame('#1e3a8a', $colours['boxes']['time'][0]);
        $this->assertSame(['time' => '#ffffff'], $colours['inks']);

        // A text colour on its own is checked against the palette background.
        set_config('textcolour_courses', '#000000', 'report_studentsummary');
        $this->assertSame('#000000', palette::colours()['inks']['courses']);
        set_config('textcolour_courses', '#ccfbf0', 'report_studentsummary');
        $this->assertArrayNotHasKey('courses', palette::colours()['inks']);

        // Light text on a light background: the pair is refused, and the background alone is used.
        set_config('colour_grade', '#e0f2fe', 'report_studentsummary');
        set_config('textcolour_grade', '#f0f0f0', 'report_studentsummary');
        $colours = palette::colours();
        $this->assertSame('#e0f2fe', $colours['boxes']['grade'][0]);
        $this->assertArrayNotHasKey('grade', $colours['inks']);

        // Text colours that are not hex are ignored.
        foreach (['white', 'rgb(255,255,255)', '#fff;x:y'] as $bad) {
            set_config('textcolour_resources', $bad, 'report_studentsummary');
            $this->assertArrayNotHasKey('resources', palette::colours()['inks'], $bad);
        }
    }

    /**
     * A box with its own text colour gets its own custom properties, and nothing unsafe gets in.
     */
    public function test_style_with_text_colour(): void {
        $this->resetAfterTest();
        set_config('colour_activities', '#1e3a8a', 'report_studentsummary');
        set_config('textcolour_activities', '#FFFFFF', 'report_studentsummary');
        set_config('textcolour_grades', 'red;background:url(//evil)', 'report_studentsummary');
        $style = palette::style();
        $this->assertStringContainsString('--ss-blue-bg:#1e3a8a;', $style);
        $this->assertStringContainsString('--ss-blue-ink:#ffffff;', $style);
        $this->assertStringContainsString('--ss-blue-bar-bg:rgba(255,255,255,0.15);', $style);
        $this->assertStringNotContainsString('--ss-rose-ink', $style);
        $this->assertStringNotContainsString('evil', $style);
        $this->assertMatchesRegularExpression('/^(--ss-[a-z-]+:(#[0-9a-f]{6}|rgba\\(\\d+,\\d+,\\d+,0\\.15\\));)+$/', $style);
    }

    /**
     * The settings check a box's background and text colour together.
     */
    public function test_setting_checks_the_pair(): void {
        $this->resetAfterTest();
        $background = new \report_studentsummary\admin\setting_boxcolour('time');
        $text = new \report_studentsummary\admin\setting_boxcolour('time', true);

        $this->assertNotSame('', $background->write_setting('#1e3a8a'));
        $this->assertNotSame('', $background->write_setting('red'));
        $this->assertSame('', $background->write_setting('#E0F2FE'));
        $this->assertSame('#e0f2fe', get_config('report_studentsummary', 'colour_time'));

        // White text on the light background is refused; dark text is fine.
        $this->assertNotSame('', $text->write_setting('#ffffff'));
        $this->assertSame('', $text->write_setting('#102a43'));
        $this->assertSame('#102a43', get_config('report_studentsummary', 'textcolour_time'));

        // With dark text stored, a dark background is now refused too.
        $this->assertNotSame('', $background->write_setting('#1e3a8a'));
        // Clearing the text colour is always allowed.
        $this->assertSame('', $text->write_setting(''));
        $this->assertSame('', get_config('report_studentsummary', 'textcolour_time'));
    }
}
