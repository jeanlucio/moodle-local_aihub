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
 * Test double that captures the request each provider assembles.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * A client that runs the real call_* methods and records what they built.
 *
 * mock_client replaces those methods outright, which is what a test of the
 * resolution ladder wants but leaves the request each provider assembles — its
 * URL, model, headers and the shape of its JSON — never exercised.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_client extends client {
    /** @var array[] One entry per http_post() call: url, payload, headers, source. */
    public array $requests = [];

    /** @var array Result handed back in place of a real provider response. */
    public array $response = ['success' => true, 'data' => 'ok'];

    #[\Override]
    protected function http_post(string $url, string $payload, array $headers, string $source): array {
        $this->requests[] = [
            'url' => $url,
            'payload' => json_decode($payload, true),
            'headers' => $headers,
            'source' => $source,
        ];

        return $this->response + ['provider' => $source];
    }

    #[\Override]
    protected function is_safe_url(string $url): bool {
        return true;
    }
}
