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
 * Tests for the usage log writer and reader.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * Tests for {@see usage_log}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_aihub\local\usage_log
 */
final class usage_log_test extends \advanced_testcase {
    /**
     * A record is inserted with the calling component and an empty model is nulled.
     *
     * @covers ::record
     * @return void
     */
    public function test_record(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $id = usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', '', true, 'site');

        $row = $DB->get_record(usage_log::TABLE, ['id' => $id], '*', MUST_EXIST);
        $this->assertSame((int) $user->id, (int) $row->userid);
        $this->assertSame('local_playergames', $row->component);
        $this->assertSame('Concepts: test', $row->description);
        $this->assertSame('Gemini', $row->provider);
        $this->assertNull($row->model);
        $this->assertSame('site', $row->keysource);
        $this->assertSame(1, (int) $row->success);
        $this->assertNull($row->errormessage);
    }

    /**
     * A failed attempt keeps the reason, so a dead key can be told apart from an
     * exhausted quota without guessing.
     *
     * @covers ::record
     * @return void
     */
    public function test_record_keeps_the_failure_reason(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $id = usage_log::record(
            (int) $user->id,
            'local_playergames',
            'Concepts: test',
            'DeepSeek',
            'deepseek-v4-flash',
            false,
            'site',
            'DeepSeek: model not found'
        );

        $row = $DB->get_record(usage_log::TABLE, ['id' => $id], '*', MUST_EXIST);
        $this->assertSame(0, (int) $row->success);
        $this->assertSame('DeepSeek: model not found', $row->errormessage);
    }

    /**
     * The failures-only filter narrows both readers without touching the default.
     *
     * @covers ::get_recent_site
     * @covers ::get_recent_for_user
     * @return void
     */
    public function test_only_failures_filter(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        usage_log::record((int) $user->id, 'local_playergames', 'ok', 'Groq', 'llama', true, 'site');
        usage_log::record((int) $user->id, 'local_playergames', 'bad', 'Gemini', 'flash', false, 'site', 'Gemini: down');

        $this->assertCount(2, usage_log::get_recent_site());
        $this->assertCount(2, usage_log::get_recent_for_user((int) $user->id));

        $failures = usage_log::get_recent_site(50, true);
        $this->assertCount(1, $failures);
        $this->assertSame('Gemini', reset($failures)->provider);

        $userfailures = usage_log::get_recent_for_user((int) $user->id, 15, true);
        $this->assertCount(1, $userfailures);
        $this->assertSame('Gemini: down', reset($userfailures)->errormessage);
    }

    /**
     * The site readers return only site-key rows, across all users, newest first.
     *
     * @covers ::get_recent_site
     * @covers ::get_all_site
     * @covers ::user_fullnames
     * @return void
     */
    public function test_site_readers(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true, 'site');
        usage_log::record((int) $other->id, 'local_aiassess', 'Forum review', 'Groq', 'llama', true, 'site');
        // A personal-key row and an untagged row must be excluded.
        usage_log::record((int) $user->id, 'report_unlocker', 'Restriction help', 'OpenAI', 'gpt', true, 'personal');
        usage_log::record((int) $user->id, 'local_playergames', 'Legacy row', 'Gemini', 'flash', true);

        $rows = usage_log::get_all_site();
        $this->assertCount(2, $rows);

        $components = array_column(array_values($rows), 'component');
        $this->assertContains('local_playergames', $components);
        $this->assertContains('local_aiassess', $components);
        $this->assertNotContains('report_unlocker', $components);

        $recent = usage_log::get_recent_site(1);
        $this->assertCount(1, $recent);

        $names = usage_log::user_fullnames($rows);
        $this->assertArrayHasKey((int) $user->id, $names);
        $this->assertArrayHasKey((int) $other->id, $names);
        $this->assertSame(fullname($user), $names[(int) $user->id]);
    }

    /**
     * The user column names a person, the system, or an id that no longer resolves,
     * but never a bare zero.
     *
     * @covers ::display_name
     * @return void
     */
    public function test_display_name(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $names = [(int) $user->id => fullname($user)];

        $this->assertSame(fullname($user), usage_log::display_name($names, (int) $user->id));
        $this->assertSame(get_string('report_systemuser', 'local_aihub'), usage_log::display_name($names, 0));
        $this->assertSame(
            get_string('report_unknownuser', 'local_aihub', 999999),
            usage_log::display_name($names, 999999)
        );
    }

    /**
     * Recent entries for a user come back newest first and exclude other users.
     *
     * @covers ::get_recent_for_user
     * @return void
     */
    public function test_get_recent_for_user(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        usage_log::record((int) $user->id, 'local_aiassess', 'Forum review', 'Groq', 'llama', true);
        usage_log::record((int) $user->id, 'report_unlocker', 'Restriction help', 'OpenAI', 'gpt-4o-mini', false);
        usage_log::record((int) $other->id, 'local_aiassess', 'Forum review', 'Gemini', 'flash', true);

        // Only this user's two rows come back; the other user's row is excluded.
        $rows = usage_log::get_recent_for_user((int) $user->id);
        $this->assertCount(2, $rows);

        $components = array_column(array_values($rows), 'component');
        $this->assertContains('local_aiassess', $components);
        $this->assertContains('report_unlocker', $components);
    }
}
