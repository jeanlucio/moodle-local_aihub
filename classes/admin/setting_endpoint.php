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
 * Admin setting for the OpenAI-compatible endpoint.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\admin;

use local_aihub\local\client;

/**
 * A URL setting that refuses what a request would later refuse.
 *
 * Plain http, localhost and private networks are blocked at call time to prevent SSRF, so
 * accepting them here would answer "saved" and then ignore the value on every request.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_endpoint extends \admin_setting_configtext {
    /**
     * Creates the setting as a URL field.
     *
     * @param string $name Unique setting name, with its plugin prefix.
     * @param string $visiblename Localised label.
     * @param string $description Localised help.
     * @param string $defaultsetting The default value.
     */
    public function __construct($name, $visiblename, $description, $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, PARAM_URL);
    }

    #[\Override]
    public function validate($data) {
        $valid = parent::validate($data);
        if ($valid !== true) {
            return $valid;
        }

        // The plugin's own default is a known public endpoint. Checking it needs a DNS lookup,
        // and where there is none the default could not even be applied on install. Requests
        // still check it, against the addresses they connect to.
        $url = trim((string) $data);
        if ($url === $this->get_defaultsetting()) {
            return true;
        }

        $problem = $this->client()->endpoint_problem($url);

        return $problem === '' ? true : $problem;
    }

    /**
     * Builds the client whose rule the value is checked against.
     *
     * @return client
     */
    protected function client(): client {
        return new client();
    }
}
