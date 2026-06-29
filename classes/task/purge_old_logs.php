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
 * Scheduled task that purges old AI usage log entries.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\task;

/**
 * Deletes usage log rows older than the configured retention period.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purge_old_logs extends \core\task\scheduled_task {
    /**
     * Returns the task name shown in the scheduled tasks admin page.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_purge_old_logs', 'local_aihub');
    }

    /**
     * Deletes log rows older than the retention period (0 keeps them forever).
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $days = (int) get_config('local_aihub', 'logretentiondays');
        if ($days <= 0) {
            return;
        }

        $cutoff = time() - ($days * DAYSECS);
        $DB->delete_records_select('local_aihub_log', 'timecreated < :cutoff', ['cutoff' => $cutoff]);
    }
}
