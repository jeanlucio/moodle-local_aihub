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
 * Usage log export for local_aihub.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * Exports a user's full AI usage log via Moodle's dataformat API (CSV, Excel, …).
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export {
    /** @var string[] Download formats offered to the user. */
    const FORMATS = ['csv', 'excel'];

    /**
     * Builds the localized columns and the per-request rows for a user's export.
     *
     * @param int $userid The user whose log is exported.
     * @return array A two-element list with the columns and the data rows.
     */
    public static function build(int $userid): array {
        $columns = [
            get_string('mykeys_log_component', 'local_aihub'),
            get_string('mykeys_log_action', 'local_aihub'),
            get_string('mykeys_log_provider', 'local_aihub'),
            get_string('mykeys_log_model', 'local_aihub'),
            get_string('mykeys_log_date', 'local_aihub'),
        ];

        $datetimeformat = get_string('strftimedatetime', 'core_langconfig');
        $rows = [];
        foreach (usage_log::get_all_for_user($userid) as $record) {
            $rows[] = [
                $record->component,
                (string) ($record->description ?? ''),
                $record->provider,
                (string) ($record->model ?? ''),
                userdate($record->timecreated, $datetimeformat),
            ];
        }

        return [$columns, $rows];
    }

    /**
     * Streams the user's complete usage log as a downloadable file and exits.
     *
     * @param int $userid The user whose log is exported.
     * @param string $format A dataformat identifier from {@see self::FORMATS}.
     * @return void
     */
    public static function download(int $userid, string $format): void {
        [$columns, $rows] = self::build($userid);
        $filename = 'aihub_usage_' . $userid . '_' . date('Ymd');
        \core\dataformat::download_data($filename, $format, $columns, $rows);
        die();
    }
}
