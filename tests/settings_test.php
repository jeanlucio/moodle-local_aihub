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
 * Tests for the local_aihub admin settings tree.
 *
 * @package    local_aihub
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub;

use advanced_testcase;

/**
 * Builds the real admin tree as different users and checks where the plugin's pages land.
 *
 * @covers \core\plugininfo\local
 */
final class settings_test extends advanced_testcase {
    /**
     * Loads the full admin tree for the current user, from scratch.
     *
     * @return \part_of_admin_tree
     */
    private function admin_tree(): \part_of_admin_tree {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        return admin_get_root(true, true);
    }

    /**
     * A usage-report viewer without site configuration rights gets the report page and no debugging.
     *
     * Regression test: on Moodle 4.5 the localplugins category only exists for site admins, so adding
     * the plugin's category under it emitted "parent does not exist!" for every such user.
     */
    public function test_report_viewer_without_site_config(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/aihub:viewusage', CAP_ALLOW, $roleid, \context_system::instance()->id);
        role_assign($roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);

        $root = $this->admin_tree();

        $this->assertDebuggingNotCalled();
        $this->assertNotNull($root->locate('local_aihub_report'));
        $this->assertNull($root->locate('local_aihub'));
    }

    /**
     * A site admin gets the plugin's category with both the settings page and the report.
     */
    public function test_site_admin_gets_category(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $root = $this->admin_tree();

        $this->assertNotNull($root->locate('local_aihub_category'));
        $this->assertNotNull($root->locate('local_aihub'));
        $this->assertNotNull($root->locate('local_aihub_report'));
    }
}
