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
 * Tests for the "My AI keys" page renderable.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\output;

use local_aihub\local\keys;
use local_aihub\local\usage_log;

/**
 * Tests for {@see mykeys}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_aihub\output\mykeys
 */
final class mykeys_test extends \advanced_testcase {
    /**
     * The provider rows reflect each provider's personal-key status, with no key values.
     *
     * @covers ::export_for_template
     * @covers ::provider_rows
     * @covers ::__construct
     * @return void
     */
    public function test_export_for_template_provider_status(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        keys::save_user_key(keys::PROVIDER_GEMINI, 'personal-gemini');

        $output = $PAGE->get_renderer('core');
        $context = (new mykeys((int) $user->id))->export_for_template($output);

        $providers = [];
        foreach ($context['providers'] as $row) {
            $providers[$row['name']] = $row;
        }

        $this->assertTrue($providers[keys::PROVIDER_GEMINI]['isset']);
        $this->assertFalse($providers[keys::PROVIDER_DEEPSEEK]['isset']);
        $this->assertSame('key_' . keys::PROVIDER_DEEPSEEK, $providers[keys::PROVIDER_DEEPSEEK]['fieldkey']);
        $this->assertSame('remove_' . keys::PROVIDER_DEEPSEEK, $providers[keys::PROVIDER_DEEPSEEK]['fieldremove']);

        // No key value is ever placed in the context, only the configured/not-configured status.
        foreach ($context['providers'] as $row) {
            $this->assertArrayNotHasKey('key', $row);
            $this->assertArrayNotHasKey('value', $row);
        }
    }

    /**
     * The recent usage rows carry the correct provider icon, including a fallback.
     *
     * @covers ::export_for_template
     * @covers ::log_rows
     * @return void
     */
    public function test_export_for_template_log_rows_and_icons(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        usage_log::record((int) $user->id, 'local_aiassess', 'Forum review', 'DeepSeek', 'deepseek-v4-flash', true);
        usage_log::record((int) $user->id, 'local_aiassess', 'Unknown source', 'SomeFutureProvider', '', true);

        $output = $PAGE->get_renderer('core');
        $context = (new mykeys((int) $user->id))->export_for_template($output);

        $this->assertTrue($context['loghasrows']);
        $icons = array_column($context['logrows'], 'providericon', 'provider');
        $this->assertSame('fa-search', $icons['DeepSeek']);
        $this->assertSame('fa-cog', $icons['SomeFutureProvider']);
    }
}
