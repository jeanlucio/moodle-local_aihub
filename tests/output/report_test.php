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
 * Tests for the site-keys usage report renderable.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\output;

use local_aihub\local\usage_log;

/**
 * Tests for {@see report}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_aihub\output\report
 */
final class report_test extends \advanced_testcase {
    /**
     * Rows carry the requesting user's name and the correct provider icon, DeepSeek included.
     *
     * @covers ::export_for_template
     * @covers ::log_rows
     * @return void
     */
    public function test_export_for_template_rows_and_icons(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        usage_log::record((int) $user->id, 'local_aiassess', 'Forum review', 'DeepSeek', 'deepseek-v4-flash', true, 'site');
        // A personal-key row must never appear in the site-keys report.
        usage_log::record((int) $user->id, 'report_unlocker', 'Restriction help', 'Groq', '', true, 'personal');

        $output = $PAGE->get_renderer('core');
        $context = (new report())->export_for_template($output);

        $this->assertTrue($context['hasrows']);
        $this->assertCount(1, $context['rows']);
        $this->assertSame(fullname($user), $context['rows'][0]['user']);
        $this->assertSame('fa-search', $context['rows'][0]['providericon']);
    }

    /**
     * With no site-keys usage, the report reports an empty state.
     *
     * @covers ::export_for_template
     * @return void
     */
    public function test_export_for_template_empty_state(): void {
        global $PAGE;
        $this->resetAfterTest();

        $output = $PAGE->get_renderer('core');
        $context = (new report())->export_for_template($output);

        $this->assertFalse($context['hasrows']);
        $this->assertSame([], $context['rows']);
    }

    /**
     * A failed attempt is marked as such and carries its reason; a successful one
     * carries neither, so the reason column stays empty rather than stale.
     *
     * @covers ::export_for_template
     * @covers ::log_rows
     * @return void
     */
    public function test_failed_rows_carry_their_reason(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        usage_log::record((int) $user->id, 'mod_codereview', 'Review', 'Gemini', 'flash', false, 'site', 'Gemini: down');
        usage_log::record((int) $user->id, 'mod_codereview', 'Review', 'Groq', 'llama', true, 'site');

        $output = $PAGE->get_renderer('core');
        $context = (new report())->export_for_template($output);

        $this->assertCount(2, $context['rows']);

        $byprovider = array_column($context['rows'], null, 'provider');
        $this->assertTrue($byprovider['Gemini']['failed']);
        $this->assertSame('Gemini: down', $byprovider['Gemini']['errormessage']);
        $this->assertFalse($byprovider['Groq']['failed']);
        $this->assertSame('', $byprovider['Groq']['errormessage']);
    }

    /**
     * The failures-only view narrows the rows and swaps the empty-state wording.
     *
     * @covers ::export_for_template
     * @covers ::log_rows
     * @return void
     */
    public function test_only_failures_view(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        usage_log::record((int) $user->id, 'mod_codereview', 'Review', 'Groq', 'llama', true, 'site');

        $output = $PAGE->get_renderer('core');
        $context = (new report(true))->export_for_template($output);

        $this->assertTrue($context['onlyfailures']);
        $this->assertFalse($context['hasrows']);
        $this->assertSame(get_string('report_nofailures', 'local_aihub'), $context['empty']);
    }

    /**
     * A row written before the failure columns existed still reads as a success.
     *
     * The upgrade adds errormessage as nullable, so every pre-existing row carries
     * a null there and the default 1 in success. Rows built through record() always
     * set both, so only a hand-built row reproduces what is actually in the table.
     *
     * @covers ::export_for_template
     * @covers ::log_rows
     * @return void
     */
    public function test_a_pre_upgrade_row_renders_as_a_success(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/local/aihub/report.php');
        $user = $this->getDataGenerator()->create_user();

        $DB->insert_record(usage_log::TABLE, (object) [
            'userid' => $user->id,
            'component' => 'local_playergames',
            'description' => 'Concepts: test',
            'provider' => 'Gemini',
            'model' => 'gemini-flash-latest',
            'keysource' => 'site',
            'timecreated' => time(),
        ]);

        $output = $PAGE->get_renderer('local_aihub');
        $context = (new report())->export_for_template($output);

        $this->assertFalse($context['rows'][0]['failed']);
        $this->assertSame('', $context['rows'][0]['errormessage']);
        $this->assertSame(get_string('report_succeeded', 'local_aihub'), $context['rows'][0]['statuslabel']);

        // And it survives the round trip through the template.
        $html = $output->render(new report());
        $this->assertStringNotContainsString('[[', $html);
        $this->assertStringContainsString('bg-success', $html);
    }

    /**
     * The page renders end to end with no unresolved string placeholders.
     *
     * @covers ::export_for_template
     * @return void
     */
    public function test_template_renders(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/local/aihub/report.php');
        $user = $this->getDataGenerator()->create_user();

        usage_log::record((int) $user->id, 'mod_codereview', 'Review', 'Gemini', 'flash', false, 'site', 'Gemini: down');

        $output = $PAGE->get_renderer('local_aihub');
        $html = $output->render(new report());

        $this->assertStringNotContainsString('[[', $html);
        $this->assertStringContainsString('Gemini: down', $html);
        $this->assertStringContainsString('bg-danger', $html);
    }
}
