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
// The stub_curl fixture extends the core class, which must exist when it is defined.
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/local/aihub/tests/fixtures/mock_client.php');
require_once($CFG->dirroot . '/local/aihub/tests/fixtures/dns_stub_client.php');
require_once($CFG->dirroot . '/local/aihub/tests/fixtures/recording_client.php');
require_once($CFG->dirroot . '/local/aihub/tests/fixtures/stub_curl.php');
require_once($CFG->dirroot . '/local/aihub/tests/fixtures/stub_transport_client.php');

/**
 * Tests for {@see client}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_aihub\local\client
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
     * A URL that parses but carries no host is rejected before any lookup.
     *
     * @return void
     */
    public function test_is_safe_url_rejects_a_missing_host(): void {
        $client = new client();

        $this->assertFalse($this->call_protected($client, 'is_safe_url', ['https:///v1/chat']));
    }

    /**
     * A hostname resolving to a private IP is blocked (anti DNS-rebinding).
     *
     * @return void
     */
    public function test_is_safe_url_blocks_dns_rebinding(): void {
        $client = new dns_stub_client();
        $client->dnsresult = ['10.0.0.5'];

        $this->assertFalse($this->call_protected($client, 'is_safe_url', ['https://internal.example.com/v1']));
    }

    /**
     * A hostname resolving only to public IPs passes.
     *
     * @return void
     */
    public function test_is_safe_url_allows_public_dns_resolution(): void {
        $client = new dns_stub_client();
        $client->dnsresult = ['8.8.8.8'];

        $this->assertTrue($this->call_protected($client, 'is_safe_url', ['https://api.example.com/v1']));
    }

    /**
     * A hostname with no resolvable DNS records is allowed through (nothing to block).
     *
     * @return void
     */
    public function test_is_safe_url_allows_when_dns_resolves_to_nothing(): void {
        $client = new dns_stub_client();
        $client->dnsresult = [];

        $this->assertTrue($this->call_protected($client, 'is_safe_url', ['https://unresolvable.example.com/v1']));
    }

    /**
     * A bare base URL gets /chat/completions appended; a full path is preserved.
     *
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
     * Gemini and Groq failing falls through to DeepSeek within the same tier.
     *
     * @return void
     */
    public function test_deepseek_fallthrough_within_tier(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('gemini_key', 'site-gemini', 'local_aihub');
        set_config('groq_key', 'site-groq', 'local_aihub');
        set_config('deepseek_key', 'site-deepseek', 'local_aihub');

        $client = new mock_client();
        $client->results['Gemini'] = ['success' => false, 'message' => 'Gemini: down', 'provider' => 'Gemini'];
        $client->results['Groq'] = ['success' => false, 'message' => 'Groq: down', 'provider' => 'Groq'];
        $client->results['DeepSeek'] = [
            'success'  => true,
            'data'     => 'ok',
            'provider' => 'DeepSeek',
            'model'    => 'deepseek-v4-flash',
        ];

        $result = $client->generate_text('', 'hello');

        $this->assertTrue($result['success']);
        $this->assertSame('DeepSeek', $result['provider']);
        $this->assertSame(['Gemini', 'Groq', 'DeepSeek'], $client->calls);
    }

    /**
     * With no key configured the client reports failure and calls no provider.
     *
     * @return void
     */
    public function test_no_key_returns_failure(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $client = new mock_client();
        $result = $client->generate_text('', 'hello');

        $this->assertFalse($result['success']);
        $this->assertSame([], $client->calls);
        $this->assertSame([], $result['attempts']);
    }

    /**
     * Every provider called is reported back, including the ones that failed.
     *
     * A failure followed by a success used to be overwritten by the winning result
     * and never reached the caller, which is what let a dead key stay invisible.
     *
     * @return void
     */
    public function test_attempts_keep_a_failure_covered_by_a_later_success(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('gemini_key', 'site-gemini', 'local_aihub');
        set_config('groq_key', 'site-groq', 'local_aihub');

        $client = new mock_client();
        $client->results['Gemini'] = ['success' => false, 'message' => 'Gemini: down', 'provider' => 'Gemini'];
        $client->results['Groq'] = [
            'success'  => true,
            'data'     => 'ok',
            'provider' => 'Groq',
            'model'    => 'openai/gpt-oss-120b',
        ];

        $result = $client->generate_text('', 'hello');

        $this->assertCount(2, $result['attempts']);

        $this->assertSame('Gemini', $result['attempts'][0]['provider']);
        $this->assertFalse($result['attempts'][0]['success']);
        $this->assertSame('Gemini: down', $result['attempts'][0]['message']);
        $this->assertSame('site', $result['attempts'][0]['keysource']);

        $this->assertSame('Groq', $result['attempts'][1]['provider']);
        $this->assertTrue($result['attempts'][1]['success']);
        $this->assertSame('openai/gpt-oss-120b', $result['attempts'][1]['model']);
    }

    /**
     * Attempts span both tiers, so a personal key that fails is not lost when a
     * site key answers.
     *
     * @return void
     */
    public function test_attempts_span_both_key_tiers(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enablepersonalkeys', 1, 'local_aihub');

        keys::save_user_key(keys::PROVIDER_GEMINI, 'personal-gemini');
        set_config('gemini_key', 'site-gemini', 'local_aihub');

        $client = new mock_client();
        $client->results['Gemini'] = ['success' => false, 'message' => 'Gemini: down', 'provider' => 'Gemini'];

        $result = $client->generate_text('', 'hello');

        $this->assertFalse($result['success']);
        $this->assertCount(2, $result['attempts']);
        $this->assertSame('personal', $result['attempts'][0]['keysource']);
        $this->assertSame('site', $result['attempts'][1]['keysource']);
    }

    /**
     * Gemini gets the request shape its API expects, which is not the shape the
     * other three get: the system prompt is a field of its own and JSON mode is
     * asked for through the generation config.
     *
     * @return void
     */
    public function test_gemini_request_shape(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('gemini_key', 'site-gemini', 'local_aihub');

        $client = new recording_client();
        $client->generate_text('Be terse.', 'hello', true);

        $this->assertCount(1, $client->requests);
        $request = $client->requests[0];

        $this->assertSame('Gemini', $request['source']);
        $this->assertStringContainsString('gemini-flash-latest:generateContent', $request['url']);
        $this->assertContains('x-goog-api-key: site-gemini', $request['headers']);

        $this->assertSame('hello', $request['payload']['contents'][0]['parts'][0]['text']);
        $this->assertSame('Be terse.', $request['payload']['systemInstruction']['parts'][0]['text']);
        $this->assertSame('application/json', $request['payload']['generationConfig']['responseMimeType']);
        // The chat-style keys belong to the other providers and must not leak here.
        $this->assertArrayNotHasKey('messages', $request['payload']);
        $this->assertArrayNotHasKey('response_format', $request['payload']);
    }

    /**
     * An empty system prompt is left out rather than sent as an empty instruction.
     *
     * @return void
     */
    public function test_gemini_omits_an_empty_system_prompt(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('gemini_key', 'site-gemini', 'local_aihub');

        $client = new recording_client();
        $client->generate_text('', 'hello', false);

        $this->assertArrayNotHasKey('systemInstruction', $client->requests[0]['payload']);
        $this->assertArrayNotHasKey('generationConfig', $client->requests[0]['payload']);
    }

    /**
     * The three chat-completions providers each send their own model id to their
     * own endpoint, with the system prompt as the first message.
     *
     * @return void
     */
    public function test_chat_completion_request_shapes(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('groq_key', 'site-groq', 'local_aihub');
        set_config('deepseek_key', 'site-deepseek', 'local_aihub');
        set_config('openai_key', 'site-openai', 'local_aihub');
        set_config('openai_baseurl', 'https://api.example.com/v1', 'local_aihub');
        set_config('openai_model', 'some-model', 'local_aihub');

        $client = new recording_client();
        $client->response = ['success' => false, 'message' => 'stub'];
        $client->generate_text('Be terse.', 'hello', true);

        $expected = [
            'Groq' => ['https://api.groq.com/openai/v1/chat/completions', 'openai/gpt-oss-120b'],
            'DeepSeek' => ['https://api.deepseek.com/chat/completions', 'deepseek-v4-flash'],
            'OpenAI' => ['https://api.example.com/v1/chat/completions', 'some-model'],
        ];

        $this->assertSame(array_keys($expected), array_column($client->requests, 'source'));

        foreach ($client->requests as $request) {
            [$url, $model] = $expected[$request['source']];
            $this->assertSame($url, $request['url']);
            $this->assertSame($model, $request['payload']['model']);
            $this->assertSame('json_object', $request['payload']['response_format']['type']);
            $this->assertSame(
                [['role' => 'system', 'content' => 'Be terse.'], ['role' => 'user', 'content' => 'hello']],
                $request['payload']['messages']
            );
        }
    }

    /**
     * The OpenAI leg prefers the user's own endpoint and model, falling back to the
     * site values field by field rather than all or nothing.
     *
     * @return void
     */
    public function test_openai_personal_endpoint_falls_back_field_by_field(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enablepersonalkeys', 1, 'local_aihub');
        set_config('openai_baseurl', 'https://site.example.com/v1', 'local_aihub');
        set_config('openai_model', 'site-model', 'local_aihub');

        keys::save_user_key(keys::PROVIDER_OPENAI, 'personal-openai');
        keys::save_user_openai_model('personal-model');

        $client = new recording_client();
        $client->generate_text('', 'hello');

        // The personal model was set but no personal URL, so each is resolved on its own.
        $this->assertSame('https://site.example.com/v1/chat/completions', $client->requests[0]['url']);
        $this->assertSame('personal-model', $client->requests[0]['payload']['model']);
        $this->assertContains('Authorization: Bearer personal-openai', $client->requests[0]['headers']);
    }

    /**
     * The mirror of the case above: a personal endpoint with no personal model
     * borrows the site model rather than sending none.
     *
     * @return void
     */
    public function test_openai_personal_url_without_a_personal_model(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enablepersonalkeys', 1, 'local_aihub');
        set_config('openai_baseurl', 'https://site.example.com/v1', 'local_aihub');
        set_config('openai_model', 'site-model', 'local_aihub');

        keys::save_user_key(keys::PROVIDER_OPENAI, 'personal-openai');
        keys::save_user_openai_url('https://personal.example.com/v1');

        $client = new recording_client();
        $client->generate_text('', 'hello');

        $this->assertSame('https://personal.example.com/v1/chat/completions', $client->requests[0]['url']);
        $this->assertSame('site-model', $client->requests[0]['payload']['model']);
    }

    /**
     * Gemini nests its answer differently from the chat-completions providers, and
     * the parser has to know which is which.
     *
     * @return void
     */
    public function test_response_parsing_per_provider(): void {
        $client = new stub_transport_client();

        $client->curl->body = json_encode([
            'candidates' => [['content' => ['parts' => [['text' => 'from gemini']]]]],
        ]);
        $gemini = $client->post_for_testing('https://x', '{}', [], 'Gemini');
        $this->assertTrue($gemini['success']);
        $this->assertSame('from gemini', $gemini['data']);

        $client->curl->body = json_encode([
            'choices' => [['message' => ['content' => 'from groq']]],
        ]);
        $groq = $client->post_for_testing('https://x', '{}', [], 'Groq');
        $this->assertTrue($groq['success']);
        $this->assertSame('from groq', $groq['data']);
    }

    /**
     * An error body is unwrapped into the sentence the provider wrote; a status
     * with no usable body degrades to the code rather than to an empty reason.
     *
     * @return void
     */
    public function test_error_responses_keep_the_provider_wording(): void {
        $client = new stub_transport_client();

        $client->curl->code = 429;
        $client->curl->body = json_encode(['error' => ['message' => 'Quota exceeded']]);
        $withmessage = $client->post_for_testing('https://x', '{}', [], 'Gemini');
        $this->assertFalse($withmessage['success']);
        $this->assertSame('Gemini: Quota exceeded', $withmessage['message']);
        $this->assertSame('Gemini', $withmessage['provider']);

        $client->curl->code = 502;
        $client->curl->body = 'upstream exploded';
        $withoutmessage = $client->post_for_testing('https://x', '{}', [], 'Groq');
        $this->assertFalse($withoutmessage['success']);
        $this->assertSame('Groq: HTTP 502', $withoutmessage['message']);
    }

    /**
     * A transport failure is reported as one, before any status is considered.
     *
     * @return void
     */
    public function test_transport_failure_is_reported(): void {
        $client = new stub_transport_client();
        $client->curl->fail_transport(28, 'Operation timed out');

        $result = $client->post_for_testing('https://x', '{}', [], 'DeepSeek');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('DeepSeek:', $result['message']);
    }

    /**
     * A 200 that carries no text is a failure, so the chain moves on to the next provider and
     * the usage log does not record a success that produced nothing.
     *
     * @return void
     */
    public function test_a_body_without_text_is_a_failure(): void {
        $client = new stub_transport_client();
        $client->curl->body = json_encode(['unexpected' => true]);

        $result = $client->post_for_testing('https://x', '{}', [], 'Gemini');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Gemini', $result['message']);
    }

    /**
     * A body that is not JSON at all, such as a gateway's HTML error page served with 200.
     *
     * @return void
     */
    public function test_a_body_that_is_not_json_is_a_failure(): void {
        $client = new stub_transport_client();
        $client->curl->body = '<html>Bad gateway</html>';

        $result = $client->post_for_testing('https://x', '{}', [], 'OpenAI');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('OpenAI', $result['message']);
    }

    /**
     * Gemini answers 200 without candidates when it blocks a prompt, and says why.
     *
     * @return void
     */
    public function test_a_blocked_gemini_prompt_reports_the_reason(): void {
        $client = new stub_transport_client();
        $client->curl->body = json_encode(['promptFeedback' => ['blockReason' => 'SAFETY']]);

        $result = $client->post_for_testing('https://x', '{}', [], 'Gemini');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SAFETY', $result['message']);
    }

    /**
     * Gemini also stops a generation mid-way and leaves no parts, giving the reason apart.
     *
     * @return void
     */
    public function test_a_gemini_candidate_without_parts_reports_the_finish_reason(): void {
        $client = new stub_transport_client();
        $client->curl->body = json_encode(['candidates' => [['finishReason' => 'RECITATION']]]);

        $result = $client->post_for_testing('https://x', '{}', [], 'Gemini');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('RECITATION', $result['message']);
    }

    /**
     * An OpenAI-style answer with an empty message, as a content filter produces.
     *
     * @return void
     */
    public function test_an_empty_chat_completion_reports_the_finish_reason(): void {
        $client = new stub_transport_client();
        $client->curl->body = json_encode(['choices' => [['message' => ['content' => ''], 'finish_reason' => 'content_filter']]]);

        $result = $client->post_for_testing('https://x', '{}', [], 'Groq');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('content_filter', $result['message']);
    }

    /**
     * The next provider answers when the first one returns nothing.
     *
     * @return void
     */
    public function test_an_empty_answer_hands_over_to_the_next_provider(): void {
        $this->resetAfterTest();
        set_config('gemini_key', 'site-gemini', 'local_aihub');
        set_config('groq_key', 'site-groq', 'local_aihub');

        $client = new class extends stub_transport_client {
            /** @var string[] Providers that were asked, in order. */
            public array $asked = [];

            #[\Override]
            protected function http_post(string $url, string $payload, array $headers, string $source): array {
                $this->asked[] = $source;
                $this->curl->body = $source === 'Gemini'
                    ? json_encode(['promptFeedback' => ['blockReason' => 'SAFETY']])
                    : json_encode(['choices' => [['message' => ['content' => 'from groq']]]]);

                return parent::http_post($url, $payload, $headers, $source);
            }
        };

        $result = $client->generate_text('', 'hello', false, 0);

        $this->assertTrue($result['success']);
        $this->assertSame('from groq', $result['data']);
        $this->assertSame(['Gemini', 'Groq'], $client->asked);
        $this->assertFalse($result['attempts'][0]['success']);
        $this->assertTrue($result['attempts'][1]['success']);
    }

    /**
     * A real answer is still a success.
     *
     * @return void
     */
    public function test_a_body_with_text_is_a_success(): void {
        $client = new stub_transport_client();
        $client->curl->body = json_encode(['candidates' => [['content' => ['parts' => [['text' => 'hello']]]]]]);

        $result = $client->post_for_testing('https://x', '{}', [], 'Gemini');

        $this->assertTrue($result['success']);
        $this->assertSame('hello', $result['data']);
    }

    /**
     * An endpoint that is refused leaves a failed attempt and a reason, instead of vanishing
     * from the log and from the answer.
     *
     * @return void
     */
    public function test_a_refused_endpoint_is_reported(): void {
        $this->resetAfterTest();
        set_config('openai_key', 'site-openai', 'local_aihub');
        set_config('openai_baseurl', 'http://192.168.0.10:11434/v1', 'local_aihub');

        $result = (new client())->generate_text('', 'hello', false, 0);

        $this->assertFalse($result['success']);
        $this->assertNotSame('', $result['message']);
        $this->assertCount(1, $result['attempts']);
        $this->assertFalse($result['attempts'][0]['success']);
        $this->assertSame('OpenAI', $result['attempts'][0]['provider']);
        $this->assertNotSame('', $result['attempts'][0]['message']);
    }

    /**
     * The same rule the call applies is available up front, to refuse the value on save.
     *
     * @return void
     */
    public function test_endpoint_problem_matches_what_the_call_accepts(): void {
        $client = new client();

        $this->assertNotSame('', $client->endpoint_problem('http://8.8.8.8/v1'));
        $this->assertNotSame('', $client->endpoint_problem('https://10.0.0.5/v1'));
        $this->assertNotSame('', $client->endpoint_problem('https://localhost/v1'));
        $this->assertSame('', $client->endpoint_problem('https://8.8.8.8/v1'));
        $this->assertSame('', $client->endpoint_problem(''), 'empty means the default endpoint');
    }

    /**
     * A course teacher's own key is tried first when the hub is called on their behalf,
     * which is what lets work running without a user, such as cron, use the owner's key.
     *
     * @return void
     */
    public function test_a_course_teachers_personal_tier_is_tried_first(): void {
        $this->resetAfterTest();
        set_config('enablepersonalkeys', 1, 'local_aihub');
        set_config('gemini_key', 'site-gemini', 'local_aihub');
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        keys::save_user_key(keys::PROVIDER_GROQ, 'personal-groq', (int) $teacher->id);

        $client = new mock_client();
        $client->results['Groq'] = ['success' => true, 'data' => 'ok', 'provider' => 'Groq', 'model' => 'm'];

        $result = $client->generate_text('', 'hello', false, (int) $teacher->id);

        $this->assertTrue($result['success']);
        $this->assertSame('personal', $result['keysource']);
        $this->assertSame(['Groq'], $client->calls);
    }

    /**
     * Something that is not a web address is a problem to report, not an empty value. A form
     * reads the field with PARAM_URL, which turns what it cannot parse into an empty string,
     * and an empty string is the instruction to forget the endpoint.
     *
     * @return void
     */
    public function test_a_value_that_is_not_a_web_address_is_reported(): void {
        $client = new client();

        $this->assertNotSame('', $client->endpoint_problem('https://[2001:db8::1]/v1'));
        $this->assertNotSame('', $client->endpoint_problem('not a url'));
        $this->assertNotSame('', $client->endpoint_problem('https://exa mple.com/v1'));
        $this->assertSame('', $client->endpoint_problem('https://8.8.8.8/v1'));
    }
}
