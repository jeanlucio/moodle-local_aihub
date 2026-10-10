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
     * @param string $errormessage Why the attempt failed, for a failed entry (may be empty).
     * @return int The inserted record id.
     */
    public static function record(
        int $userid,
        string $component,
        string $description,
        string $provider,
        string $model,
        bool $success,
        string $keysource = '',
        string $errormessage = ''
    ): int {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->component = \core_text::substr($component, 0, 100);
        $record->description = $description !== '' ? \core_text::substr($description, 0, 255) : null;
        $record->provider = \core_text::substr($provider, 0, 40);
        $record->model = $model !== '' ? \core_text::substr($model, 0, keys::MODEL_MAX_LENGTH) : null;
        $record->keysource = $keysource !== '' ? \core_text::substr($keysource, 0, 20) : null;
        $record->success = $success ? 1 : 0;
        $record->errormessage = $errormessage !== '' ? \core_text::substr($errormessage, 0, 255) : null;
        $record->timecreated = time();

        return (int) $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Returns the most recent log entries for a user, newest first.
     *
     * @param int $userid The user whose entries are fetched.
     * @param int $limit Maximum number of rows to return.
     * @param bool|null $success Restrict to failed (false) or successful (true) attempts; null for both.
     * @return array Array of record objects.
     */
    public static function get_recent_for_user(int $userid, int $limit = 15, ?bool $success = null): array {
        global $DB;

        $conditions = ['userid' => $userid];
        if ($success !== null) {
            $conditions['success'] = $success ? 1 : 0;
        }

        return $DB->get_records(
            self::TABLE,
            $conditions,
            'timecreated DESC',
            'id, component, description, provider, model, keysource, success, errormessage, timecreated',
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
            'id, component, description, provider, model, keysource, success, errormessage, timecreated'
        );
    }

    /**
     * Names the origin of the key that served a request, for the user's own history and download.
     *
     * Rows written before the origin was recorded have none, and say nothing rather than guess.
     *
     * @param string|null $keysource The stored origin: personal, site or empty.
     * @return string
     */
    public static function keysource_label(?string $keysource): string {
        if ($keysource === 'personal' || $keysource === 'site') {
            return get_string('keysource_' . $keysource, 'local_aihub');
        }

        return '';
    }

    /**
     * Returns the most recent requests served by the site keys, across all users.
     *
     * @param int $limit Maximum number of rows to return.
     * @param bool|null $success Restrict to failed (false) or successful (true) attempts; null for both.
     * @return array Array of record objects.
     */
    public static function get_recent_site(int $limit = 50, ?bool $success = null): array {
        global $DB;

        $conditions = ['keysource' => 'site'];
        if ($success !== null) {
            $conditions['success'] = $success ? 1 : 0;
        }

        return $DB->get_records(
            self::TABLE,
            $conditions,
            'timecreated DESC',
            'id, userid, component, description, provider, model, success, errormessage, timecreated',
            0,
            $limit
        );
    }

    /**
     * Returns every row logged against a site key, newest first, as a recordset.
     *
     * A recordset instead of an array: a year of log under heavy use is far more rows than
     * should be held in memory just to be written to a download. Each row carries the name
     * fields of its user, read by {@see self::row_user_name()}, so no second lookup is needed.
     *
     * The caller must close the recordset.
     *
     * @return \moodle_recordset
     */
    public static function site_recordset(): \moodle_recordset {
        global $DB;

        $namecolumns = [];
        foreach (\core_user\fields::get_name_fields() as $field) {
            $namecolumns[] = 'u.' . $field . ' AS user_' . $field;
        }

        $sql = "SELECT l.id, l.userid, l.component, l.description, l.provider, l.model, l.success,
                       l.errormessage, l.timecreated, " . implode(', ', $namecolumns) . "
                  FROM {" . self::TABLE . "} l
             LEFT JOIN {user} u ON u.id = l.userid
                 WHERE l.keysource = :keysource
              ORDER BY l.timecreated DESC, l.id DESC";

        return $DB->get_recordset_sql($sql, ['keysource' => 'site']);
    }

    /**
     * Returns the display name of the user a {@see self::site_recordset()} row belongs to.
     *
     * @param \stdClass $row A row from the site recordset.
     * @return string
     */
    public static function row_user_name(\stdClass $row): string {
        if ((int) $row->userid === 0) {
            return get_string('report_systemuser', 'local_aihub');
        }

        // A user row that no longer exists comes back with every name column empty.
        if (!isset($row->user_firstname) && !isset($row->user_lastname)) {
            return get_string('report_unknownuser', 'local_aihub', (int) $row->userid);
        }

        $user = new \stdClass();
        foreach (\core_user\fields::get_name_fields() as $field) {
            $user->{$field} = $row->{'user_' . $field} ?? '';
        }

        return fullname($user);
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

    /**
     * Resolves what to show in a user column for a logged request.
     *
     * Requests made from cron or a CLI script carry no user, and a bare zero in a
     * column headed "user" reads as a broken row rather than as what it is. A user
     * who no longer resolves keeps their id, which is the only thing left to trace.
     *
     * @param array $names Map of user id to full name, from {@see self::user_fullnames()}.
     * @param int $userid The user id stored on the record.
     * @return string
     */
    public static function display_name(array $names, int $userid): string {
        if ($userid === 0) {
            return get_string('report_systemuser', 'local_aihub');
        }

        return $names[$userid] ?? get_string('report_unknownuser', 'local_aihub', $userid);
    }
}
