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
$string['privacy:metadata:external:gemini'] = 'When a Gemini key resolves, the prompt is sent to the Google Gemini API to generate text.';
$string['privacy:metadata:external:groq'] = 'When a Groq key resolves, the prompt is sent to the Groq API to generate text.';
$string['privacy:metadata:external:openai'] = 'When an OpenAI-compatible key resolves, the prompt is sent to the configured OpenAI-compatible API to generate text.';
$string['privacy:metadata:external:prompt'] = 'The prompt text submitted for generation.';
$string['privacy:metadata:logtable'] = 'A log of AI generation requests made by the user.';
$string['privacy:metadata:logtable:component'] = 'The plugin that requested the generation.';
$string['privacy:metadata:logtable:model'] = 'The model used for the generation.';
$string['privacy:metadata:logtable:provider'] = 'The provider that served the generation.';
$string['privacy:metadata:logtable:timecreated'] = 'The time the request was made.';
$string['privacy:metadata:logtable:userid'] = 'The user who made the request.';
$string['privacy:metadata:preference:gemini_key'] = 'The personal Gemini API key.';
$string['privacy:metadata:preference:groq_key'] = 'The personal Groq API key.';
$string['privacy:metadata:preference:openai_key'] = 'The personal OpenAI-compatible API key.';
$string['privacy:metadata:preference:openai_model'] = 'The personal OpenAI-compatible model name.';
$string['privacy:metadata:preference:openai_url'] = 'The personal OpenAI-compatible base URL.';
$string['privacy:redacted'] = 'A personal API key is stored (its value is hidden for security).';
$string['provider_gemini'] = 'Gemini';
$string['provider_groq'] = 'Groq';
$string['provider_openai'] = 'OpenAI-compatible';
$string['settings_gemini_key'] = 'Gemini API key';
$string['settings_gemini_key_desc'] = 'Site-wide API key for Google Gemini. Used when a user has no personal key.';
$string['settings_groq_key'] = 'Groq API key';
$string['settings_groq_key_desc'] = 'Site-wide API key for Groq.';
$string['settings_logretentiondays'] = 'Usage log retention (days)';
$string['settings_logretentiondays_desc'] = 'Delete AI usage log entries older than this many days. Set to 0 to keep them indefinitely.';
$string['settings_openai_baseurl'] = 'OpenAI-compatible base URL';
$string['settings_openai_baseurl_desc'] = 'Base URL of an OpenAI-compatible API. Defaults to https://api.openai.com/v1.';
$string['settings_openai_key'] = 'OpenAI-compatible API key';
$string['settings_openai_key_desc'] = 'Site-wide API key for the OpenAI-compatible endpoint.';
$string['settings_openai_model'] = 'OpenAI-compatible model';
$string['settings_openai_model_desc'] = 'Model identifier sent to the OpenAI-compatible API. Defaults to gpt-4o-mini.';
$string['settings_sitekeys_desc'] = 'These keys are used for any user who has not set a personal key. Leave blank to rely on personal keys or core_ai.';
$string['settings_sitekeys_heading'] = 'Site API keys';
$string['task_purge_old_logs'] = 'Purge old AI usage logs';
