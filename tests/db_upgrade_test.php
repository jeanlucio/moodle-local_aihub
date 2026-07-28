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
use xmldb_table;

/**
 * Unit tests for the local_aihub upgrade steps.
 *
 * Nothing else runs upgrade.php: the test database and moodle-plugin-ci both build
 * the schema from install.xml, so an upgrade step that disagrees with it stays green
 * everywhere and only surfaces on a site that already has data. These tests rebuild
 * the pre-upgrade shape and run the real function against it.
 *
 * @package    local_aihub
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers ::xmldb_local_aihub_upgrade
 */
final class db_upgrade_test extends advanced_testcase {
    /** @var string The table the upgrade steps operate on. */
    private const TABLE = 'local_aihub_log';

    /**
     * Pulls in the upgrade function and the savepoint helper it calls.
     *
     * @return void
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();

        // The savepoint helper lives in upgradelib.php, which only the real upgrade
        // runner loads. Calling the function directly needs it pulled in by hand.
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/local/aihub/db/upgrade.php');
    }

    /**
     * Removes the column so the table matches what a pre-2026072800 site holds.
     *
     * @return void
     */
    private function drop_errormessage(): void {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table(self::TABLE);
        $field = new \xmldb_field('errormessage', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'success');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
    }

    /**
     * Records an older plugin version, as a site partway through an upgrade has.
     *
     * upgrade_plugin_savepoint() refuses anything that is not a step forward, so
     * without this the call below would be rejected as a downgrade.
     *
     * @param int $version Version to record as the plugin's current one.
     * @return void
     */
    private function set_current_version(int $version): void {
        set_config('version', $version, 'local_aihub');
    }

    /**
     * The step adds the column, and adds it with the spec install.xml declares.
     *
     * A type or length that drifts from install.xml is invisible to every other
     * check here, because they all build the table from install.xml to begin with.
     *
     * @return void
     */
    public function test_upgrade_adds_the_errormessage_column(): void {
        global $DB;

        $this->drop_errormessage();
        $this->assertArrayNotHasKey('errormessage', $DB->get_columns(self::TABLE, false));

        $this->set_current_version(2026072100);
        xmldb_local_aihub_upgrade(2026072100);

        $columns = $DB->get_columns(self::TABLE, false);
        $this->assertArrayHasKey('errormessage', $columns);

        $column = $columns['errormessage'];
        $this->assertSame('C', $column->meta_type);
        $this->assertEquals(255, $column->max_length);
        $this->assertFalse($column->not_null);
    }

    /**
     * Rows written before the step keep their data and read as successes.
     *
     * The column is nullable with no backfill, which is deliberate: a row from
     * before the change carries no failure information, and inventing one would be
     * worse than leaving it empty.
     *
     * @return void
     */
    public function test_upgrade_preserves_existing_rows(): void {
        global $DB;

        $this->drop_errormessage();

        $user = $this->getDataGenerator()->create_user();
        $id = $DB->insert_record(self::TABLE, (object) [
            'userid' => $user->id,
            'component' => 'local_playergames',
            'description' => 'Concepts: test',
            'provider' => 'Gemini',
            'model' => 'gemini-flash-latest',
            'keysource' => 'site',
            'timecreated' => time(),
        ]);

        $this->set_current_version(2026072100);
        xmldb_local_aihub_upgrade(2026072100);

        $row = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        $this->assertSame('local_playergames', $row->component);
        $this->assertSame('Gemini', $row->provider);
        $this->assertSame(1, (int) $row->success);
        $this->assertNull($row->errormessage);
    }

    /**
     * Running the step twice is harmless, which is what the field_exists guard is
     * for: a failed upgrade gets re-run, and the second pass must not error.
     *
     * @return void
     */
    public function test_upgrade_is_repeatable(): void {
        global $DB;

        $this->set_current_version(2026072100);
        xmldb_local_aihub_upgrade(2026072100);

        $this->set_current_version(2026072100);
        $this->assertTrue(xmldb_local_aihub_upgrade(2026072100));
        $this->assertArrayHasKey('errormessage', $DB->get_columns(self::TABLE, false));
    }
}
