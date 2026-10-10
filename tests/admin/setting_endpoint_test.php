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
 * Tests for the endpoint admin setting.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\admin;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/aihub/tests/fixtures/dns_stub_client.php');

/**
 * Tests for {@see setting_endpoint}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_aihub\admin\setting_endpoint
 */
final class setting_endpoint_test extends \advanced_testcase {
    /**
     * Builds the setting the way settings.php does.
     *
     * @return setting_endpoint
     */
    private function setting(): setting_endpoint {
        return new setting_endpoint('local_aihub/openai_baseurl', 'Endpoint', 'Help', 'https://api.openai.com/v1');
    }

    /**
     * A value the call would later refuse is refused on save, with the reason, instead of
     * being reported as saved and then ignored.
     *
     * @return void
     */
    public function test_an_endpoint_that_would_be_refused_is_not_saved(): void {
        $this->resetAfterTest();

        foreach (['http://8.8.8.8/v1', 'https://10.0.0.5/v1', 'https://localhost:11434/v1'] as $url) {
            $error = $this->setting()->write_setting($url);
            $this->assertNotSame('', $error, $url . ' should be refused');
            $this->assertNotSame($url, get_config('local_aihub', 'openai_baseurl'));
        }
    }

    /**
     * A public HTTPS endpoint is stored, and so is an empty value, which means the default.
     *
     * @return void
     */
    public function test_a_public_https_endpoint_is_saved(): void {
        $this->resetAfterTest();

        $this->assertSame('', $this->setting()->write_setting('https://8.8.8.8/v1'));
        $this->assertSame('https://8.8.8.8/v1', get_config('local_aihub', 'openai_baseurl'));
        $this->assertSame('', $this->setting()->write_setting(''));
    }

    /**
     * Something that cannot be read as a web address is refused too, instead of being stored
     * as an empty value that would make the default endpoint apply without a word.
     *
     * @return void
     */
    public function test_a_value_that_is_not_a_web_address_is_not_saved(): void {
        $this->resetAfterTest();
        set_config('openai_baseurl', 'https://8.8.8.8/v1', 'local_aihub');

        $error = $this->setting()->write_setting('https://[2001:db8::1]/v1');

        $this->assertNotSame('', $error);
        $this->assertSame('https://8.8.8.8/v1', get_config('local_aihub', 'openai_baseurl'));
    }

    /**
     * The plugin's own default is written without a DNS lookup, as it is on install, so a
     * server that cannot resolve names still gets it. Another host that resolves to nothing
     * is still refused.
     *
     * @return void
     */
    public function test_the_default_is_saved_without_dns(): void {
        $this->resetAfterTest();
        $default = 'https://api.openai.com/v1';
        $setting = new class ('local_aihub/openai_baseurl', 'Endpoint', 'Help', $default) extends setting_endpoint {
            #[\Override]
            protected function client(): \local_aihub\local\client {
                $client = new \local_aihub\local\dns_stub_client();
                $client->dnsresult = [];

                return $client;
            }
        };

        $this->assertSame('', $setting->write_setting('https://api.openai.com/v1'));
        $this->assertSame('https://api.openai.com/v1', get_config('local_aihub', 'openai_baseurl'));
        $this->assertNotSame('', $setting->write_setting('https://api.example.com/v1'));
        $this->assertSame('https://api.openai.com/v1', get_config('local_aihub', 'openai_baseurl'));
    }
}
