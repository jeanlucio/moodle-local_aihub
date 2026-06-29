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
 * Library functions for local_aihub.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add a "My AI keys" entry to the user's own preferences page.
 *
 * @param navigation_node $usersetting The user settings navigation node.
 * @param stdClass $user The user whose preferences are being shown.
 * @param context_user $usercontext The user context.
 * @param stdClass $course The current course.
 * @param context_course $coursecontext The current course context.
 */
function local_aihub_extend_navigation_user_settings(
    navigation_node $usersetting,
    stdClass $user,
    context_user $usercontext,
    stdClass $course,
    context_course $coursecontext
): void {
    global $USER;

    // Only expose this on the user's own preferences page.
    if ($user->id != $USER->id) {
        return;
    }

    // Only when personal keys are enabled and allowed for this user.
    if (!\local_aihub\local\keys::personal_keys_allowed((int) $USER->id)) {
        return;
    }

    $usersetting->add(
        get_string('mykeys', 'local_aihub'),
        new moodle_url('/local/aihub/mykeys.php'),
        navigation_node::TYPE_SETTING,
        null,
        'local_aihub_mykeys'
    );
}
