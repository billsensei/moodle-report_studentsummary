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
 * Tests for the privacy provider.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_studentsummary\privacy;

/**
 * Tests for the privacy provider.
 *
 * @package    report_studentsummary
 * @copyright  2026 Bill
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_studentsummary\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * The plugin stores nothing, so it declares itself a null provider with a real reason string.
     */
    public function test_null_provider(): void {
        $this->assertContains(\core_privacy\local\metadata\null_provider::class, class_implements(provider::class));
        $reason = provider::get_reason();
        $this->assertTrue(get_string_manager()->string_exists($reason, 'report_studentsummary'));
    }

    /**
     * Moodle's privacy manager accepts the plugin as compliant and finds the reason string.
     *
     * This fails if the plugin ever stores user data (a table, preference or persistent cache) without
     * switching to a full provider, because a null provider would then no longer be enough.
     */
    public function test_component_is_compliant(): void {
        $manager = new \core_privacy\manager();
        $this->assertTrue($manager->component_is_compliant('report_studentsummary'));
        $key = $manager->get_null_provider_reason('report_studentsummary');
        $this->assertSame('privacy:metadata', $key);
        $this->assertNotEmpty(get_string($key, 'report_studentsummary'));
    }
}
