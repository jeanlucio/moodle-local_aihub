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
 * Tests for the BYOK transport client.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/aihub/tests/fixtures/mock_client.php');

/**
 * Tests for {@see client}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_aihub\local\client
 */
final class client_test extends \advanced_testcase {
    /**
     * Invokes a protected method on a client instance via reflection.
     *
     * @param client $client The client instance.
     * @param string $method The protected method name.
     * @param array $args Positional arguments.
     * @return mixed The method return value.
     */
    private function call_protected(client $client, string $method, array $args) {
        $reflection = new \ReflectionMethod(client::class, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($client, $args);
    }

    /**
     * Non-HTTPS, loopback and private addresses are rejected; a public IP passes.
     *
     * Only literal IPs and the loopback host are used so no DNS lookup is needed.
     *
     * @covers ::is_safe_url
     * @return void
     */
    public function test_is_safe_url(): void {
        $client = new client();

        $this->assertFalse($this->call_protected($client, 'is_safe_url', ['http://8.8.8.8/chat/completions']));
        $this->assertFalse($this->call_protected($client, 'is_safe_url', ['https://localhost/v1']));
        $this->assertFalse($this->call_protected($client, 'is_safe_url', ['https://127.0.0.1/v1']));
        $this->assertFalse($this->call_protected($client, 'is_safe_url', ['https://10.0.0.5/v1']));
        $this->assertFalse($this->call_protected($client, 'is_safe_url', ['https://192.168.1.1/v1']));
        $this->assertFalse($this->call_protected($client, 'is_safe_url', ['ftp://8.8.8.8/v1']));

        $this->assertTrue($this->call_protected($client, 'is_safe_url', ['https://8.8.8.8/chat/completions']));
    }

    /**
     * A bare base URL gets /chat/completions appended; a full path is preserved.
     *
     * @covers ::resolve_openai_url
     * @return void
     */
    public function test_resolve_openai_url(): void {
        $client = new client();

        $this->assertSame(
            'https://api.openai.com/v1/chat/completions',
            $this->call_protected($client, 'resolve_openai_url', ['https://api.openai.com/v1'])
        );
        $this->assertSame(
            'https://api.openai.com/v1/chat/completions',
            $this->call_protected($client, 'resolve_openai_url', ['https://api.openai.com/v1/'])
        );
        $this->assertSame(
            'https://example.com/chat/completions',
            $this->call_protected($client, 'resolve_openai_url', ['https://example.com/chat/completions'])
        );
    }

    /**
     * The personal tier is tried before the site tier, in provider order.
     *
     * @covers ::generate_text
     * @covers ::try_key_tier
     * @return void
     */
    public function test_personal_tier_wins_over_site(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enablepersonalkeys', 1, 'local_aihub');

        keys::save_user_key(keys::PROVIDER_GROQ, 'personal-groq');
        set_config('gemini_key', 'site-gemini', 'local_aihub');

        $client = new mock_client();
        $client->results['Groq'] = ['success' => true, 'data' => 'ok', 'provider' => 'Groq', 'model' => 'openai/gpt-oss-120b'];

        $result = $client->generate_text('', 'hello');

        $this->assertTrue($result['success']);
        $this->assertSame('Groq', $result['provider']);
        // Personal Groq resolved before the site tier was reached.
        $this->assertSame(['Groq'], $client->calls);
    }

    /**
     * Within a tier, a failing provider falls through to the next one.
     *
     * @covers ::generate_text
     * @covers ::try_key_tier
     * @return void
     */
    public function test_provider_fallthrough_within_tier(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('gemini_key', 'site-gemini', 'local_aihub');
        set_config('groq_key', 'site-groq', 'local_aihub');

        $client = new mock_client();
        $client->results['Gemini'] = ['success' => false, 'message' => 'Gemini: down', 'provider' => 'Gemini'];
        $client->results['Groq'] = ['success' => true, 'data' => 'ok', 'provider' => 'Groq', 'model' => 'openai/gpt-oss-120b'];

        $result = $client->generate_text('', 'hello');

        $this->assertTrue($result['success']);
        $this->assertSame('Groq', $result['provider']);
        $this->assertSame(['Gemini', 'Groq'], $client->calls);
    }

    /**
     * With no key configured the client reports failure and calls no provider.
     *
     * @covers ::generate_text
     * @return void
     */
    public function test_no_key_returns_failure(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $client = new mock_client();
        $result = $client->generate_text('', 'hello');

        $this->assertFalse($result['success']);
        $this->assertSame([], $client->calls);
    }
}
