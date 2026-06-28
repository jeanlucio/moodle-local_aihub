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
 * Test double for the BYOK transport client.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * A client whose provider calls are programmable and never hit the network.
 *
 * Set a per-provider result in {@see self::$results} (keyed by display name
 * Gemini, Groq or OpenAI). Providers actually invoked are recorded in
 * {@see self::$calls}, in order, so the resolution ladder can be asserted.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mock_client extends client {
    /** @var array<string, array> Programmed result per provider display name. */
    public array $results = [];

    /** @var string[] Provider display names actually called, in order. */
    public array $calls = [];

    #[\Override]
    protected function call_gemini(string $system, string $user, string $key, bool $jsonmode): array {
        $this->calls[] = 'Gemini';
        return $this->results['Gemini'] ?? ['success' => false, 'message' => 'Gemini: stub', 'provider' => 'Gemini'];
    }

    #[\Override]
    protected function call_groq(string $system, string $user, string $key, bool $jsonmode): array {
        $this->calls[] = 'Groq';
        return $this->results['Groq'] ?? ['success' => false, 'message' => 'Groq: stub', 'provider' => 'Groq'];
    }

    #[\Override]
    protected function call_openai_compatible(
        string $system,
        string $user,
        string $key,
        string $endpointurl,
        string $model,
        bool $jsonmode
    ): array {
        $this->calls[] = 'OpenAI';
        return $this->results['OpenAI'] ?? ['success' => false, 'message' => 'OpenAI: stub', 'provider' => 'OpenAI'];
    }

    #[\Override]
    protected function is_safe_url(string $url): bool {
        return true;
    }
}
