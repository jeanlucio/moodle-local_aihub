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
$string['aihub:viewusage'] = 'Ver o uso de IA do site';
$string['download_csv'] = 'Baixar CSV';
$string['download_excel'] = 'Baixar Excel';
$string['enablepersonalkeys'] = 'Habilitar chaves de API pessoais';
$string['enablepersonalkeys_desc'] = 'Permite que usuários com a capacidade guardem suas próprias chaves de API de IA.';
$string['mykeys'] = 'Minhas chaves de IA';
$string['mykeys_advanced'] = 'Configurações avançadas compatíveis com OpenAI';
$string['mykeys_intro'] = 'Guarde suas próprias chaves de API para usar recursos de IA com sua cota pessoal. Por segurança, uma chave salva nunca é exibida novamente — deixe um campo em branco para manter a chave atual.';
$string['mykeys_keyssaved'] = 'Suas configurações de chave de IA foram salvas.';
$string['mykeys_log_action'] = 'O que foi feito';
$string['mykeys_log_component'] = 'Solicitado por';
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
$string['privacy:metadata:external:gemini'] = 'Quando uma chave Gemini é resolvida, o prompt é enviado à API Google Gemini para gerar texto.';
$string['privacy:metadata:external:groq'] = 'Quando uma chave Groq é resolvida, o prompt é enviado à API Groq para gerar texto.';
$string['privacy:metadata:external:openai'] = 'Quando uma chave compatível com OpenAI é resolvida, o prompt é enviado à API compatível com OpenAI configurada para gerar texto.';
$string['privacy:metadata:external:prompt'] = 'O texto do prompt enviado para geração.';
$string['privacy:metadata:logtable'] = 'Um registro das requisições de geração de IA feitas pelo usuário.';
$string['privacy:metadata:logtable:component'] = 'O plugin que solicitou a geração.';
$string['privacy:metadata:logtable:description'] = 'Um rótulo curto do que foi gerado.';
$string['privacy:metadata:logtable:keysource'] = 'Se a requisição usou uma chave de API pessoal ou do site.';
$string['privacy:metadata:logtable:model'] = 'O modelo usado na geração.';
$string['privacy:metadata:logtable:provider'] = 'O provedor que atendeu a geração.';
$string['privacy:metadata:logtable:timecreated'] = 'O momento em que a requisição foi feita.';
$string['privacy:metadata:logtable:userid'] = 'O usuário que fez a requisição.';
$string['privacy:metadata:preference:gemini_key'] = 'A chave de API Gemini pessoal.';
$string['privacy:metadata:preference:groq_key'] = 'A chave de API Groq pessoal.';
$string['privacy:metadata:preference:openai_key'] = 'A chave de API compatível com OpenAI pessoal.';
$string['privacy:metadata:preference:openai_model'] = 'O nome do modelo compatível com OpenAI pessoal.';
$string['privacy:metadata:preference:openai_url'] = 'A URL base compatível com OpenAI pessoal.';
$string['privacy:redacted'] = 'Uma chave de API pessoal está armazenada (o valor é ocultado por segurança).';
$string['provider_gemini'] = 'Gemini';
$string['provider_groq'] = 'Groq';
$string['provider_openai'] = 'Compatível com OpenAI';
$string['report_empty'] = 'Nenhuma requisição de IA usou as chaves do site ainda.';
$string['report_intro'] = 'Requisições de geração de IA atendidas pelas chaves de API do site, de todos os usuários.';
$string['report_title'] = 'Relatório de uso de IA do site';
$string['report_user'] = 'Usuário';
$string['settings_gemini_key'] = 'Chave de API Gemini';
$string['settings_gemini_key_desc'] = 'Chave de API de todo o site para o Google Gemini. Usada quando o usuário não tem chave pessoal.';
$string['settings_groq_key'] = 'Chave de API Groq';
$string['settings_groq_key_desc'] = 'Chave de API de todo o site para o Groq.';
$string['settings_logretentiondays'] = 'Retenção do log de uso (dias)';
$string['settings_logretentiondays_desc'] = 'Apaga entradas do log de uso de IA mais antigas que esta quantidade de dias. Use 0 para mantê-las indefinidamente.';
$string['settings_openai_baseurl'] = 'URL base compatível com OpenAI';
$string['settings_openai_baseurl_desc'] = 'URL base de uma API compatível com OpenAI. Padrão: https://api.openai.com/v1.';
$string['settings_openai_key'] = 'Chave de API compatível com OpenAI';
$string['settings_openai_key_desc'] = 'Chave de API de todo o site para o endpoint compatível com OpenAI.';
$string['settings_openai_model'] = 'Modelo compatível com OpenAI';
$string['settings_openai_model_desc'] = 'Identificador do modelo enviado à API compatível com OpenAI. Padrão: gpt-4o-mini.';
$string['settings_sitekeys_desc'] = 'Estas chaves são usadas para qualquer usuário que não tenha definido uma chave pessoal. Deixe em branco para depender de chaves pessoais ou do core_ai.';
$string['settings_sitekeys_heading'] = 'Chaves de API do site';
$string['task_purge_old_logs'] = 'Limpar registros antigos de uso de IA';
