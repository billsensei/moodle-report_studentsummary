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
 * A box background colour: empty (use the palette) or a #rrggbb colour that keeps the text readable.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_boxcolour extends \admin_setting_configcolourpicker {
    /**
     * Constructor.
     *
     * @param string $box Box key from {@see palette::BOXES}.
     */
    public function __construct(string $box) {
        parent::__construct(
            'report_studentsummary/colour_' . $box,
            get_string('colour_' . $box, 'report_studentsummary'),
            get_string('colour_desc', 'report_studentsummary', palette::MIN_CONTRAST),
            '',
            null,
            false
        );
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
        if ($hex !== '' && !palette::is_readable($hex, [palette::REFERENCE_INK])) {
            return get_string('colourlowcontrast', 'report_studentsummary', [
                'ratio' => format_float(palette::contrast(palette::REFERENCE_INK, $hex), 1),
                'min' => palette::MIN_CONTRAST,
            ]);
        }
        return $this->config_write($this->name, $hex) ? '' : get_string('errorsetting', 'admin');
    }
}
