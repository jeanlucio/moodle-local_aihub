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
 * Tests for the usage log export.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * Tests for {@see export}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_aihub\local\export
 */
final class export_test extends \advanced_testcase {
    /**
     * The export includes every row for the user, with all columns.
     *
     * @covers ::build
     * @return void
     */
    public function test_build(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        for ($i = 0; $i < 20; $i++) {
            usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true);
        }
        usage_log::record((int) $other->id, 'local_aiassess', 'Forum review', 'Groq', 'llama', true);

        [$columns, $rows] = export::build((int) $user->id);

        // Five columns, and every one of the user's rows (not capped at 15).
        $this->assertCount(5, $columns);
        $this->assertCount(20, $rows);
        $this->assertCount(5, $rows[0]);
        $this->assertSame('local_playergames', $rows[0][0]);
        $this->assertSame('Concepts: test', $rows[0][1]);
        $this->assertSame('Gemini', $rows[0][2]);
    }

    /**
     * The site export covers every site-key row across users, with the user column.
     *
     * @covers ::build_site
     * @return void
     */
    public function test_build_site(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true, 'site');
        usage_log::record((int) $other->id, 'local_aiassess', 'Forum review', 'Groq', 'llama', true, 'site');
        // Personal-key usage is not part of the site report.
        usage_log::record((int) $user->id, 'report_unlocker', 'Restriction help', 'OpenAI', 'gpt', true, 'personal');

        [$columns, $rows] = export::build_site();

        // Six columns (user first) and only the two site-key rows.
        $this->assertCount(6, $columns);
        $this->assertCount(2, $rows);
        $this->assertCount(6, $rows[0]);

        $names = array_column($rows, 0);
        $this->assertContains(fullname($user), $names);
        $this->assertContains(fullname($other), $names);
    }
}
