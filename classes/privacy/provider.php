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
 * Privacy provider for local_aihub.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Implements the Moodle Privacy API for the AI Hub.
 *
 * Personal data lives in the per-user usage log and in user preferences (BYOK
 * keys, endpoint, model). Prompts are also transmitted to external providers.
 * The data is associated with the user's own context.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /** @var string Database table backing the usage log. */
    private const TABLE = 'local_aihub_log';

    /**
     * Returns metadata about the personal data stored by this plugin.
     *
     * @param collection $collection The initialised collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_aihub_log', [
            'userid'      => 'privacy:metadata:logtable:userid',
            'component'   => 'privacy:metadata:logtable:component',
            'description' => 'privacy:metadata:logtable:description',
            'provider'    => 'privacy:metadata:logtable:provider',
            'model'       => 'privacy:metadata:logtable:model',
            'keysource'   => 'privacy:metadata:logtable:keysource',
            'timecreated' => 'privacy:metadata:logtable:timecreated',
        ], 'privacy:metadata:logtable');

        $collection->add_user_preference('local_aihub_deepseek_key', 'privacy:metadata:preference:deepseek_key');
        $collection->add_user_preference('local_aihub_gemini_key', 'privacy:metadata:preference:gemini_key');
        $collection->add_user_preference('local_aihub_groq_key', 'privacy:metadata:preference:groq_key');
        $collection->add_user_preference('local_aihub_openai_key', 'privacy:metadata:preference:openai_key');
        $collection->add_user_preference('local_aihub_openai_url', 'privacy:metadata:preference:openai_url');
        $collection->add_user_preference('local_aihub_openai_model', 'privacy:metadata:preference:openai_model');

        $collection->add_external_location_link(
            'deepseek',
            ['prompt' => 'privacy:metadata:external:prompt'],
            'privacy:metadata:external:deepseek'
        );
        $collection->add_external_location_link(
            'google_gemini',
            ['prompt' => 'privacy:metadata:external:prompt'],
            'privacy:metadata:external:gemini'
        );
        $collection->add_external_location_link(
            'groq',
            ['prompt' => 'privacy:metadata:external:prompt'],
            'privacy:metadata:external:groq'
        );
        $collection->add_external_location_link(
            'openai_compatible',
            ['prompt' => 'privacy:metadata:external:prompt'],
            'privacy:metadata:external:openai'
        );

        return $collection;
    }

    /**
     * Returns the contexts that contain personal data for the given user.
     *
     * @param int $userid The user to search.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists(self::TABLE, ['userid' => $userid])) {
            $contextlist->add_user_context($userid);
        }
        return $contextlist;
    }

    /**
     * Returns the users who have personal data in the given context.
     *
     * @param userlist $userlist The userlist to add users to.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        if ($DB->record_exists(self::TABLE, ['userid' => $context->instanceid])) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Exports the usage log for the approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_USER || $context->instanceid != $userid) {
                continue;
            }

            $records = $DB->get_records(self::TABLE, ['userid' => $userid], 'timecreated ASC');
            if (!$records) {
                continue;
            }

            $rows = [];
            foreach ($records as $record) {
                $rows[] = [
                    'component'   => $record->component,
                    'description' => $record->description,
                    'provider'    => $record->provider,
                    'model'       => $record->model,
                    'keysource'   => $record->keysource,
                    'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('mykeys_log_heading', 'local_aihub')],
                (object) ['logs' => $rows]
            );
        }
    }

    /**
     * Exports the user's stored preferences. Key values are redacted.
     *
     * @param int $userid The user to export preferences for.
     * @return void
     */
    public static function export_user_preferences(int $userid): void {
        $redacted = get_string('privacy:redacted', 'local_aihub');

        $secrets = [
            'local_aihub_deepseek_key' => 'privacy:metadata:preference:deepseek_key',
            'local_aihub_gemini_key' => 'privacy:metadata:preference:gemini_key',
            'local_aihub_groq_key'   => 'privacy:metadata:preference:groq_key',
            'local_aihub_openai_key' => 'privacy:metadata:preference:openai_key',
        ];
        foreach ($secrets as $name => $description) {
            if (get_user_preferences($name, null, $userid) !== null) {
                writer::export_user_preference('local_aihub', $name, $redacted, get_string($description, 'local_aihub'));
            }
        }

        $plain = [
            'local_aihub_openai_url'   => 'privacy:metadata:preference:openai_url',
            'local_aihub_openai_model' => 'privacy:metadata:preference:openai_model',
        ];
        foreach ($plain as $name => $description) {
            $value = get_user_preferences($name, null, $userid);
            if ($value !== null) {
                writer::export_user_preference('local_aihub', $name, $value, get_string($description, 'local_aihub'));
            }
        }
    }

    /**
     * Deletes all usage-log data in the given context.
     *
     * @param context $context The context to delete in.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        $DB->delete_records(self::TABLE, ['userid' => $context->instanceid]);
    }

    /**
     * Deletes usage-log data for one user across the approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to delete in.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $userid) {
                $DB->delete_records(self::TABLE, ['userid' => $userid]);
            }
        }
    }

    /**
     * Deletes usage-log data for the approved users in the given context.
     *
     * @param approved_userlist $userlist The approved users to delete.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            if ($userid == $context->instanceid) {
                $DB->delete_records(self::TABLE, ['userid' => $userid]);
            }
        }
    }
}
