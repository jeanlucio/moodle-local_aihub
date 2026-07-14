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
 * Renderable for the self-service "My AI keys" page.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\output;

use local_aihub\local\keys;
use local_aihub\local\usage_log;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Builds the write-only key form and the recent usage history for one user.
 *
 * Key values are never placed in the template context: only a configured /
 * not-configured status is exposed, so a saved key cannot be read back (even
 * via "Log in as").
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mykeys implements renderable, templatable {
    /** @var int The user whose keys are being managed. */
    private int $userid;

    /** @var array<string, string> Font Awesome icon class per provider display name. */
    private const PROVIDER_ICONS = [
        'DeepSeek' => 'fa-search',
        'Gemini'   => 'fa-google',
        'Groq'     => 'fa-bolt',
        'OpenAI'   => 'fa-plug',
    ];

    /**
     * Constructor.
     *
     * @param int $userid The user whose keys and history are shown.
     */
    public function __construct(int $userid) {
        $this->userid = $userid;
    }

    /**
     * Exports the Mustache context for the page.
     *
     * @param renderer_base $output The active renderer.
     * @return array The template context.
     */
    public function export_for_template(renderer_base $output): array {
        $logrows = $this->log_rows();
        return [
            'intro'                => get_string('mykeys_intro', 'local_aihub'),
            'actionurl'            => (new moodle_url('/local/aihub/mykeys.php'))->out(false),
            'sesskey'              => sesskey(),
            'savelabel'            => get_string('mykeys_save', 'local_aihub'),
            'removelabel'          => get_string('mykeys_remove', 'local_aihub'),
            'replacehint'          => get_string('mykeys_replacehint', 'local_aihub'),
            'showhidelabel'        => get_string('mykeys_showhide', 'local_aihub'),
            'advancedlabel'        => get_string('mykeys_advanced', 'local_aihub'),
            'providers'            => $this->provider_rows(),
            'openaiurl'            => keys::get_personal_openai_url($this->userid),
            'openaiurllabel'       => get_string('settings_openai_baseurl', 'local_aihub'),
            'openaiurlplaceholder' => keys::get_openai_baseurl(),
            'openaimodel'          => keys::get_personal_openai_model($this->userid),
            'openaimodellabel'     => get_string('settings_openai_model', 'local_aihub'),
            'openaimodelplaceholder' => keys::get_openai_model(),
            'logheading'           => get_string('mykeys_log_heading', 'local_aihub'),
            'logempty'             => get_string('mykeys_log_empty', 'local_aihub'),
            'logactionlabel'       => get_string('mykeys_log_action', 'local_aihub'),
            'logproviderlabel'     => get_string('mykeys_log_provider', 'local_aihub'),
            'logmodellabel'        => get_string('mykeys_log_model', 'local_aihub'),
            'logdatelabel'         => get_string('mykeys_log_date', 'local_aihub'),
            'logrows'              => $logrows,
            'loghasrows'           => !empty($logrows),
            'downloadcsvurl'       => (new moodle_url(
                '/local/aihub/mykeys.php',
                ['download' => 'csv']
            ))->out(false),
            'downloadexcelurl'     => (new moodle_url(
                '/local/aihub/mykeys.php',
                ['download' => 'excel']
            ))->out(false),
            'downloadcsvlabel'     => get_string('download_csv', 'local_aihub'),
            'downloadexcellabel'   => get_string('download_excel', 'local_aihub'),
        ];
    }

    /**
     * Builds the per-provider status rows (no key values are included).
     *
     * @return array[]
     */
    private function provider_rows(): array {
        $labels = [
            keys::PROVIDER_GEMINI   => get_string('provider_gemini', 'local_aihub'),
            keys::PROVIDER_GROQ     => get_string('provider_groq', 'local_aihub'),
            keys::PROVIDER_DEEPSEEK => get_string('provider_deepseek', 'local_aihub'),
            keys::PROVIDER_OPENAI   => get_string('provider_openai', 'local_aihub'),
        ];

        $rows = [];
        foreach (keys::providers() as $provider) {
            $isset = keys::get_personal_key($provider, $this->userid) !== '';
            $rows[] = [
                'name'        => $provider,
                'label'       => $labels[$provider],
                'isset'       => $isset,
                'statuslabel' => $isset
                    ? get_string('mykeys_status_set', 'local_aihub')
                    : get_string('mykeys_status_unset', 'local_aihub'),
                'fieldkey'    => 'key_' . $provider,
                'fieldremove' => 'remove_' . $provider,
                'inputid'     => 'aihub-key-' . $provider,
            ];
        }
        return $rows;
    }

    /**
     * Builds the recent usage history rows for the user.
     *
     * @return array[]
     */
    private function log_rows(): array {
        $records = usage_log::get_recent_for_user($this->userid);
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
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
