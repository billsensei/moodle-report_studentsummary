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
 * Colour palettes for the report boxes.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\local;

/**
 * Resolves the colours of the report: a ready-made palette plus optional per-box overrides.
 *
 * Only validated #rrggbb values ever leave this class, so the result is safe to place in a style attribute.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class palette {
    /** @var float Smallest accepted contrast ratio between text and box background (WCAG AA). */
    public const MIN_CONTRAST = 4.5;

    /** @var string Palette used when the setting is missing or unknown. */
    public const DEFAULT = 'default';

    /** @var string The weakest text colour used in any palette, which overrides must stay readable against. */
    public const REFERENCE_INK = '#3e4c59';

    /** @var float Share of the background brightness kept when a border is derived from an overridden background. */
    private const BORDER_FACTOR = 0.55;

    /** @var string[] Box keys, in report order, mapped to the stem of their CSS custom properties. */
    public const BOXES = [
        'header' => 'header',
        'activities' => 'blue',
        'resources' => 'green',
        'grade' => 'amber',
        'time' => 'purple',
        'courses' => 'teal',
        'grades' => 'rose',
    ];

    /**
     * The ready-made palettes.
     *
     * Each has the text colours and a [background, border] pair per box key.
     *
     * @return array[] Palette key => ['ink' => string, 'soft' => string, 'boxes' => [box => [bg, border]]].
     */
    public static function presets(): array {
        return [
            'default' => [
                'ink' => '#1f2933', 'soft' => '#3e4c59',
                'boxes' => [
                    'header' => ['#e4e7eb', '#9aa5b1'], 'activities' => ['#dbeafe', '#3b82f6'],
                    'resources' => ['#dcfce7', '#22a45d'], 'grade' => ['#fef3c7', '#d99706'],
                    'time' => ['#ede9fe', '#8b5cf6'], 'courses' => ['#ccfbf1', '#14b8a6'],
                    'grades' => ['#ffe4e6', '#e11d48'],
                ],
            ],
            'highcontrast' => [
                'ink' => '#000000', 'soft' => '#000000',
                'boxes' => [
                    'header' => ['#f2f2f2', '#000000'], 'activities' => ['#eef5ff', '#0b3d91'],
                    'resources' => ['#effcf3', '#0b6b2f'], 'grade' => ['#fff9e6', '#8a5a00'],
                    'time' => ['#f5f2ff', '#5b21b6'], 'courses' => ['#eafffb', '#00595e'],
                    'grades' => ['#fff0f2', '#9f1239'],
                ],
            ],
            'greyscale' => [
                'ink' => '#1f2933', 'soft' => '#3e4c59',
                'boxes' => [
                    'header' => ['#d9d9d9', '#7a7a7a'], 'activities' => ['#e6e6e6', '#555555'],
                    'resources' => ['#f0f0f0', '#6b6b6b'], 'grade' => ['#fafafa', '#808080'],
                    'time' => ['#dedede', '#4d4d4d'], 'courses' => ['#ececec', '#666666'],
                    'grades' => ['#f5f5f5', '#5c5c5c'],
                ],
            ],
            'earth' => [
                'ink' => '#1f2933', 'soft' => '#3e4c59',
                'boxes' => [
                    'header' => ['#ebe5de', '#a39382'], 'activities' => ['#e1e8f0', '#5d7a99'],
                    'resources' => ['#e6ecd3', '#7d8f3c'], 'grade' => ['#f6e8c8', '#b78a1d'],
                    'time' => ['#eadfe9', '#8a5a86'], 'courses' => ['#d9ece4', '#3f8a74'],
                    'grades' => ['#f6dcd3', '#b5533c'],
                ],
            ],
        ];
    }

    /**
     * Palette choices for the admin setting.
     *
     * @return string[] Palette key => display name.
     */
    public static function choices(): array {
        $choices = [];
        foreach (array_keys(self::presets()) as $key) {
            $choices[$key] = get_string('palette_' . $key, 'report_studentsummary');
        }
        return $choices;
    }

    /**
     * Normalise a colour to lower-case #rrggbb.
     *
     * @param string $value A colour such as "#ABC", "abc", "#aabbcc" or an empty string.
     * @return string|null "#rrggbb", "" for an empty value, or null if it is not a hex colour.
     */
    public static function normalise_hex(string $value): ?string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (!preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $value, $matches)) {
            return null;
        }
        $hex = strtolower($matches[1]);
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return '#' . $hex;
    }

    /**
     * WCAG contrast ratio between two #rrggbb colours.
     *
     * @param string $first A #rrggbb colour.
     * @param string $second A #rrggbb colour.
     * @return float From 1 (identical) to 21 (black on white).
     */
    public static function contrast(string $first, string $second): float {
        $one = self::luminance($first);
        $two = self::luminance($second);
        return (max($one, $two) + 0.05) / (min($one, $two) + 0.05);
    }

    /**
     * Relative luminance of a #rrggbb colour.
     *
     * @param string $hex A #rrggbb colour.
     * @return float 0 (black) to 1 (white).
     */
    private static function luminance(string $hex): float {
        $channels = [];
        foreach ([1, 3, 5] as $offset) {
            $value = hexdec(substr($hex, $offset, 2)) / 255;
            $channels[] = $value <= 0.03928 ? $value / 12.92 : pow(($value + 0.055) / 1.055, 2.4);
        }
        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * Darken a #rrggbb colour by scaling each channel.
     *
     * @param string $hex A #rrggbb colour.
     * @param float $factor 1 keeps the colour, 0 gives black.
     * @return string A #rrggbb colour.
     */
    public static function darken(string $hex, float $factor): string {
        $out = '#';
        foreach ([1, 3, 5] as $offset) {
            $out .= sprintf('%02x', (int)round(hexdec(substr($hex, $offset, 2)) * $factor));
        }
        return $out;
    }

    /**
     * Whether text in the given colours stays readable on a background.
     *
     * @param string $background A #rrggbb colour.
     * @param string[] $inks Text colours that will be drawn on it.
     * @return bool
     */
    public static function is_readable(string $background, array $inks): bool {
        foreach ($inks as $ink) {
            if (self::contrast($ink, $background) < self::MIN_CONTRAST) {
                return false;
            }
        }
        return true;
    }

    /**
     * The colours in force: the chosen palette with valid, readable per-box overrides applied.
     *
     * An override that is not a hex colour, or is too dark for the text, is ignored (the settings page refuses
     * such values, but configuration can also be written directly).
     *
     * @return array ['ink' => string, 'soft' => string, 'boxes' => [box => [background, border]]]
     */
    public static function colours(): array {
        $presets = self::presets();
        $key = (string)get_config('report_studentsummary', 'palette');
        $preset = $presets[$key] ?? $presets[self::DEFAULT];
        foreach (array_keys(self::BOXES) as $box) {
            $override = self::normalise_hex((string)get_config('report_studentsummary', 'colour_' . $box));
            if ($override && self::is_readable($override, [$preset['ink'], $preset['soft'], self::REFERENCE_INK])) {
                $preset['boxes'][$box] = [$override, self::darken($override, self::BORDER_FACTOR)];
            }
        }
        return $preset;
    }

    /**
     * The colours as CSS custom properties for the style attribute of the report wrapper.
     *
     * @return string For example "--ss-ink:#1f2933;--ss-blue-bg:#dbeafe;...".
     */
    public static function style(): string {
        $colours = self::colours();
        [$red, $green, $blue] = array_map(fn($offset) => hexdec(substr($colours['ink'], $offset, 2)), [1, 3, 5]);
        $declarations = [
            '--ss-ink:' . $colours['ink'],
            '--ss-ink-soft:' . $colours['soft'],
            '--ss-bar:' . $colours['ink'],
            "--ss-bar-bg:rgba($red,$green,$blue,0.15)",
        ];
        foreach (self::BOXES as $box => $stem) {
            [$background, $border] = $colours['boxes'][$box];
            $declarations[] = "--ss-$stem-bg:$background";
            $declarations[] = "--ss-$stem-border:$border";
        }
        return implode(';', $declarations) . ';';
    }
}
