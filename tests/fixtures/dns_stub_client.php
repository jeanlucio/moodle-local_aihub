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
 * Test double for exercising is_safe_url's DNS-rebinding branch without real DNS lookups.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * A client whose DNS resolution is programmable, while is_safe_url runs for real.
 *
 * Unlike {@see mock_client}, this double does not stub is_safe_url() itself, so
 * its real logic (HTTPS check, loopback/private-range blocking, and the public-ip
 * check on resolved addresses) is exercised end to end.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dns_stub_client extends client {
    /** @var string[] Programmed DNS resolution result, returned regardless of host. */
    public array $dnsresult = [];

    #[\Override]
    protected function resolve_dns(string $host): array {
        return $this->dnsresult;
    }
}
