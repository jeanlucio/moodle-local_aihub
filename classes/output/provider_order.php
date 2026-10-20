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
 * The sortable list of AI providers, shared by the user's page and the administrator's.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\output;

use local_aihub\local\keys;
use renderable;
use renderer_base;
use templatable;

/**
 * Builds the context of a list the user drags to put the providers in the order they are tried in.
 *
 * The list and a hidden field carry the same order. The field starts with the order the person
 * chose, which is empty for "automatic": it is only rewritten once the list is dragged, so saving
 * the form without touching the list does not turn "automatic" into a fixed order.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider_order implements renderable, templatable {
    /** @var string[] The providers in the order the list shows them: the effective order. */
    private array $shown;

    /** @var string[] The order the person saved, empty for "automatic". */
    private array $chosen;

    /** @var string Name of the hidden form field that carries the order on submit. */
    private string $inputname;

    /** @var bool Whether to mark the providers that have no site key. */
    private bool $flagmissingkeys;

    /** @var string Id of the element that names the list, for assistive technology. */
    private string $labelid;

    /**
     * Constructor.
     *
     * @param string[] $shown The providers in the order the list shows them: the effective order.
     * @param string[] $chosen The order the person saved, empty for "automatic".
     * @param string $inputname Name of the hidden form field that carries the order on submit.
     * @param bool $flagmissingkeys Whether to mark the providers that have no site key.
     * @param string $labelid Id of the element that names the list, for assistive technology.
     */
    public function __construct(
        array $shown,
        array $chosen,
        string $inputname,
        bool $flagmissingkeys = false,
        string $labelid = ''
    ) {
        $this->shown = $shown;
        $this->chosen = $chosen;
        $this->inputname = $inputname;
        $this->flagmissingkeys = $flagmissingkeys;
        $this->labelid = $labelid;
    }

    /**
     * Exports the Mustache context for the list.
     *
     * @param renderer_base $output The active renderer.
     * @return array The template context.
     */
    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $labels = [
            keys::PROVIDER_GEMINI   => get_string('provider_gemini', 'local_aihub'),
            keys::PROVIDER_GROQ     => get_string('provider_groq', 'local_aihub'),
            keys::PROVIDER_DEEPSEEK => get_string('provider_deepseek', 'local_aihub'),
            keys::PROVIDER_OPENAI   => get_string('provider_openai', 'local_aihub'),
        ];

        $items = [];
        foreach ($this->shown as $provider) {
            $label = $labels[$provider] ?? $provider;
            $items[] = [
                'name'       => $provider,
                'label'      => $label,
                'movetitle'  => get_string('providerorder_move', 'local_aihub', $label),
                'nokey'      => $this->flagmissingkeys && keys::get_site_key($provider) === '',
                'nokeylabel' => get_string('providerorder_nokey', 'local_aihub'),
            ];
        }

        return [
            'listid'     => 'aihub-' . $this->inputname . '-list',
            'inputid'    => 'aihub-' . $this->inputname,
            'inputname'  => $this->inputname,
            'labelid'    => $this->labelid,
            'inputvalue' => implode(',', $this->chosen),
            'items'      => $items,
        ];
    }
}
