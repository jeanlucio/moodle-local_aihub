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
 * Usage log writer and reader for local_aihub.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * Records and retrieves AI generation requests in local_aihub_log.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usage_log {
    /** @var string Database table backing the usage log. */
    const TABLE = 'local_aihub_log';

    /**
     * Inserts a usage log entry.
     *
     * @param int $userid The user who requested the generation.
     * @param string $component Frankenstyle of the calling plugin (may be empty).
     * @param string $description Short label of what was generated (may be empty).
     * @param string $provider Provider display name (Gemini, Groq, OpenAI).
     * @param string $model Model identifier used (may be empty).
     * @param bool $success Whether the generation succeeded.
     * @param string $keysource Key tier that served the request: 'personal', 'site' or empty.
     * @return int The inserted record id.
     */
    public static function record(
        int $userid,
        string $component,
        string $description,
        string $provider,
        string $model,
        bool $success,
        string $keysource = ''
    ): int {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->component = $component;
        $record->description = $description !== '' ? $description : null;
        $record->provider = $provider;
        $record->model = $model !== '' ? $model : null;
        $record->keysource = $keysource !== '' ? $keysource : null;
        $record->success = $success ? 1 : 0;
        $record->timecreated = time();

        return (int) $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Returns the most recent log entries for a user, newest first.
     *
     * @param int $userid The user whose entries are fetched.
     * @param int $limit Maximum number of rows to return.
     * @return array Array of record objects.
     */
    public static function get_recent_for_user(int $userid, int $limit = 15): array {
        global $DB;

        return $DB->get_records(
            self::TABLE,
            ['userid' => $userid],
            'timecreated DESC',
            'id, component, description, provider, model, keysource, success, timecreated',
            0,
            $limit
        );
    }

    /**
     * Returns all log entries for a user, newest first (for export).
     *
     * @param int $userid The user whose entries are fetched.
     * @return array Array of record objects.
     */
    public static function get_all_for_user(int $userid): array {
        global $DB;

        return $DB->get_records(
            self::TABLE,
            ['userid' => $userid],
            'timecreated DESC',
            'id, component, description, provider, model, success, timecreated'
        );
    }

    /**
     * Returns the most recent requests served by the site keys, across all users.
     *
     * @param int $limit Maximum number of rows to return.
     * @return array Array of record objects.
     */
    public static function get_recent_site(int $limit = 50): array {
        global $DB;

        return $DB->get_records(
            self::TABLE,
            ['keysource' => 'site'],
            'timecreated DESC',
            'id, userid, component, description, provider, model, success, timecreated',
            0,
            $limit
        );
    }

    /**
     * Returns every request served by the site keys, across all users (for export).
     *
     * @return array Array of record objects.
     */
    public static function get_all_site(): array {
        global $DB;

        return $DB->get_records(
            self::TABLE,
            ['keysource' => 'site'],
            'timecreated DESC',
            'id, userid, component, description, provider, model, success, timecreated'
        );
    }

    /**
     * Resolves a userid-to-fullname map for a set of log records in one query.
     *
     * @param array $records Log record objects carrying a userid property.
     * @return array<int, string> Map of userid to formatted full name.
     */
    public static function user_fullnames(array $records): array {
        global $DB;

        $userids = array_unique(array_map(static fn($record) => (int) $record->userid, $records));
        if (empty($userids)) {
            return [];
        }

        $namefields = \core_user\fields::get_name_fields();
        $users = $DB->get_records_list('user', 'id', $userids, '', 'id,' . implode(',', $namefields));

        $names = [];
        foreach ($users as $user) {
            $names[(int) $user->id] = fullname($user);
        }
        return $names;
    }
}
