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

    /**
     * The provider order has a page of its own under the plugin's category, for the administrator
     * only, and the settings page points to it. It is not a setting: nothing is stored under the
     * settings page for it.
     */
    public function test_provider_order_has_a_page_of_its_own(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $root = $this->admin_tree();
        $page = $root->locate('local_aihub_providerorder');

        $this->assertNotNull($page);
        $this->assertInstanceOf(\admin_externalpage::class, $page);
        $this->assertStringContainsString('/local/aihub/providerorder.php', $page->url->out(false));
        $this->assertSame(['moodle/site:config'], $page->req_capability);

        $link = $root->locate('local_aihub')->settings->local_aihubproviderorderlink;
        $this->assertInstanceOf(\admin_setting_description::class, $link);
        $this->assertStringContainsString('/local/aihub/providerorder.php', (string) $link->description);
        $this->assertObjectNotHasProperty('local_aihubdefaultprovider', $root->locate('local_aihub')->settings);
    }

    /**
     * Someone who may see the usage report but not change the site's configuration does not get the
     * order page.
     */
    public function test_report_viewer_does_not_get_the_order_page(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/aihub:viewusage', CAP_ALLOW, $roleid, \context_system::instance()->id);
        role_assign($roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);

        $this->assertNull($this->admin_tree()->locate('local_aihub_providerorder'));
    }

    /**
     * How long a failing provider is remembered is a plain number of minutes, five by default.
     */
    public function test_failure_memory_setting(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $setting = $this->admin_tree()->locate('local_aihub')->settings->local_aihubfailurememory;

        $this->assertSame(5, (int) $setting->get_defaultsetting());
        $this->assertSame('', $setting->write_setting('10'));
        $this->assertSame('10', get_config('local_aihub', 'failurememory'));
        $this->assertNotSame('', $setting->write_setting('ten'), 'a value that is not a number is refused');
    }

    /**
     * Changing a site key, the endpoint or the model forgets the site's failures, since that may be
     * exactly what the failing provider needed; saving the same value again does not.
     */
    public function test_changing_a_site_setting_forgets_the_site_failures(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        \cache::make('local_aihub', 'providerhealth')->purge();
        $page = $this->admin_tree()->locate('local_aihub');

        foreach (['local_aihubgemini_key', 'local_aihubopenai_baseurl', 'local_aihubopenai_model'] as $name) {
            $setting = $page->settings->{$name};
            $value = $name === 'local_aihubopenai_baseurl' ? 'https://8.8.8.8/v1' : 'changed-' . $name;

            local\health::mark_failed(local\health::TIER_SITE, local\keys::PROVIDER_GEMINI, 0);
            $original = $setting->get_setting();
            $this->assertSame('', $setting->write_setting($value), $name);
            $this->assertTrue($setting->post_write_settings($original), $name . ' changed');
            $this->assertFalse(
                local\health::is_demoted(local\health::TIER_SITE, local\keys::PROVIDER_GEMINI, 0),
                $name . ' clears the memory'
            );

            local\health::mark_failed(local\health::TIER_SITE, local\keys::PROVIDER_GEMINI, 0);
            $original = $setting->get_setting();
            $setting->write_setting($value);
            $this->assertFalse($setting->post_write_settings($original), $name . ' unchanged');
            $this->assertTrue(
                local\health::is_demoted(local\health::TIER_SITE, local\keys::PROVIDER_GEMINI, 0),
                $name . ' saved again as it was keeps the memory'
            );
        }
    }
}
