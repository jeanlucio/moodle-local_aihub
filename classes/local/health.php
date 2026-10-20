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
 * Short-lived memory of which AI providers have just failed.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * Remembers, for a few minutes, that a provider failed, so the next requests try it last.
 *
 * The client creates no state of its own and every request starts from scratch, so without this a
 * provider that times out costs its full timeout on every request. A failure is only a hint: a
 * remembered provider is moved to the end of the list, never taken out of it, so a request cannot
 * fail because of the memory alone.
 *
 * Failures are kept apart by group of keys. The site's keys are shared by everyone, so one failure
 * covers them all; a personal key belongs to one user, so a failure with it is that user's alone.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class health {
    /** @var string Group of keys that belong to one user. */
    public const TIER_PERSONAL = 'personal';

    /** @var string Group of keys set by the administrator for the whole site. */
    public const TIER_SITE = 'site';

    /** @var int Minutes a failure is remembered when the setting was never saved. */
    public const DEFAULT_MINUTES = 5;

    /** @var int Longest window the setting may ask for, in minutes. */
    public const MAX_MINUTES = 60;

    /**
     * Returns how long a failure counts for, in seconds. Zero means the memory is turned off.
     *
     * @return int
     */
    public static function window(): int {
        $setting = get_config('local_aihub', 'failurememory');
        $minutes = ($setting === false || $setting === '') ? self::DEFAULT_MINUTES : (int) $setting;

        return max(0, min(self::MAX_MINUTES, $minutes)) * MINSECS;
    }

    /**
     * Remembers that a provider failed, at the given moment.
     *
     * @param string $tier One of the TIER_* constants.
     * @param string $provider One of the keys::PROVIDER_* constants.
     * @param int $userid User whose personal keys were used; ignored for the site's keys.
     * @param int|null $now The moment, for tests. Defaults to the current time.
     * @return void
     */
    public static function mark_failed(string $tier, string $provider, int $userid, ?int $now = null): void {
        if (self::window() === 0) {
            return;
        }
        self::cache()->set(self::key($tier, $provider, $userid), $now ?? time());
    }

    /**
     * Tells whether a provider failed recently enough to be tried last.
     *
     * @param string $tier One of the TIER_* constants.
     * @param string $provider One of the keys::PROVIDER_* constants.
     * @param int $userid User whose personal keys are in use; ignored for the site's keys.
     * @param int|null $now The moment, for tests. Defaults to the current time.
     * @return bool
     */
    public static function is_demoted(string $tier, string $provider, int $userid, ?int $now = null): bool {
        $window = self::window();
        if ($window === 0) {
            return false;
        }
        $failedat = self::cache()->get(self::key($tier, $provider, $userid));
        if ($failedat === false) {
            return false;
        }

        return (($now ?? time()) - (int) $failedat) < $window;
    }

    /**
     * Moves the providers that failed recently to the end, keeping the order of the rest.
     *
     * Nothing is removed: with every provider in that state the list comes back unchanged, and each
     * one is still tried.
     *
     * @param string[] $providers The providers, in the order they would be tried.
     * @param string $tier One of the TIER_* constants.
     * @param int $userid User whose personal keys are in use; ignored for the site's keys.
     * @param int|null $now The moment, for tests. Defaults to the current time.
     * @return string[] The same providers, those that failed recently last.
     */
    public static function demote(array $providers, string $tier, int $userid, ?int $now = null): array {
        $healthy = [];
        $failing = [];
        foreach ($providers as $provider) {
            if (self::is_demoted($tier, $provider, $userid, $now)) {
                $failing[] = $provider;
            } else {
                $healthy[] = $provider;
            }
        }

        return array_merge($healthy, $failing);
    }

    /**
     * Forgets a failure, as when the provider has just answered.
     *
     * @param string $tier One of the TIER_* constants.
     * @param string $provider One of the keys::PROVIDER_* constants.
     * @param int $userid User whose personal keys were used; ignored for the site's keys.
     * @return void
     */
    public static function clear(string $tier, string $provider, int $userid): void {
        self::cache()->delete(self::key($tier, $provider, $userid));
    }

    /**
     * Forgets every failure of the site's keys, as when the administrator changes them.
     *
     * @return void
     */
    public static function clear_site(): void {
        foreach (keys::providers() as $provider) {
            self::clear(self::TIER_SITE, $provider, 0);
        }
    }

    /**
     * Forgets every failure of one user's personal keys, as when the user changes them.
     *
     * @param int $userid The user.
     * @return void
     */
    public static function clear_user(int $userid): void {
        foreach (keys::providers() as $provider) {
            self::clear(self::TIER_PERSONAL, $provider, $userid);
        }
    }

    /**
     * Builds the cache key for a provider within a group of keys.
     *
     * @param string $tier One of the TIER_* constants.
     * @param string $provider One of the keys::PROVIDER_* constants.
     * @param int $userid User whose personal keys are in use; ignored for the site's keys.
     * @return string
     */
    private static function key(string $tier, string $provider, int $userid): string {
        if ($tier === self::TIER_PERSONAL) {
            return 'personal_' . $userid . '_' . $provider;
        }

        return 'site_' . $provider;
    }

    /**
     * Returns the cache the failures are kept in.
     *
     * @return \cache_application
     */
    private static function cache(): \cache_application {
        return \cache::make('local_aihub', 'providerhealth');
    }
}
