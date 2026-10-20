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
 * Tests for the sortable provider list.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\output;

use local_aihub\local\keys;

/**
 * Tests for {@see provider_order}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_aihub\output\provider_order
 */
final class provider_order_test extends \advanced_testcase {
    /**
     * Each provider is one item, in the order shown, with a name for its drag handle that says
     * which provider it moves.
     *
     * @return void
     */
    public function test_items_follow_the_order_shown(): void {
        global $PAGE;
        $list = new provider_order(['groq', 'gemini', 'openai', 'deepseek'], [], 'providerorder');

        $context = $list->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame(['groq', 'gemini', 'openai', 'deepseek'], array_column($context['items'], 'name'));
        $this->assertSame('Groq', $context['items'][0]['label']);
        $this->assertSame(get_string('providerorder_move', 'local_aihub', 'Groq'), $context['items'][0]['movetitle']);
    }

    /**
     * The hidden field carries what was saved and nothing else, so "automatic" stays empty.
     *
     * @return void
     */
    public function test_the_field_carries_only_the_saved_order(): void {
        global $PAGE;
        $output = $PAGE->get_renderer('core');

        $automatic = (new provider_order(['gemini', 'groq'], [], 'providerorder'))->export_for_template($output);
        $own = (new provider_order(['groq', 'gemini'], ['groq', 'gemini'], 'providerorder'))->export_for_template($output);

        $this->assertSame('', $automatic['inputvalue']);
        $this->assertSame('groq,gemini', $own['inputvalue']);
        $this->assertSame('providerorder', $own['inputname']);
        $this->assertSame('aihub-providerorder', $own['inputid']);
        $this->assertSame('aihub-providerorder-list', $own['listid']);
    }

    /**
     * On the administrator's page a provider with no site key is marked; on the user's page nothing is,
     * since the keys that count there are their own and the site's together.
     *
     * @return void
     */
    public function test_providers_without_a_site_key_are_marked_when_asked(): void {
        global $PAGE;
        $this->resetAfterTest();
        $output = $PAGE->get_renderer('core');
        set_config('groq_key', 'site-groq', 'local_aihub');

        $marked = (new provider_order(keys::providers(), [], 'providerorder', true))->export_for_template($output);
        $plain = (new provider_order(keys::providers(), [], 'providerorder', false))->export_for_template($output);

        $flags = array_column($marked['items'], 'nokey', 'name');
        $this->assertTrue($flags['gemini']);
        $this->assertFalse($flags['groq']);
        $this->assertSame([false], array_values(array_unique(array_column($plain['items'], 'nokey'))));
    }

    /**
     * The list can be named by another element on the page, for assistive technology.
     *
     * @return void
     */
    public function test_the_list_can_be_named_by_a_heading(): void {
        global $PAGE;
        $output = $PAGE->get_renderer('core');

        $named = (new provider_order(['groq'], [], 'providerorder', false, 'some-heading'))->export_for_template($output);
        $unnamed = (new provider_order(['groq'], [], 'providerorder'))->export_for_template($output);

        $this->assertSame('some-heading', $named['labelid']);
        $this->assertSame('', $unnamed['labelid']);
    }
}
