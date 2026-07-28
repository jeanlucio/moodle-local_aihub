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
 * Behat step definitions for local_aihub.
 *
 * @package    local_aihub
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;
use local_aihub\local\usage_log;

/**
 * Steps that drive the self-service AI keys page in acceptance tests.
 *
 * @package    local_aihub
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_aihub extends behat_base {
    /**
     * Opens the self-service "My AI keys" page.
     *
     * @Given I am on the My AI keys page
     */
    public function i_am_on_the_my_ai_keys_page(): void {
        $this->getSession()->visit($this->locate_path('/local/aihub/mykeys.php'));
    }

    /**
     * Opens the site-wide AI usage report.
     *
     * @Given I am on the site AI usage report page
     */
    public function i_am_on_the_site_ai_usage_report_page(): void {
        $this->getSession()->visit($this->locate_path('/local/aihub/report.php'));
    }

    /**
     * Seeds usage log rows.
     *
     * The log is written by the generation path rather than by a form, so there is
     * no interface a scenario could drive to produce a row to read back.
     *
     * Columns: user, component, description, provider, model, keysource, success,
     * errormessage. Only user and provider are required.
     *
     * @Given /^the following AI usage entries exist:$/
     * @param TableNode $data The rows to insert.
     */
    public function the_following_ai_usage_entries_exist(TableNode $data): void {
        global $DB;

        foreach ($data->getHash() as $row) {
            $user = $DB->get_record('user', ['username' => $row['user']], 'id', MUST_EXIST);
            $success = !isset($row['success']) || $row['success'] === '1';

            usage_log::record(
                (int) $user->id,
                $row['component'] ?? 'local_aihub',
                $row['description'] ?? '',
                $row['provider'],
                $row['model'] ?? '',
                $success,
                $row['keysource'] ?? 'site',
                $row['errormessage'] ?? ''
            );
        }
    }
}
