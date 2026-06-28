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
 * Self-service page for managing personal AI API keys.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_aihub\local\keys;
use local_aihub\output\mykeys;

require_login();

$context = context_system::instance();
$userid = (int) $USER->id;

if (!keys::personal_keys_allowed($userid)) {
    throw new moodle_exception('mykeys_notallowed', 'local_aihub');
}

$url = new moodle_url('/local/aihub/mykeys.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mykeys', 'local_aihub'));
$PAGE->set_heading(get_string('mykeys', 'local_aihub'));

if (data_submitted() && confirm_sesskey()) {
    foreach (keys::providers() as $provider) {
        if (optional_param('remove_' . $provider, 0, PARAM_BOOL)) {
            keys::save_user_key($provider, '', $userid);
            continue;
        }
        $key = optional_param('key_' . $provider, '', PARAM_RAW_TRIMMED);
        if ($key !== '') {
            keys::save_user_key($provider, $key, $userid);
        }
    }

    keys::save_user_openai_url(optional_param('openai_url', '', PARAM_URL), $userid);
    keys::save_user_openai_model(optional_param('openai_model', '', PARAM_TEXT), $userid);

    redirect($url, get_string('mykeys_keyssaved', 'local_aihub'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->requires->js_call_amd('local_aihub/mykeys', 'init');

$output = $PAGE->get_renderer('local_aihub');

echo $output->header();
echo $output->render(new mykeys($userid));
echo $output->footer();
