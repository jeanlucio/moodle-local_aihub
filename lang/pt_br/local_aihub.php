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
 * Brazilian Portuguese language strings for local_aihub.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:disable moodle.Files.LineLength
defined('MOODLE_INTERNAL') || die();

$string['aihub:usepersonalkey'] = 'Usar uma chave de API de IA pessoal';
$string['enablepersonalkeys'] = 'Habilitar chaves de API pessoais';
$string['enablepersonalkeys_desc'] = 'Permite que usuários com a capacidade guardem suas próprias chaves de API de IA.';
$string['mykeys'] = 'Minhas chaves de IA';
$string['mykeys_advanced'] = 'Configurações avançadas compatíveis com OpenAI';
$string['mykeys_heading'] = 'Minhas chaves de IA';
$string['mykeys_intro'] = 'Guarde suas próprias chaves de API para usar recursos de IA com sua cota pessoal. Por segurança, uma chave salva nunca é exibida novamente — deixe um campo em branco para manter a chave atual.';
$string['mykeys_keyssaved'] = 'Suas configurações de chave de IA foram salvas.';
$string['mykeys_log_date'] = 'Data';
$string['mykeys_log_empty'] = 'Nenhuma requisição de IA ainda.';
$string['mykeys_log_heading'] = 'Uso recente de IA';
$string['mykeys_log_model'] = 'Modelo';
$string['mykeys_log_provider'] = 'Provedor';
$string['mykeys_notallowed'] = 'Chaves de IA pessoais não estão habilitadas para a sua conta.';
$string['mykeys_remove'] = 'Remover a chave salva';
$string['mykeys_replacehint'] = 'Deixe em branco para manter a chave atual.';
$string['mykeys_save'] = 'Salvar';
$string['mykeys_showhide'] = 'Mostrar ou ocultar a chave enquanto digita';
$string['mykeys_status_set'] = 'Configurada';
$string['mykeys_status_unset'] = 'Não configurada';
$string['pluginname'] = 'Central de IA';
$string['provider_gemini'] = 'Gemini';
$string['provider_groq'] = 'Groq';
$string['provider_openai'] = 'Compatível com OpenAI';
$string['settings_gemini_key'] = 'Chave de API Gemini';
$string['settings_gemini_key_desc'] = 'Chave de API de todo o site para o Google Gemini. Usada quando o usuário não tem chave pessoal.';
$string['settings_groq_key'] = 'Chave de API Groq';
$string['settings_groq_key_desc'] = 'Chave de API de todo o site para o Groq.';
$string['settings_openai_baseurl'] = 'URL base compatível com OpenAI';
$string['settings_openai_baseurl_desc'] = 'URL base de uma API compatível com OpenAI. Padrão: https://api.openai.com/v1.';
$string['settings_openai_key'] = 'Chave de API compatível com OpenAI';
$string['settings_openai_key_desc'] = 'Chave de API de todo o site para o endpoint compatível com OpenAI.';
$string['settings_openai_model'] = 'Modelo compatível com OpenAI';
$string['settings_openai_model_desc'] = 'Identificador do modelo enviado à API compatível com OpenAI. Padrão: gpt-4o-mini.';
$string['settings_sitekeys_desc'] = 'Estas chaves são usadas para qualquer usuário que não tenha definido uma chave pessoal. Deixe em branco para depender de chaves pessoais ou do core_ai.';
$string['settings_sitekeys_heading'] = 'Chaves de API do site';
