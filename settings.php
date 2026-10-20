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
 * Admin settings for local_aihub.
 *
 * @package    local_aihub
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Moodle 4.5 only creates the localplugins category for site admins (newer branches always create it). A
// user who holds local/aihub:viewusage without moodle/site:config still needs the usage report, so there it
// goes under Reports instead of a category whose parent does not exist for them.
$reportparent = 'reports';
if ($ADMIN->locate('localplugins')) {
    $ADMIN->add('localplugins', new admin_category(
        'local_aihub_category',
        get_string('pluginname', 'local_aihub')
    ));
    $reportparent = 'local_aihub_category';
}

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aihub', get_string('settings', 'moodle'));
    $ADMIN->add('local_aihub_category', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_aihub/enablepersonalkeys',
        get_string('enablepersonalkeys', 'local_aihub'),
        get_string('enablepersonalkeys_desc', 'local_aihub'),
        1
    ));

    // A change to the site's keys, endpoint or model may be what a failing provider needed, so the
    // memory of the site's failures starts over.
    $clearhealth = 'local_aihub\local\health::clear_site';

    $settings->add(new admin_setting_heading(
        'local_aihub/sitekeys',
        get_string('settings_sitekeys_heading', 'local_aihub'),
        get_string('settings_sitekeys_desc', 'local_aihub')
    ));

    $setting = new admin_setting_configpasswordunmask(
        'local_aihub/gemini_key',
        get_string('settings_gemini_key', 'local_aihub'),
        get_string('settings_gemini_key_desc', 'local_aihub'),
        ''
    );
    $setting->set_updatedcallback($clearhealth);
    $settings->add($setting);

    $setting = new admin_setting_configpasswordunmask(
        'local_aihub/groq_key',
        get_string('settings_groq_key', 'local_aihub'),
        get_string('settings_groq_key_desc', 'local_aihub'),
        ''
    );
    $setting->set_updatedcallback($clearhealth);
    $settings->add($setting);

    $setting = new admin_setting_configpasswordunmask(
        'local_aihub/deepseek_key',
        get_string('settings_deepseek_key', 'local_aihub'),
        get_string('settings_deepseek_key_desc', 'local_aihub'),
        ''
    );
    $setting->set_updatedcallback($clearhealth);
    $settings->add($setting);

    $setting = new admin_setting_configpasswordunmask(
        'local_aihub/openai_key',
        get_string('settings_openai_key', 'local_aihub'),
        get_string('settings_openai_key_desc', 'local_aihub'),
        ''
    );
    $setting->set_updatedcallback($clearhealth);
    $settings->add($setting);

    $setting = new \local_aihub\admin\setting_endpoint(
        'local_aihub/openai_baseurl',
        get_string('settings_openai_baseurl', 'local_aihub'),
        get_string('settings_openai_baseurl_desc', 'local_aihub'),
        'https://api.openai.com/v1'
    );
    $setting->set_updatedcallback($clearhealth);
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_aihub/openai_model',
        get_string('settings_openai_model', 'local_aihub'),
        get_string('settings_openai_model_desc', 'local_aihub'),
        'gpt-4o-mini',
        PARAM_TEXT
    );
    $setting->set_updatedcallback($clearhealth);
    $settings->add($setting);

    // The order has a page of its own, like the question bank's column order: a list that is dragged does not
    // belong in a form of settings, and the settings tree is also built on install and upgrade, where nothing
    // is rendered.
    $providerorderurl = new moodle_url('/local/aihub/providerorder.php');
    $ADMIN->add('local_aihub_category', new admin_externalpage(
        'local_aihub_providerorder',
        get_string('providerorder_title', 'local_aihub'),
        $providerorderurl,
        'moodle/site:config'
    ));
    $settings->add(new admin_setting_description(
        'local_aihub/providerorderlink',
        '',
        new lang_string(
            'settings_providerorder_link',
            'local_aihub',
            html_writer::link($providerorderurl, get_string('providerorder_title', 'local_aihub'))
        )
    ));

    $settings->add(new admin_setting_configtext(
        'local_aihub/failurememory',
        get_string('settings_failurememory', 'local_aihub'),
        get_string('settings_failurememory_desc', 'local_aihub'),
        5,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_aihub/logretentiondays',
        get_string('settings_logretentiondays', 'local_aihub'),
        get_string('settings_logretentiondays_desc', 'local_aihub'),
        365,
        PARAM_INT
    ));
}

$ADMIN->add($reportparent, new admin_externalpage(
    'local_aihub_report',
    get_string('report_title', 'local_aihub'),
    new moodle_url('/local/aihub/report.php'),
    'local/aihub:viewusage'
));
