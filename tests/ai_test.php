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
 * Tests for the public BYOK text-generation facade.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub;

use local_aihub\local\keys;
use local_aihub\local\mock_client;
use local_aihub\local\usage_log;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/aihub/tests/fixtures/mock_client.php');

/**
 * Tests for {@see ai}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_aihub\ai
 */
final class ai_test extends \advanced_testcase {
    #[\Override]
    protected function tearDown(): void {
        ai::set_client_for_testing(null);
        parent::tearDown();
    }

    /**
     * Availability is false with no key and true once a site key exists.
     *
     * @covers ::is_available
     * @return void
     */
    public function test_is_available(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->assertFalse(ai::is_available());

        set_config('gemini_key', 'site-gemini', 'local_aihub');
        $this->assertTrue(ai::is_available());
    }

    /**
     * A successful generation records a log row tagged with the caller component.
     *
     * @covers ::generate_text
     * @return void
     */
    public function test_generate_text_logs_on_success(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('gemini_key', 'site-gemini', 'local_aihub');

        $client = new mock_client();
        $client->results['Gemini'] = [
            'success' => true,
            'data' => '{"ok":true}',
            'provider' => 'Gemini',
            'model' => 'gemini-flash-latest',
        ];
        ai::set_client_for_testing($client);

        $result = ai::generate_text('', 'hello', true, 'local_playergames', 'Concepts: test');

        $this->assertTrue($result['success']);
        $this->assertSame('{"ok":true}', $result['data']);

        $rows = $DB->get_records(usage_log::TABLE);
        $this->assertCount(1, $rows);
        $row = reset($rows);
        $this->assertSame('local_playergames', $row->component);
        $this->assertSame('Concepts: test', $row->description);
        $this->assertSame('Gemini', $row->provider);
        $this->assertSame('site', $row->keysource);
        $this->assertSame(1, (int) $row->success);
    }

    /**
     * A failed generation (no key resolves) writes no log row.
     *
     * @covers ::generate_text
     * @return void
     */
    public function test_generate_text_does_not_log_on_failure(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $client = new mock_client();
        ai::set_client_for_testing($client);

        $result = ai::generate_text('', 'hello', false, 'local_playergames');

        $this->assertFalse($result['success']);
        $this->assertSame(0, $DB->count_records(usage_log::TABLE));
    }

    /**
     * A consumer that resolved a hub key itself and made its own request can still
     * report that usage, without going through generate_text()/the client.
     *
     * @covers ::report_usage
     * @return void
     */
    public function test_report_usage_writes_log_row(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        ai::report_usage((int) get_admin()->id, 'block_playerhud', 'item', 'Gemini', 'gemini-flash-latest', 'site');

        $rows = $DB->get_records(usage_log::TABLE);
        $this->assertCount(1, $rows);
        $row = reset($rows);
        $this->assertSame('block_playerhud', $row->component);
        $this->assertSame('item', $row->description);
        $this->assertSame('Gemini', $row->provider);
        $this->assertSame('site', $row->keysource);
        $this->assertSame(1, (int) $row->success);
    }
}
