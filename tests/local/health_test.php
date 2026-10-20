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
 * Tests for the memory of failing providers.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * Tests for {@see health}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_aihub\local\health
 */
final class health_test extends \advanced_testcase {
    /**
     * Starts every test with no failure remembered.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        \cache::make('local_aihub', 'providerhealth')->purge();
    }

    /**
     * A failure counts inside the window and stops counting once it has passed.
     *
     * @return void
     */
    public function test_a_failure_counts_only_inside_the_window(): void {
        set_config('failurememory', 5, 'local_aihub');
        $start = 1000000;

        health::mark_failed(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $start);

        $this->assertTrue(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $start));
        $this->assertTrue(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $start + 299));
        $this->assertFalse(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $start + 300));
        $this->assertFalse(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $start + 3600));
    }

    /**
     * A provider that never failed is not demoted.
     *
     * @return void
     */
    public function test_a_provider_that_never_failed_is_not_demoted(): void {
        $this->assertFalse(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GROQ, 0));
    }

    /**
     * Longer windows are honoured and the setting is kept within zero and an hour.
     *
     * @return void
     */
    public function test_the_window_comes_from_the_setting_within_its_limits(): void {
        $this->assertSame(5 * MINSECS, health::window(), 'five minutes until the setting is saved');

        set_config('failurememory', 20, 'local_aihub');
        $this->assertSame(20 * MINSECS, health::window());

        set_config('failurememory', 500, 'local_aihub');
        $this->assertSame(60 * MINSECS, health::window(), 'cut to an hour');

        set_config('failurememory', -4, 'local_aihub');
        $this->assertSame(0, health::window(), 'a negative value means off');

        set_config('failurememory', '', 'local_aihub');
        $this->assertSame(5 * MINSECS, health::window(), 'a blank value is the default, not off');
    }

    /**
     * Zero turns the memory off: nothing is stored and nothing is demoted.
     *
     * @return void
     */
    public function test_zero_turns_the_memory_off(): void {
        set_config('failurememory', 0, 'local_aihub');

        health::mark_failed(health::TIER_SITE, keys::PROVIDER_GEMINI, 0);

        $this->assertFalse(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GEMINI, 0));

        // Turning it back on does not bring back a failure that was never stored.
        set_config('failurememory', 5, 'local_aihub');
        $this->assertFalse(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GEMINI, 0));
    }

    /**
     * The site's keys, each user's personal keys and each provider are remembered apart.
     *
     * @return void
     */
    public function test_failures_are_kept_apart_by_group_user_and_provider(): void {
        $now = 2000000;
        health::mark_failed(health::TIER_PERSONAL, keys::PROVIDER_GEMINI, 11, $now);

        $this->assertTrue(health::is_demoted(health::TIER_PERSONAL, keys::PROVIDER_GEMINI, 11, $now));
        $this->assertFalse(health::is_demoted(health::TIER_PERSONAL, keys::PROVIDER_GEMINI, 12, $now), 'another user');
        $this->assertFalse(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GEMINI, 11, $now), 'the site group');
        $this->assertFalse(health::is_demoted(health::TIER_PERSONAL, keys::PROVIDER_GROQ, 11, $now), 'another provider');

        health::mark_failed(health::TIER_SITE, keys::PROVIDER_GROQ, 11, $now);

        $this->assertTrue(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GROQ, 12, $now), 'shared by every user');
        $this->assertFalse(health::is_demoted(health::TIER_PERSONAL, keys::PROVIDER_GROQ, 11, $now));
    }

    /**
     * Providers that failed go last and the rest keep their order.
     *
     * @return void
     */
    public function test_demote_moves_the_failing_providers_to_the_end(): void {
        $now = 3000000;
        $order = [keys::PROVIDER_GEMINI, keys::PROVIDER_GROQ, keys::PROVIDER_DEEPSEEK, keys::PROVIDER_OPENAI];

        $this->assertSame($order, health::demote($order, health::TIER_SITE, 0, $now), 'nothing failed');

        health::mark_failed(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $now);
        $this->assertSame(
            [keys::PROVIDER_GROQ, keys::PROVIDER_DEEPSEEK, keys::PROVIDER_OPENAI, keys::PROVIDER_GEMINI],
            health::demote($order, health::TIER_SITE, 0, $now)
        );

        health::mark_failed(health::TIER_SITE, keys::PROVIDER_DEEPSEEK, 0, $now);
        $this->assertSame(
            [keys::PROVIDER_GROQ, keys::PROVIDER_OPENAI, keys::PROVIDER_GEMINI, keys::PROVIDER_DEEPSEEK],
            health::demote($order, health::TIER_SITE, 0, $now),
            'the failing ones keep their own order among themselves'
        );
    }

    /**
     * With every provider failing nothing is dropped, so a request still has something to try.
     *
     * @return void
     */
    public function test_demote_never_removes_a_provider(): void {
        $now = 4000000;
        $order = [keys::PROVIDER_GEMINI, keys::PROVIDER_GROQ];
        health::mark_failed(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $now);
        health::mark_failed(health::TIER_SITE, keys::PROVIDER_GROQ, 0, $now);

        $this->assertSame($order, health::demote($order, health::TIER_SITE, 0, $now));
    }

    /**
     * An answer wipes the failure on the spot.
     *
     * @return void
     */
    public function test_clear_forgets_one_failure(): void {
        $now = 5000000;
        health::mark_failed(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $now);
        health::mark_failed(health::TIER_SITE, keys::PROVIDER_GROQ, 0, $now);

        health::clear(health::TIER_SITE, keys::PROVIDER_GEMINI, 0);

        $this->assertFalse(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $now));
        $this->assertTrue(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GROQ, 0, $now));
    }

    /**
     * The administrator changing the keys forgets the site's failures and nobody else's.
     *
     * @return void
     */
    public function test_clear_site_leaves_personal_failures_alone(): void {
        $now = 6000000;
        foreach (keys::providers() as $provider) {
            health::mark_failed(health::TIER_SITE, $provider, 0, $now);
        }
        health::mark_failed(health::TIER_PERSONAL, keys::PROVIDER_GEMINI, 21, $now);

        health::clear_site();

        foreach (keys::providers() as $provider) {
            $this->assertFalse(health::is_demoted(health::TIER_SITE, $provider, 0, $now));
        }
        $this->assertTrue(health::is_demoted(health::TIER_PERSONAL, keys::PROVIDER_GEMINI, 21, $now));
    }

    /**
     * A user changing their keys forgets their own failures and nobody else's.
     *
     * @return void
     */
    public function test_clear_user_leaves_other_users_and_the_site_alone(): void {
        $now = 7000000;
        foreach (keys::providers() as $provider) {
            health::mark_failed(health::TIER_PERSONAL, $provider, 31, $now);
        }
        health::mark_failed(health::TIER_PERSONAL, keys::PROVIDER_GEMINI, 32, $now);
        health::mark_failed(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $now);

        health::clear_user(31);

        foreach (keys::providers() as $provider) {
            $this->assertFalse(health::is_demoted(health::TIER_PERSONAL, $provider, 31, $now));
        }
        $this->assertTrue(health::is_demoted(health::TIER_PERSONAL, keys::PROVIDER_GEMINI, 32, $now));
        $this->assertTrue(health::is_demoted(health::TIER_SITE, keys::PROVIDER_GEMINI, 0, $now));
    }
}
