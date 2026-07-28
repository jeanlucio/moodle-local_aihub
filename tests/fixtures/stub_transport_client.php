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
 * Test double that runs the real response parsing against a canned transport.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * A client whose HTTP layer answers from a stub, so parsing runs for real.
 *
 * The response handling differs per provider and is the part that turns an API
 * error into the sentence an administrator reads, so it is worth reaching without
 * a network round trip.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stub_transport_client extends client {
    /** @var stub_curl The canned transport handed to http_post(). */
    public stub_curl $curl;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->curl = new stub_curl();
    }

    /**
     * Runs the real http_post() against the stubbed transport.
     *
     * @param string $url Target URL.
     * @param string $payload JSON-encoded POST body.
     * @param array $headers Array of header strings.
     * @param string $source Display name of the AI provider.
     * @return array
     */
    public function post_for_testing(string $url, string $payload, array $headers, string $source): array {
        return $this->http_post($url, $payload, $headers, $source);
    }

    #[\Override]
    protected function make_curl(): \curl {
        return $this->curl;
    }
}
