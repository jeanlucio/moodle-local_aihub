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
 * BYOK key store and resolution for local_aihub.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aihub\local;

/**
 * Resolves AI API keys for the hub, personal-first then site.
 *
 * This is a pure BYOK store: it knows about personal (per-user, opt-in) keys and
 * site (admin-wide) keys only. It never consults core_ai — that fallback is the
 * responsibility of each consumer plugin.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class keys {
    /** @var string Gemini provider identifier. */
    const PROVIDER_GEMINI = 'gemini';

    /** @var string Groq provider identifier. */
    const PROVIDER_GROQ = 'groq';

    /** @var string DeepSeek provider identifier. */
    const PROVIDER_DEEPSEEK = 'deepseek';

    /** @var string OpenAI-compatible provider identifier. */
    const PROVIDER_OPENAI = 'openai';

    /**
     * Returns the hardcoded provider order used during resolution.
     *
     * Free/fast tiers first (Gemini, Groq), paid last (OpenAI). The order only
     * matters when more than one key is set within the same tier.
     *
     * @return string[]
     */
    public static function providers(): array {
        return [self::PROVIDER_GEMINI, self::PROVIDER_GROQ, self::PROVIDER_DEEPSEEK, self::PROVIDER_OPENAI];
    }

    /**
     * Returns true when personal keys may be used for the given user.
     *
     * Requires both the site toggle (enablepersonalkeys) and the per-user
     * capability local/aihub:usepersonalkey at system context.
     *
     * @param int|null $userid Defaults to $USER->id.
     * @return bool
     */
    public static function personal_keys_allowed(?int $userid = null): bool {
        global $USER;

        if (!get_config('local_aihub', 'enablepersonalkeys')) {
            return false;
        }
        $userid = $userid ?? (int) $USER->id;
        return has_capability(
            'local/aihub:usepersonalkey',
            \context_system::instance(),
            $userid
        );
    }

    /**
     * Returns the personal (per-user, opt-in) API key for a provider, or '' if unset.
     *
     * @param string $provider One of the PROVIDER_* constants.
     * @param int|null $userid Defaults to $USER->id.
     * @return string
     */
    public static function get_personal_key(string $provider, ?int $userid = null): string {
        global $USER;

        $userid = $userid ?? (int) $USER->id;
        return (string) get_user_preferences('local_aihub_' . $provider . '_key', '', $userid);
    }

    /**
     * Returns the site-wide (admin-configured) API key for a provider, or '' if unset.
     *
     * @param string $provider One of the PROVIDER_* constants.
     * @return string
     */
    public static function get_site_key(string $provider): string {
        return (string) get_config('local_aihub', $provider . '_key');
    }

    /**
     * Returns the resolved key for a provider: personal first (when allowed), then site.
     *
     * @param string $provider One of the PROVIDER_* constants.
     * @param int|null $userid User whose personal key is checked first. Defaults to $USER->id.
     * @return string
     */
    public static function get_key(string $provider, ?int $userid = null): string {
        if (self::personal_keys_allowed($userid)) {
            $personal = self::get_personal_key($provider, $userid);
            if ($personal !== '') {
                return $personal;
            }
        }
        return self::get_site_key($provider);
    }

    /**
     * Returns true when the user has at least one personal key set (any provider).
     *
     * @param int|null $userid Defaults to $USER->id.
     * @return bool
     */
    public static function has_personal_key(?int $userid = null): bool {
        foreach (self::providers() as $provider) {
            if (self::get_personal_key($provider, $userid) !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true when at least one BYOK source is available for the user.
     *
     * Considers personal keys only when personal keys are allowed; otherwise just
     * site keys. core_ai is intentionally not considered here.
     *
     * @param int|null $userid Defaults to $USER->id.
     * @return bool
     */
    public static function has_any_key(?int $userid = null): bool {
        if (self::personal_keys_allowed($userid) && self::has_personal_key($userid)) {
            return true;
        }
        foreach (self::providers() as $provider) {
            if (self::get_site_key($provider) !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the configured OpenAI-compatible base URL (site value).
     *
     * @return string
     */
    public static function get_openai_baseurl(): string {
        $url = (string) get_config('local_aihub', 'openai_baseurl');
        if ($url === '') {
            return 'https://api.openai.com/v1';
        }
        return $url;
    }

    /**
     * Returns the configured OpenAI-compatible model name (site value).
     *
     * @return string
     */
    public static function get_openai_model(): string {
        $model = (string) get_config('local_aihub', 'openai_model');
        if ($model === '') {
            return 'gpt-4o-mini';
        }
        return $model;
    }

    /**
     * Returns the personal OpenAI-compatible base URL, or '' if unset.
     *
     * @param int|null $userid Defaults to $USER->id.
     * @return string
     */
    public static function get_personal_openai_url(?int $userid = null): string {
        global $USER;

        $userid = $userid ?? (int) $USER->id;
        return (string) get_user_preferences('local_aihub_openai_url', '', $userid);
    }

    /**
     * Returns the personal OpenAI-compatible model name, or '' if unset.
     *
     * @param int|null $userid Defaults to $USER->id.
     * @return string
     */
    public static function get_personal_openai_model(?int $userid = null): string {
        global $USER;

        $userid = $userid ?? (int) $USER->id;
        return (string) get_user_preferences('local_aihub_openai_model', '', $userid);
    }

    /**
     * Saves a personal API key as a user preference. Empty value clears it.
     *
     * @param string $provider One of the PROVIDER_* constants.
     * @param string $key The API key value (empty to clear).
     * @param int|null $userid Defaults to $USER->id.
     * @return void
     */
    public static function save_user_key(string $provider, string $key, ?int $userid = null): void {
        global $USER;

        $userid = $userid ?? (int) $USER->id;
        $prefname = 'local_aihub_' . $provider . '_key';

        if ($key === '') {
            unset_user_preference($prefname, $userid);
        } else {
            set_user_preference($prefname, $key, $userid);
        }
    }

    /**
     * Saves the personal OpenAI-compatible base URL. Empty value clears it.
     *
     * @param string $url The base URL (empty to clear).
     * @param int|null $userid Defaults to $USER->id.
     * @return void
     */
    public static function save_user_openai_url(string $url, ?int $userid = null): void {
        global $USER;

        $userid = $userid ?? (int) $USER->id;
        if ($url === '') {
            unset_user_preference('local_aihub_openai_url', $userid);
        } else {
            set_user_preference('local_aihub_openai_url', $url, $userid);
        }
    }

    /**
     * Saves the personal OpenAI-compatible model name. Empty value clears it.
     *
     * @param string $model The model identifier (empty to clear).
     * @param int|null $userid Defaults to $USER->id.
     * @return void
     */
    public static function save_user_openai_model(string $model, ?int $userid = null): void {
        global $USER;

        $userid = $userid ?? (int) $USER->id;
        if ($model === '') {
            unset_user_preference('local_aihub_openai_model', $userid);
        } else {
            set_user_preference('local_aihub_openai_model', $model, $userid);
        }
    }
}
