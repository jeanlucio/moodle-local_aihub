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
 * Tests for the usage log retention task.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\task;

use local_aihub\local\usage_log;

/**
 * Tests for {@see purge_old_logs}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_aihub\task\purge_old_logs
 */
final class purge_old_logs_test extends \advanced_testcase {
    /**
     * Backdates a log row's timecreated to a given number of days ago.
     *
     * @param int $id The log row id.
     * @param int $daysago How many days in the past to set timecreated to.
     * @return void
     */
    private function backdate(int $id, int $daysago): void {
        global $DB;

        $DB->set_field(usage_log::TABLE, 'timecreated', time() - ($daysago * DAYSECS), ['id' => $id]);
    }

    /**
     * Rows older than the retention period are deleted; newer rows are kept.
     *
     * @covers ::execute
     * @return void
     */
    public function test_execute_deletes_only_rows_older_than_retention(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('logretentiondays', 30, 'local_aihub');
        $user = $this->getDataGenerator()->create_user();

        $oldid = usage_log::record((int) $user->id, 'local_aiassess', '', 'Gemini', '', true, 'site');
        $this->backdate($oldid, 31);
        $recentid = usage_log::record((int) $user->id, 'local_aiassess', '', 'Gemini', '', true, 'site');
        $this->backdate($recentid, 29);

        (new purge_old_logs())->execute();

        $this->assertFalse($DB->record_exists(usage_log::TABLE, ['id' => $oldid]));
        $this->assertTrue($DB->record_exists(usage_log::TABLE, ['id' => $recentid]));
    }

    /**
     * A retention of 0 (or unset) keeps every row indefinitely.
     *
     * @covers ::execute
     * @return void
     */
    public function test_execute_keeps_everything_when_retention_is_zero(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('logretentiondays', 0, 'local_aihub');
        $user = $this->getDataGenerator()->create_user();

        $id = usage_log::record((int) $user->id, 'local_aiassess', '', 'Gemini', '', true, 'site');
        $this->backdate($id, 3650);

        (new purge_old_logs())->execute();

        $this->assertTrue($DB->record_exists(usage_log::TABLE, ['id' => $id]));
    }
}
