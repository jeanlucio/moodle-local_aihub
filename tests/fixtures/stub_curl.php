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
 * Curl stand-in that answers with a canned response.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * A curl that returns a programmed body and status without touching the network.
 *
 * Extends the core class so it satisfies the return type of the seam it is
 * injected through; only the four methods the client actually uses are replaced.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stub_curl extends \curl {
    /** @var string Body returned by post(). */
    public string $body = '';

    /** @var int HTTP status reported by get_info(). */
    public int $code = 200;

    /**
     * Marks the next call as a transport failure.
     *
     * The parent already declares $errno and $error untyped, so they are assigned
     * rather than redeclared: PHP rejects narrowing an inherited property's type.
     *
     * @param int $errno Curl error number.
     * @param string $message Curl error text.
     * @return void
     */
    public function fail_transport(int $errno, string $message): void {
        $this->errno = $errno;
        $this->error = $message;
    }

    #[\Override]
    public function post($url, $params = '', $options = []) {
        return $this->body;
    }

    #[\Override]
    public function get_info() {
        return ['http_code' => $this->code];
    }

    #[\Override]
    public function get_errno() {
        return $this->errno;
    }
}
