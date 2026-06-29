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
 * Pre-uninstall cleanup for local_aihub.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Removes personal AI key preferences from the core user_preferences table.
 *
 * The plugin's own table and admin settings are dropped by core; only the
 * preferences it wrote into a core table need explicit cleanup.
 *
 * @return bool
 */
function xmldb_local_aihub_uninstall(): bool {
    global $DB;

    $DB->delete_records_select(
        'user_preferences',
        $DB->sql_like('name', ':pattern'),
        ['pattern' => 'local_aihub_%']
    );

    return true;
}
