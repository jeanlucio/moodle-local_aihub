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
 * Admin report of AI usage served by the site-wide keys.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_aihub\local\export;
use local_aihub\output\report;

require_login();

$context = context_system::instance();
require_capability('local/aihub:viewusage', $context);

// Stream the report download before any page output is sent.
$download = optional_param('download', '', PARAM_ALPHA);
if ($download !== '' && in_array($download, export::FORMATS, true)) {
    export::download_site($download);
}

admin_externalpage_setup('local_aihub_report');

$output = $PAGE->get_renderer('local_aihub');

echo $output->header();
echo $output->heading(get_string('report_title', 'local_aihub'));
echo $output->render(new report());
echo $output->footer();
