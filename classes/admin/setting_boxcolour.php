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
 * The per-box colour admin setting.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\admin;

use report_studentsummary\local\palette;

/**
 * A box colour: its background or its text. Empty means use the palette; otherwise a #rrggbb colour.
 *
 * The background and the text colour of a box are checked together, so a dark background is accepted once a light
 * text colour is chosen for the box (and the other way round).
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_boxcolour extends \admin_setting_configcolourpicker {
    /** @var string Box key from {@see palette::BOXES}. */
    private string $box;

    /** @var bool Whether this is the box's text colour rather than its background. */
    private bool $text;

    /**
     * Constructor.
     *
     * @param string $box Box key from {@see palette::BOXES}.
     * @param bool $text True for the box's text colour, false for its background.
     */
    public function __construct(string $box, bool $text = false) {
        $this->box = $box;
        $this->text = $text;
        $prefix = $text ? palette::TEXT_PREFIX : 'colour_';
        parent::__construct(
            'report_studentsummary/' . $prefix . $box,
            get_string($prefix . $box, 'report_studentsummary'),
            get_string($text ? 'textcolour_desc' : 'colour_desc', 'report_studentsummary', palette::MIN_CONTRAST),
            '',
            null,
            false
        );
    }

    /**
     * The value of another setting of this box: as submitted with this form if it was, otherwise as stored.
     *
     * @param string $name Setting name without the plugin.
     * @return string
     */
    private function other_value(string $name): string {
        $submitted = optional_param('s_report_studentsummary_' . $name, null, PARAM_RAW);
        return (string)($submitted ?? get_config('report_studentsummary', $name));
    }

    /**
     * Store the colour, refusing anything that is not hex or would make the text hard to read.
     *
     * @param string $data Submitted value.
     * @return string Empty on success, otherwise an error message.
     */
    public function write_setting($data) {
        $hex = palette::normalise_hex((string)$data);
        if ($hex === null) {
            return get_string('colourinvalid', 'report_studentsummary');
        }
        if ($hex !== '') {
            $preset = palette::preset($this->other_value('palette') ?: null);
            if ($this->text) {
                $background = palette::normalise_hex($this->other_value('colour_' . $this->box)) ?: $preset['boxes'][$this->box][0];
                $text = $hex;
            } else {
                $background = $hex;
                $text = palette::normalise_hex($this->other_value(palette::TEXT_PREFIX . $this->box)) ?: null;
            }
            if (!palette::box_is_readable($background, $text, $preset)) {
                $ink = $text ?? palette::REFERENCE_INK;
                return get_string($this->text ? 'textcolourlowcontrast' : 'colourlowcontrast', 'report_studentsummary', [
                    'ratio' => format_float(palette::contrast($ink, $background), 1),
                    'min' => palette::MIN_CONTRAST,
                ]);
            }
        }
        return $this->config_write($this->name, $hex) ? '' : get_string('errorsetting', 'admin');
    }
}
