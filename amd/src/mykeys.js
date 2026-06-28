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
 * Show/hide toggle for the personal AI key fields.
 *
 * @module     local_aihub/mykeys
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Wires every show/hide toggle button to its key input.
 */
export const init = () => {
    const toggles = document.querySelectorAll('[data-action="local-aihub-toggle"]');
    toggles.forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            if (!input) {
                return;
            }
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            button.setAttribute('aria-pressed', hidden ? 'true' : 'false');
        });
    });
};
