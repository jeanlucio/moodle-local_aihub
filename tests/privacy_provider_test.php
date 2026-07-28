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
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\privacy;

use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use local_aihub\local\keys;
use local_aihub\local\usage_log;

/**
 * Tests for {@see provider}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_aihub\privacy\provider
 */
final class privacy_provider_test extends \advanced_testcase {
    /**
     * Metadata declares the log table, the preferences and the external links.
     *
     * @return void
     */
    public function test_get_metadata(): void {
        $collection = provider::get_metadata(new collection('local_aihub'));
        $this->assertNotEmpty($collection->get_collection());
    }

    /**
     * The user's own context is returned when they have log rows.
     *
     * @return void
     */
    public function test_contexts_and_users(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true);

        $contextlist = provider::get_contexts_for_userid((int) $user->id);
        $contexts = $contextlist->get_contexts();
        $this->assertCount(1, $contexts);
        $this->assertEquals(context_user::instance($user->id)->id, reset($contexts)->id);

        $userlist = new \core_privacy\local\request\userlist(context_user::instance($user->id), 'local_aihub');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([(int) $user->id], $userlist->get_userids());
    }

    /**
     * Exporting writes the log rows and redacts stored key values.
     *
     * @return void
     */
    public function test_export(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true);
        keys::save_user_key(keys::PROVIDER_GEMINI, 'supersecretkey', (int) $user->id);
        keys::save_user_openai_model('gpt-4o', (int) $user->id);

        $context = context_user::instance($user->id);
        $approved = new approved_contextlist($user, 'local_aihub', [$context->id]);
        provider::export_user_data($approved);
        provider::export_user_preferences((int) $user->id);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data([get_string('mykeys_log_heading', 'local_aihub')]);
        $this->assertCount(1, $data->logs);
        $this->assertSame('local_playergames', $data->logs[0]['component']);
        $this->assertSame('Concepts: test', $data->logs[0]['description']);

        $prefs = $writer->get_user_preferences('local_aihub');
        // The key value must be redacted, never exported in clear.
        $this->assertNotSame('supersecretkey', $prefs->local_aihub_gemini_key->value);
        $this->assertSame('gpt-4o', $prefs->local_aihub_openai_model->value);
    }

    /**
     * Deleting for a user empties their log rows.
     *
     * @return void
     */
    public function test_delete_for_user(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true);
        usage_log::record((int) $other->id, 'local_aiassess', 'Forum review', 'Groq', 'llama', true);

        $context = context_user::instance($user->id);
        $approved = new approved_contextlist($user, 'local_aihub', [$context->id]);
        provider::delete_data_for_user($approved);

        $this->assertSame(0, $DB->count_records('local_aihub_log', ['userid' => $user->id]));
        $this->assertSame(1, $DB->count_records('local_aihub_log', ['userid' => $other->id]));
    }

    /**
     * Deleting a whole user context empties that user's rows and no one else's.
     *
     * @return void
     */
    public function test_delete_for_all_users_in_context(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true);
        usage_log::record((int) $other->id, 'local_aiassess', 'Forum review', 'Groq', 'llama', true);

        provider::delete_data_for_all_users_in_context(context_user::instance($user->id));

        $this->assertSame(0, $DB->count_records('local_aihub_log', ['userid' => $user->id]));
        $this->assertSame(1, $DB->count_records('local_aihub_log', ['userid' => $other->id]));
    }

    /**
     * A context that is not a user context is left alone. The log is keyed by user
     * and nothing else, so a course-level request must not empty the whole table.
     *
     * @return void
     */
    public function test_delete_ignores_a_non_user_context(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true);

        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));

        $this->assertSame(1, $DB->count_records('local_aihub_log'));
    }

    /**
     * Deleting an approved user list removes only the user the context belongs to,
     * even when the list names someone else as well.
     *
     * @return void
     */
    public function test_delete_for_users(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true);
        usage_log::record((int) $other->id, 'local_aiassess', 'Forum review', 'Groq', 'llama', true);

        $context = context_user::instance($user->id);
        $userlist = new approved_userlist($context, 'local_aihub', [$user->id, $other->id]);
        provider::delete_data_for_users($userlist);

        $this->assertSame(0, $DB->count_records('local_aihub_log', ['userid' => $user->id]));
        // The other user's rows live in their own context and are not this one's to drop.
        $this->assertSame(1, $DB->count_records('local_aihub_log', ['userid' => $other->id]));
    }

    /**
     * A user list in a non-user context deletes nothing.
     *
     * @return void
     */
    public function test_delete_for_users_ignores_a_non_user_context(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        usage_log::record((int) $user->id, 'local_playergames', 'Concepts: test', 'Gemini', 'flash', true);

        $userlist = new approved_userlist(\context_course::instance($course->id), 'local_aihub', [$user->id]);
        provider::delete_data_for_users($userlist);

        $this->assertSame(1, $DB->count_records('local_aihub_log'));
    }
}
