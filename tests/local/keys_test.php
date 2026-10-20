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
 * @covers     \local_aihub\local\keys
 */
final class keys_test extends \advanced_testcase {
    /**
     * OpenAI base URL and model fall back to sane defaults when unset.
     *
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
     * Clearing the settings falls back to the built-in values rather than sending a
     * request to an empty URL with no model.
     *
     * The admin form accepts an empty field, and the defaults declared in
     * settings.php happen to match these, so a test that only reads the untouched
     * settings never reaches this branch.
     *
     * @return void
     */
    public function test_openai_defaults_when_the_settings_are_blanked(): void {
        $this->resetAfterTest();

        set_config('openai_baseurl', '', 'local_aihub');
        set_config('openai_model', '', 'local_aihub');

        $this->assertSame('https://api.openai.com/v1', keys::get_openai_baseurl());
        $this->assertSame('gpt-4o-mini', keys::get_openai_model());
    }

    /**
     * Personal keys are saved, read back and cleared via user preferences.
     *
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
     * The personal OpenAI-compatible base URL and model are saved, read back and cleared.
     *
     * @return void
     */
    public function test_personal_openai_roundtrip(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertSame('', keys::get_personal_openai_url());
        $this->assertSame('', keys::get_personal_openai_model());

        keys::save_user_openai_url('https://openrouter.ai/api/v1');
        keys::save_user_openai_model('gpt-4o');
        $this->assertSame('https://openrouter.ai/api/v1', keys::get_personal_openai_url());
        $this->assertSame('gpt-4o', keys::get_personal_openai_model());

        keys::save_user_openai_url('');
        keys::save_user_openai_model('');
        $this->assertSame('', keys::get_personal_openai_url());
        $this->assertSame('', keys::get_personal_openai_model());
    }

    /**
     * Personal keys require both the site toggle and the per-user capability.
     *
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

    /**
     * Once personal keys are allowed, the user's own key is enough on its own, with
     * no site key configured at all.
     *
     * @return void
     */
    public function test_has_any_key_counts_a_permitted_personal_key(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enablepersonalkeys', 1, 'local_aihub');

        $this->assertFalse(keys::has_any_key());

        keys::save_user_key(keys::PROVIDER_GROQ, 'personal-groq');
        $this->assertTrue(keys::has_any_key());
    }

    /**
     * A teacher gets the role from the course enrolment, whose capabilities never reach the
     * system context. The permission has to be found where the role actually is.
     *
     * @return void
     */
    public function test_a_course_teacher_may_use_personal_keys(): void {
        $this->resetAfterTest();
        set_config('enablepersonalkeys', 1, 'local_aihub');
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $this->assertFalse(has_capability(
            'local/aihub:usepersonalkey',
            \context_system::instance(),
            $teacher
        ), 'the premise: the system context does not grant it to a course teacher');
        $this->assertTrue(keys::personal_keys_allowed((int) $teacher->id));
    }

    /**
     * Students, people with no role anywhere and the system user are not offered personal keys,
     * and the site switch still wins over the capability.
     *
     * @return void
     */
    public function test_only_people_who_teach_may_use_personal_keys(): void {
        $this->resetAfterTest();
        set_config('enablepersonalkeys', 1, 'local_aihub');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $nobody = $this->getDataGenerator()->create_user();

        $this->assertFalse(keys::personal_keys_allowed((int) $student->id));
        $this->assertFalse(keys::personal_keys_allowed((int) $nobody->id));
        $this->assertFalse(keys::personal_keys_allowed(0), 'cron has no user and never has a personal key');

        set_config('enablepersonalkeys', 0, 'local_aihub');
        $this->assertFalse(keys::personal_keys_allowed((int) $teacher->id));
    }

    /**
     * Being a student in other courses takes nothing away from someone who teaches in one.
     *
     * @return void
     */
    public function test_teaching_in_one_course_is_enough(): void {
        $this->resetAfterTest();
        set_config('enablepersonalkeys', 1, 'local_aihub');
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $generator->create_course()->id, 'student');
        $generator->enrol_user($user->id, $generator->create_course()->id, 'student');
        $this->assertFalse(keys::personal_keys_allowed((int) $user->id));

        $generator->enrol_user($user->id, $generator->create_course()->id, 'editingteacher');
        $this->assertTrue(keys::personal_keys_allowed((int) $user->id));
    }

    /**
     * The personal key of a course teacher is the one resolved for them, and availability
     * counts it, so a site with no site key at all still has an AI source for that teacher.
     *
     * @return void
     */
    public function test_a_course_teachers_personal_key_resolves(): void {
        $this->resetAfterTest();
        set_config('enablepersonalkeys', 1, 'local_aihub');
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        keys::save_user_key(keys::PROVIDER_GEMINI, 'teacher-key', (int) $teacher->id);

        $this->assertSame('teacher-key', keys::get_key(keys::PROVIDER_GEMINI, (int) $teacher->id));
        $this->assertTrue(keys::has_any_key((int) $teacher->id));
    }

    /**
     * A model name longer than the log column would later make every generation for that
     * user fail, so it is cut when it is saved.
     *
     * @return void
     */
    public function test_a_long_model_name_is_cut_to_what_the_log_can_hold(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        keys::save_user_openai_model(str_repeat('m', 300), (int) $user->id);

        $this->assertSame(100, \core_text::strlen(keys::get_personal_openai_model((int) $user->id)));
    }

    /**
     * With nothing chosen anywhere the order is the built-in one, which is what the hub did
     * before the order could be changed.
     *
     * @return void
     */
    public function test_provider_order_is_the_built_in_one_by_default(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $this->assertSame(
            [keys::PROVIDER_GEMINI, keys::PROVIDER_GROQ, keys::PROVIDER_DEEPSEEK, keys::PROVIDER_OPENAI],
            keys::provider_order((int) $user->id)
        );
        $this->assertSame(keys::providers(), keys::provider_order((int) $user->id));
    }

    /**
     * The site's order goes first, in the order the administrator gave, and what is left of the
     * built-in order follows.
     *
     * @return void
     */
    public function test_provider_order_follows_the_site_order(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        keys::save_site_provider_order([keys::PROVIDER_OPENAI, keys::PROVIDER_GROQ]);

        $this->assertSame(
            [keys::PROVIDER_OPENAI, keys::PROVIDER_GROQ, keys::PROVIDER_GEMINI, keys::PROVIDER_DEEPSEEK],
            keys::provider_order((int) $user->id)
        );
    }

    /**
     * Every provider can be moved, not just the first one: a provider put last stays last.
     *
     * @return void
     */
    public function test_provider_order_can_put_any_provider_anywhere(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $order = [keys::PROVIDER_DEEPSEEK, keys::PROVIDER_OPENAI, keys::PROVIDER_GROQ, keys::PROVIDER_GEMINI];

        keys::save_site_provider_order($order);

        $this->assertSame($order, keys::provider_order((int) $user->id));
    }

    /**
     * The user's own order goes ahead of the site's, and the site's fills in what the user left out.
     * Someone who chose nothing follows the site.
     *
     * @return void
     */
    public function test_provider_order_puts_the_users_order_ahead_of_the_sites(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        keys::save_site_provider_order([keys::PROVIDER_GROQ, keys::PROVIDER_GEMINI, keys::PROVIDER_DEEPSEEK]);
        keys::save_user_provider_order([keys::PROVIDER_DEEPSEEK], (int) $user->id);

        $this->assertSame(
            [keys::PROVIDER_DEEPSEEK, keys::PROVIDER_GROQ, keys::PROVIDER_GEMINI, keys::PROVIDER_OPENAI],
            keys::provider_order((int) $user->id)
        );
        $this->assertSame(
            [keys::PROVIDER_GROQ, keys::PROVIDER_GEMINI, keys::PROVIDER_DEEPSEEK, keys::PROVIDER_OPENAI],
            keys::provider_order((int) $other->id)
        );
    }

    /**
     * A provider listed twice, or in both the user's order and the site's, appears once, in its
     * first place.
     *
     * @return void
     */
    public function test_provider_order_has_no_repeated_provider(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        keys::save_site_provider_order([keys::PROVIDER_GROQ, keys::PROVIDER_OPENAI]);
        keys::save_user_provider_order([keys::PROVIDER_GROQ, keys::PROVIDER_GROQ, keys::PROVIDER_GEMINI], (int) $user->id);

        $order = keys::provider_order((int) $user->id);

        $this->assertSame($order, array_values(array_unique($order)));
        $this->assertSame(
            [keys::PROVIDER_GROQ, keys::PROVIDER_GEMINI, keys::PROVIDER_OPENAI, keys::PROVIDER_DEEPSEEK],
            $order
        );
    }

    /**
     * A value that is not a provider is ignored, so a stale setting or preference cannot take a
     * provider out of the list or put a made-up one in it.
     *
     * @return void
     */
    public function test_provider_order_ignores_unknown_values(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        set_config('providerorder', 'not-a-provider,,  ,groq', 'local_aihub');
        set_user_preference('local_aihub_providerorder', 'also-not-one,<script>', (int) $user->id);

        $this->assertSame(
            [keys::PROVIDER_GROQ, keys::PROVIDER_GEMINI, keys::PROVIDER_DEEPSEEK, keys::PROVIDER_OPENAI],
            keys::provider_order((int) $user->id)
        );
    }

    /**
     * The text a form sends, or the list a caller builds, is reduced to the known providers once each.
     *
     * @return void
     */
    public function test_normalize_order(): void {
        $this->assertSame([], keys::normalize_order(''));
        $this->assertSame([], keys::normalize_order('nothing,else'));
        $this->assertSame(['groq', 'gemini'], keys::normalize_order(' groq , gemini ,groq'));
        $this->assertSame(['openai', 'deepseek'], keys::normalize_order(['openai', 'x', 'deepseek', 'openai']));
        $this->assertSame(['gemini'], keys::normalize_order('GEMINI,gemini'), 'the names are case sensitive');
    }

    /**
     * A user's order is stored as the list given, read back the same, and cleared by an empty list
     * or one with nothing valid in it, which is "automatic".
     *
     * @return void
     */
    public function test_a_users_order_is_saved_and_cleared(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $userid = (int) $user->id;

        $this->assertSame([], keys::get_user_provider_order($userid));

        keys::save_user_provider_order('deepseek,groq,openai,gemini', $userid);
        $this->assertSame(['deepseek', 'groq', 'openai', 'gemini'], keys::get_user_provider_order($userid));
        $this->assertSame('deepseek,groq,openai,gemini', get_user_preferences('local_aihub_providerorder', null, $userid));

        keys::save_user_provider_order([], $userid);
        $this->assertSame([], keys::get_user_provider_order($userid));
        $this->assertNull(get_user_preferences('local_aihub_providerorder', null, $userid));

        keys::save_user_provider_order('groq', $userid);
        keys::save_user_provider_order('<script>,nope', $userid);
        $this->assertNull(get_user_preferences('local_aihub_providerorder', null, $userid));
    }

    /**
     * The site's order is saved the same way, and an empty one goes back to automatic.
     *
     * @return void
     */
    public function test_the_sites_order_is_saved_and_cleared(): void {
        $this->resetAfterTest();

        $this->assertSame([], keys::get_site_provider_order());

        keys::save_site_provider_order('openai,gemini');
        $this->assertSame(['openai', 'gemini'], keys::get_site_provider_order());

        keys::save_site_provider_order('');
        $this->assertSame([], keys::get_site_provider_order());
        $this->assertSame(keys::providers(), keys::provider_order(0));
    }

    /**
     * Without a user, as in cron, only the site's order applies.
     *
     * @return void
     */
    public function test_provider_order_without_a_user_uses_the_sites_order(): void {
        $this->resetAfterTest();

        keys::save_site_provider_order([keys::PROVIDER_DEEPSEEK]);

        $this->assertSame(
            [keys::PROVIDER_DEEPSEEK, keys::PROVIDER_GEMINI, keys::PROVIDER_GROQ, keys::PROVIDER_OPENAI],
            keys::provider_order(0)
        );
    }
}
