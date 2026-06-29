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
     * @return int The inserted record id.
     */
    public static function record(
        int $userid,
        string $component,
        string $description,
        string $provider,
        string $model,
        bool $success
    ): int {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->component = $component;
        $record->description = $description !== '' ? $description : null;
        $record->provider = $provider;
        $record->model = $model !== '' ? $model : null;
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
            'id, component, description, provider, model, success, timecreated',
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
}
