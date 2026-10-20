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
 * Page where the administrator drags the AI providers into the order they are tried in.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_aihub\local\keys;
use local_aihub\output\provider_order;

admin_externalpage_setup('local_aihub_providerorder');

$url = new moodle_url('/local/aihub/providerorder.php');
$PAGE->set_url($url);

if (data_submitted() && confirm_sesskey()) {
    // The list sends the whole order it shows once it has been dragged, and nothing before that. Only providers
    // on the fixed list are kept, and "back to automatic" clears the choice.
    $order = optional_param('resetorder', 0, PARAM_BOOL) ? [] : optional_param('providerorder', '', PARAM_TEXT);
    keys::save_site_provider_order($order);

    redirect($url, get_string('providerorder_saved', 'local_aihub'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$chosen = keys::get_site_provider_order();
$list = new provider_order(keys::provider_order(0), $chosen, 'providerorder', true, 'aihub-providerorder-label');

$PAGE->requires->js_call_amd('local_aihub/providerorder', 'init', ['aihub-providerorder-list']);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_aihub/admin_providerorder', [
    'heading' => get_string('providerorder_title', 'local_aihub'),
    'intro' => get_string('providerorder_intro', 'local_aihub'),
    'actionurl' => $url->out(false),
    'sesskey' => sesskey(),
    'savelabel' => get_string('providerorder_save', 'local_aihub'),
    'resetlabel' => get_string('providerorder_reset', 'local_aihub'),
    'hasown' => $chosen !== [],
    'providerorder' => $list->export_for_template($OUTPUT),
]);
echo $OUTPUT->footer();
