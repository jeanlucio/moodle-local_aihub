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
 * Tests for the site-keys usage report renderable.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\output;

use local_aihub\local\usage_log;

/**
 * Tests for {@see report}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_aihub\output\report
 */
final class report_test extends \advanced_testcase {
    /**
     * Rows carry the requesting user's name and the correct provider icon, DeepSeek included.
     *
     * @covers ::export_for_template
     * @covers ::log_rows
     * @return void
     */
    public function test_export_for_template_rows_and_icons(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        usage_log::record((int) $user->id, 'local_aiassess', 'Forum review', 'DeepSeek', 'deepseek-v4-flash', true, 'site');
        // A personal-key row must never appear in the site-keys report.
        usage_log::record((int) $user->id, 'report_unlocker', 'Restriction help', 'Groq', '', true, 'personal');

        $output = $PAGE->get_renderer('core');
        $context = (new report())->export_for_template($output);

        $this->assertTrue($context['hasrows']);
        $this->assertCount(1, $context['rows']);
        $this->assertSame(fullname($user), $context['rows'][0]['user']);
        $this->assertSame('fa-search', $context['rows'][0]['providericon']);
    }

    /**
     * With no site-keys usage, the report reports an empty state.
     *
     * @covers ::export_for_template
     * @return void
     */
    public function test_export_for_template_empty_state(): void {
        global $PAGE;
        $this->resetAfterTest();

        $output = $PAGE->get_renderer('core');
        $context = (new report())->export_for_template($output);

        $this->assertFalse($context['hasrows']);
        $this->assertSame([], $context['rows']);
    }
}
