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
 * Tests for the BYOK key store and resolution.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * Tests for {@see keys}.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_aihub\local\keys
 */
final class keys_test extends \advanced_testcase {
    /**
     * OpenAI base URL and model fall back to sane defaults when unset.
     *
     * @covers ::get_openai_baseurl
     * @covers ::get_openai_model
     * @return void
     */
    public function test_openai_defaults(): void {
        $this->resetAfterTest();

        $this->assertSame('https://api.openai.com/v1', keys::get_openai_baseurl());
        $this->assertSame('gpt-4o-mini', keys::get_openai_model());

        set_config('openai_baseurl', 'https://openrouter.ai/api/v1', 'local_aihub');
        set_config('openai_model', 'gpt-4o', 'local_aihub');
        $this->assertSame('https://openrouter.ai/api/v1', keys::get_openai_baseurl());
        $this->assertSame('gpt-4o', keys::get_openai_model());
    }

    /**
     * Personal keys are saved, read back and cleared via user preferences.
     *
     * @covers ::save_user_key
     * @covers ::get_personal_key
     * @covers ::has_personal_key
     * @return void
     */
    public function test_personal_key_roundtrip(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertSame('', keys::get_personal_key(keys::PROVIDER_GEMINI));
        $this->assertFalse(keys::has_personal_key());

        keys::save_user_key(keys::PROVIDER_GEMINI, 'personal-abc');
        $this->assertSame('personal-abc', keys::get_personal_key(keys::PROVIDER_GEMINI));
        $this->assertTrue(keys::has_personal_key());

        keys::save_user_key(keys::PROVIDER_GEMINI, '');
        $this->assertSame('', keys::get_personal_key(keys::PROVIDER_GEMINI));
        $this->assertFalse(keys::has_personal_key());
    }

    /**
     * Personal keys require both the site toggle and the per-user capability.
     *
     * @covers ::personal_keys_allowed
     * @return void
     */
    public function test_personal_keys_allowed(): void {
        $this->resetAfterTest();

        // Toggle off: never allowed, even for an admin.
        $this->setAdminUser();
        set_config('enablepersonalkeys', 0, 'local_aihub');
        $this->assertFalse(keys::personal_keys_allowed());

        // Toggle on plus capability (admin has it): allowed.
        set_config('enablepersonalkeys', 1, 'local_aihub');
        $this->assertTrue(keys::personal_keys_allowed());

        // Toggle on but a plain user without the capability: not allowed.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertFalse(keys::personal_keys_allowed());
    }

    /**
     * Resolution prefers the personal key when allowed, otherwise the site key.
     *
     * @covers ::get_key
     * @return void
     */
    public function test_get_key_resolution(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enablepersonalkeys', 1, 'local_aihub');
        set_config('gemini_key', 'site-key', 'local_aihub');

        // No personal key: falls back to site.
        $this->assertSame('site-key', keys::get_key(keys::PROVIDER_GEMINI));

        // Personal key set and allowed: personal wins.
        keys::save_user_key(keys::PROVIDER_GEMINI, 'personal-key');
        $this->assertSame('personal-key', keys::get_key(keys::PROVIDER_GEMINI));

        // Toggle off: personal ignored, site used even though a personal key exists.
        set_config('enablepersonalkeys', 0, 'local_aihub');
        $this->assertSame('site-key', keys::get_key(keys::PROVIDER_GEMINI));
    }

    /**
     * Availability reflects site keys, and personal keys only when allowed.
     *
     * @covers ::has_any_key
     * @return void
     */
    public function test_has_any_key(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Nothing configured.
        $this->assertFalse(keys::has_any_key());

        // A personal key the user is not allowed to use does not count.
        keys::save_user_key(keys::PROVIDER_GROQ, 'personal-groq');
        $this->assertFalse(keys::has_any_key());

        // A site key always counts.
        set_config('groq_key', 'site-groq', 'local_aihub');
        $this->assertTrue(keys::has_any_key());
    }
}
