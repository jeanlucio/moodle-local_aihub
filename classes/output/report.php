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
 * Renderable for the site-keys AI usage report.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\output;

use local_aihub\local\usage_log;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Builds the admin report of AI requests served by the site-wide keys.
 *
 * Lists generation requests across all users that resolved to a site key, so an
 * administrator can audit and export the consumption of the shared quota.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report implements renderable, templatable {
    /** @var array<string, string> Font Awesome icon class per provider display name. */
    private const PROVIDER_ICONS = [
        'DeepSeek' => 'fa-search',
        'Gemini'   => 'fa-google',
        'Groq'     => 'fa-bolt',
        'OpenAI'   => 'fa-plug',
    ];

    /**
     * Exports the Mustache context for the report page.
     *
     * @param renderer_base $output The active renderer.
     * @return array The template context.
     */
    public function export_for_template(renderer_base $output): array {
        $rows = $this->log_rows();
        return [
            'intro'              => get_string('report_intro', 'local_aihub'),
            'empty'              => get_string('report_empty', 'local_aihub'),
            'userlabel'          => get_string('report_user', 'local_aihub'),
            'componentlabel'     => get_string('mykeys_log_component', 'local_aihub'),
            'actionlabel'        => get_string('mykeys_log_action', 'local_aihub'),
            'providerlabel'      => get_string('mykeys_log_provider', 'local_aihub'),
            'modellabel'         => get_string('mykeys_log_model', 'local_aihub'),
            'datelabel'          => get_string('mykeys_log_date', 'local_aihub'),
            'rows'               => $rows,
            'hasrows'            => !empty($rows),
            'downloadcsvurl'     => (new moodle_url(
                '/local/aihub/report.php',
                ['download' => 'csv']
            ))->out(false),
            'downloadexcelurl'   => (new moodle_url(
                '/local/aihub/report.php',
                ['download' => 'excel']
            ))->out(false),
            'downloadcsvlabel'   => get_string('download_csv', 'local_aihub'),
            'downloadexcellabel' => get_string('download_excel', 'local_aihub'),
        ];
    }

    /**
     * Builds the recent site-keys usage rows.
     *
     * @return array[]
     */
    private function log_rows(): array {
        $records = usage_log::get_recent_site();
        $names = usage_log::user_fullnames($records);

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'user'         => $names[(int) $record->userid] ?? (string) $record->userid,
                'component'    => $record->component,
                'description'  => (string) ($record->description ?? ''),
                'provider'     => $record->provider,
                'providericon' => self::PROVIDER_ICONS[$record->provider] ?? 'fa-cog',
                'model'        => (string) ($record->model ?? ''),
                'date'         => userdate(
                    $record->timecreated,
                    get_string('strftimedatetimeshort', 'core_langconfig')
                ),
            ];
        }
        return $rows;
    }
}
