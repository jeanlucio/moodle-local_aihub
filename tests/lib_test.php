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

namespace local_aihub;

use advanced_testcase;
use context_course;
use context_user;
use navigation_node;

/**
 * Tests for the navigation callback.
 *
 * This is the only way into the personal-keys screen. If the entry stops being
 * added, or starts being added on someone else's preferences page, nothing else
 * in the plugin notices: the page itself keeps working for anyone who knows the
 * URL, and the feature simply disappears from the menu.
 *
 * @package    local_aihub
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::local_aihub_extend_navigation_user_settings
 */
final class lib_test extends advanced_testcase {
    /**
     * Pulls in the function library, which is not autoloaded.
     *
     * @return void
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/local/aihub/lib.php');
    }

    /**
     * Creates a user holding local/aihub:usepersonalkey.
     *
     * The capability is granted to manager, editingteacher and teacher archetypes
     * only, so a plain account never sees this entry however the site is set up.
     *
     * @return \stdClass
     */
    private function create_permitted_user(): \stdClass {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        role_assign($roleid, $user->id, \context_system::instance());

        return $user;
    }

    /**
     * Runs the callback for a user's preferences page and reports whether the
     * entry was added.
     *
     * @param \stdClass $owner The user whose preferences page is being built.
     * @return bool
     */
    private function entry_added_for(\stdClass $owner): bool {
        $course = get_course(SITEID);
        $node = navigation_node::create('root');

        local_aihub_extend_navigation_user_settings(
            $node,
            $owner,
            context_user::instance($owner->id),
            $course,
            context_course::instance($course->id)
        );

        return (bool) $node->find('local_aihub_mykeys', navigation_node::TYPE_SETTING);
    }

    /**
     * With personal keys enabled, the entry appears on the user's own page.
     *
     * @return void
     */
    public function test_entry_is_added_for_the_owner(): void {
        set_config('enablepersonalkeys', 1, 'local_aihub');
        $user = $this->create_permitted_user();
        $this->setUser($user);

        $this->assertTrue($this->entry_added_for($user));
    }

    /**
     * An account without the capability never sees the entry, even with personal
     * keys enabled site-wide.
     *
     * @return void
     */
    public function test_entry_is_not_added_without_the_capability(): void {
        set_config('enablepersonalkeys', 1, 'local_aihub');
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertFalse($this->entry_added_for($user));
    }

    /**
     * Viewing someone else's preferences page does not offer their keys, which
     * would link to a screen that only ever edits the viewer's own.
     *
     * @return void
     */
    public function test_entry_is_not_added_on_another_users_page(): void {
        set_config('enablepersonalkeys', 1, 'local_aihub');
        $user = $this->create_permitted_user();
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertFalse($this->entry_added_for($other));
    }

    /**
     * With personal keys switched off site-wide, the entry is not offered at all.
     *
     * @return void
     */
    public function test_entry_is_not_added_when_personal_keys_are_disabled(): void {
        set_config('enablepersonalkeys', 0, 'local_aihub');
        $user = $this->create_permitted_user();
        $this->setUser($user);

        $this->assertFalse($this->entry_added_for($user));
    }
}
