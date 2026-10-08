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
 * @covers     \local_aihub\local\export
 */
final class export_test extends \advanced_testcase {
    /**
     * The export includes every row for the user, with all columns.
     *
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

        // Eight columns, and every one of the user's rows (not capped at 15).
        $this->assertCount(8, $columns);
        $this->assertCount(20, $rows);
        $this->assertCount(8, $rows[0]);
        $this->assertSame('local_playergames', $rows[0][0]);
        $this->assertSame('Concepts: test', $rows[0][1]);
        $this->assertSame('Gemini', $rows[0][2]);
        $this->assertSame(get_string('report_succeeded', 'local_aihub'), $rows[0][5]);
        $this->assertSame('', $rows[0][6]);
    }

    /**
     * A failed attempt exports its outcome and reason rather than looking like a
     * successful one with missing data.
     *
     * @return void
     */
    public function test_build_exports_a_failure(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        usage_log::record((int) $user->id, 'mod_codereview', 'Review', 'Gemini', 'flash', false, 'site', 'Gemini: down');

        [, $rows] = export::build((int) $user->id);

        $this->assertSame(get_string('report_failed', 'local_aihub'), $rows[0][5]);
        $this->assertSame('Gemini: down', $rows[0][6]);
    }

    /**
     * The site export covers every site-key row across users, with the user column.
     *
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
        $rows = iterator_to_array($rows, false);

        // Eight columns (user first) and only the two site-key rows.
        $this->assertCount(8, $columns);
        $this->assertCount(2, $rows);
        $this->assertCount(8, $rows[0]);

        $names = array_column($rows, 0);
        $this->assertContains(fullname($user), $names);
        $this->assertContains(fullname($other), $names);
    }

    /**
     * The site export is a stream, not an array, so a log with a year of rows is formatted
     * one row at a time while the download is written.
     *
     * @return void
     */
    public function test_build_site_is_a_stream(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true, 'site');

        [, $rows] = export::build_site();

        $this->assertInstanceOf(\Traversable::class, $rows);
        $this->assertNotInstanceOf(\Countable::class, $rows);
    }

    /**
     * The download carries the origin of the key too, so what was spent on the user's own keys
     * can be told apart from what the site paid for.
     *
     * @return void
     */
    public function test_build_says_whose_key_was_used(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        usage_log::record((int) $user->id, 'mod_x', 'mine', 'Gemini', 'flash', true, 'personal');
        usage_log::record((int) $user->id, 'mod_x', 'theirs', 'Groq', 'llama', true, 'site');
        usage_log::record((int) $user->id, 'mod_x', 'old', 'Groq', 'llama', true);

        [$columns, $rows] = export::build((int) $user->id);

        $position = array_search(get_string('mykeys_log_keysource', 'local_aihub'), $columns, true);
        $this->assertNotFalse($position, 'the key column is part of the export');
        $byaction = array_column($rows, $position, 1);
        $this->assertSame(get_string('keysource_personal', 'local_aihub'), $byaction['mine']);
        $this->assertSame(get_string('keysource_site', 'local_aihub'), $byaction['theirs']);
        $this->assertSame('', $byaction['old']);
    }
}
