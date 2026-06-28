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
 * English language strings for local_aihub.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:disable moodle.Files.LineLength
defined('MOODLE_INTERNAL') || die();

$string['aihub:usepersonalkey'] = 'Use a personal AI API key';
$string['enablepersonalkeys'] = 'Enable personal API keys';
$string['enablepersonalkeys_desc'] = 'Allow users with the capability to store their own AI API keys.';
$string['mykeys'] = 'My AI keys';
$string['mykeys_advanced'] = 'Advanced OpenAI-compatible settings';
$string['mykeys_heading'] = 'My AI keys';
$string['mykeys_intro'] = 'Store your own API keys to use AI features with your personal quota. For security, a saved key is never shown again — leave a field blank to keep the current key.';
$string['mykeys_keyssaved'] = 'Your AI key settings were saved.';
$string['mykeys_log_date'] = 'Date';
$string['mykeys_log_empty'] = 'No AI requests yet.';
$string['mykeys_log_heading'] = 'Recent AI usage';
$string['mykeys_log_model'] = 'Model';
$string['mykeys_log_provider'] = 'Provider';
$string['mykeys_notallowed'] = 'Personal AI keys are not enabled for your account.';
$string['mykeys_remove'] = 'Remove the saved key';
$string['mykeys_replacehint'] = 'Leave blank to keep the current key.';
$string['mykeys_save'] = 'Save';
$string['mykeys_showhide'] = 'Show or hide the key while typing';
$string['mykeys_status_set'] = 'Configured';
$string['mykeys_status_unset'] = 'Not configured';
$string['pluginname'] = 'AI Hub';
$string['provider_gemini'] = 'Gemini';
$string['provider_groq'] = 'Groq';
$string['provider_openai'] = 'OpenAI-compatible';
$string['settings_gemini_key'] = 'Gemini API key';
$string['settings_gemini_key_desc'] = 'Site-wide API key for Google Gemini. Used when a user has no personal key.';
$string['settings_groq_key'] = 'Groq API key';
$string['settings_groq_key_desc'] = 'Site-wide API key for Groq.';
$string['settings_openai_baseurl'] = 'OpenAI-compatible base URL';
$string['settings_openai_baseurl_desc'] = 'Base URL of an OpenAI-compatible API. Defaults to https://api.openai.com/v1.';
$string['settings_openai_key'] = 'OpenAI-compatible API key';
$string['settings_openai_key_desc'] = 'Site-wide API key for the OpenAI-compatible endpoint.';
$string['settings_openai_model'] = 'OpenAI-compatible model';
$string['settings_openai_model_desc'] = 'Model identifier sent to the OpenAI-compatible API. Defaults to gpt-4o-mini.';
$string['settings_sitekeys_desc'] = 'These keys are used for any user who has not set a personal key. Leave blank to rely on personal keys or core_ai.';
$string['settings_sitekeys_heading'] = 'Site API keys';
