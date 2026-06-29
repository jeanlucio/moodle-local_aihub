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
 * Public BYOK text-generation facade for local_aihub.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub;

use local_aihub\local\client;
use local_aihub\local\keys;
use local_aihub\local\usage_log;

/**
 * Stable entry point consumer plugins call to generate text with BYOK keys.
 *
 * Consume via a runtime guard: when this class is absent, fall back to core_ai
 * directly. The facade itself never consults core_ai. The returned text is raw
 * and untrusted: validate and pass it through format_text before display.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai {
    /** @var client|null Injected client used during tests. */
    private static ?client $clientoverride = null;

    /**
     * Generates text using the first available BYOK key (personal then site).
     *
     * Does not consult core_ai: when no key resolves, the result has success set
     * to false and the caller decides whether to fall back.
     *
     * @param string $system System instruction (may be empty).
     * @param string $user User prompt text.
     * @param bool $jsonmode Whether to request structured JSON output.
     * @param string $component Frankenstyle of the calling plugin, for the usage log.
     * @param string $description Short label of what is being generated, for the usage log.
     * @param int|null $userid User whose personal keys are tried first. Defaults to $USER->id.
     * @return array Keys: success (bool), data (string), provider (string), model (string), keysource (string), message (string).
     */
    public static function generate_text(
        string $system,
        string $user,
        bool $jsonmode = false,
        string $component = '',
        string $description = '',
        ?int $userid = null
    ): array {
        global $USER;

        $userid = $userid ?? (int) $USER->id;
        $client = self::$clientoverride ?? new client();
        $result = $client->generate_text($system, $user, $jsonmode, $userid);

        if (!empty($result['success'])) {
            usage_log::record(
                $userid,
                $component,
                $description,
                (string) ($result['provider'] ?? ''),
                (string) ($result['model'] ?? ''),
                true,
                (string) ($result['keysource'] ?? '')
            );
        }

        return $result;
    }

    /**
     * Returns true when at least one BYOK key (personal or site) is available.
     *
     * Does not consider core_ai. Consumers combine this with their own core_ai
     * check to decide whether to expose AI features.
     *
     * @param int|null $userid Defaults to $USER->id.
     * @return bool
     */
    public static function is_available(?int $userid = null): bool {
        return keys::has_any_key($userid);
    }

    /**
     * Injects a stub client for testing. Only callable under PHPUnit.
     *
     * @param client|null $client The stub client, or null to reset.
     * @return void
     */
    public static function set_client_for_testing(?client $client): void {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new \coding_exception('set_client_for_testing is only available during tests');
        }
        self::$clientoverride = $client;
    }
}
