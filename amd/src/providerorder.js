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
 * Drag-to-reorder list of the AI providers.
 *
 * @module     local_aihub/providerorder
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import jQuery from 'jquery';
import SortableList from 'core/sortable_list';

/**
 * Makes the list sortable and keeps the hidden field in step with it.
 *
 * Moving, with the mouse or from the keyboard, is the core sortable list's: it does the dragging,
 * the "move here" targets and the announcements. The hidden field is only written once something is
 * dropped, so a form saved without touching the list leaves the saved order as it was.
 *
 * @param {string} listId Id of the list element.
 */
export const init = (listId) => {
    const list = document.getElementById(listId);
    const input = list ? document.getElementById(list.dataset.input) : null;
    if (!list || !input) {
        return;
    }

    new SortableList(`#${listId}`, {moveHandlerSelector: '[data-drag-type=move]'});

    // The core module reports a drop through a jQuery event, which bubbles up to the list.
    jQuery(list).on(SortableList.EVENTS.DROP, () => {
        input.value = Array.from(list.children).map((item) => item.dataset.provider).join(',');
    });
};
