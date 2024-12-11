<?php
// This file is part of CodeRunnerEx.
//
// CodeRunnerEx is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// CodeRunnerEx is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with CodeRunnerEx. If not, see <http://www.gnu.org/licenses/>.

/**
 * Configuration settings declaration information for the CodeRunnerEx question type.
 *
 * @package   qtype_coderunnerex
 * @copyright Ginger Jiang, China Pharmerutical University.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/coderunnerex/lib/utils.php');

use qtype_coderunner\constants;

$settings->add(new admin_setting_heading(
    'coderunner_inherited_settings',
    get_string('head_title_inheritedfromcoderunner', 'qtype_coderunnerex'),
    ''
));

require($CFG->dirroot . '/question/type/coderunner/settings.php');

$settings->add(new admin_setting_heading(
    'coderunnerex_custom_settings',
    get_string('head_title_coderunnerexcustom', 'qtype_coderunnerex'),
    ''
));

$settings->add(new admin_setting_configcheckbox(('qtype_coderunnerex/copy_button_for_example_table'),
    get_string('copy_button_for_example_table', 'qtype_coderunnerex'),
    get_string('copy_button_for_example_table_desc', 'qtype_coderunnerex'),
    true));

$settings->add(new admin_setting_configcheckbox(('qtype_coderunnerex/copy_button_for_test_code'),
    get_string('copy_button_for_test_code', 'qtype_coderunnerex'),
    get_string('copy_button_for_test_code_desc', 'qtype_coderunnerex'),
    true));

$settings->add(new admin_setting_configcheckbox(('qtype_coderunnerex/copy_button_for_test_input'),
    get_string('copy_button_for_test_input', 'qtype_coderunnerex'),
    get_string('copy_button_for_test_input_desc', 'qtype_coderunnerex'),
    true));

$settings->add(new admin_setting_configcheckbox(('qtype_coderunnerex/copy_button_for_test_output'),
    get_string('copy_button_for_test_output', 'qtype_coderunnerex'),
    get_string('copy_button_for_test_output_desc', 'qtype_coderunnerex'),
    true));

$settings->add(new admin_setting_configcheckbox(('qtype_coderunnerex/copy_button_for_test_expected'),
    get_string('copy_button_for_test_expected', 'qtype_coderunnerex'),
    get_string('copy_button_for_test_expected_desc', 'qtype_coderunnerex'),
    true));


$settings->add(new admin_setting_configtext(
    "qtype_coderunnerex/default_coderunner_type",
    // get_string('default_setting_prefix', 'qtype_coderunnerex') . get_string('coderunnertype', 'qtype_coderunner'),
    get_string('default_coderunner_type', 'qtype_coderunnerex'),
    get_string('default_coderunner_type_desc', 'qtype_coderunnerex'),
    ''
));

$settings->add(new admin_setting_configtext(
    "qtype_coderunnerex/default_code_editor_row_count",
    // get_string('default_setting_prefix', 'qtype_coderunnerex') . get_string('answerboxlines', 'qtype_coderunner'),
    get_string('default_code_editor_row_count', 'qtype_coderunnerex'),
    get_string('default_code_editor_row_count_desc', 'qtype_coderunnerex'),
    18,
    PARAM_INT
));

//

$feedbackvalues = [
    constants::FEEDBACK_USE_QUIZ => get_string('feedback_quiz', 'qtype_coderunner'),
    constants::FEEDBACK_SHOW    => get_string('feedback_show', 'qtype_coderunner'),
    constants::FEEDBACK_HIDE => get_string('feedback_hide', 'qtype_coderunner'),
];

$settings->add(new admin_setting_configselect(
    "qtype_coderunnerex/default_feedback_mode",
    get_string('default_feedback_mode', 'qtype_coderunnerex'),
    get_string('default_feedback_mode_desc', 'qtype_coderunnerex'),
    constants::FEEDBACK_USE_QUIZ,
    $feedbackvalues
));

//

/*
$allornothing_values = [
    constants::FEEDBACK_USE_QUIZ => get_string('feedback_quiz', 'qtype_coderunner'),
    constants::FEEDBACK_SHOW    => get_string('feedback_show', 'qtype_coderunner'),
    constants::FEEDBACK_HIDE => get_string('feedback_hide', 'qtype_coderunner'),
];
*/

$settings->add(new admin_setting_configcheckbox(
    "qtype_coderunnerex/default_allornothing",
    get_string('default_allornothing', 'qtype_coderunnerex'),
    get_string('default_allornothing_desc', 'qtype_coderunnerex'),
    true
));

/*
$settings->add(new admin_setting_configcheckbox(
    "qtype_coderunnerex/encrypt_code_transfer",
    get_string('encrypt_code_transfer', 'qtype_coderunnerex'),
    get_string('encrypt_code_transfer_desc', 'qtype_coderunnerex'),
    false
));
*/

$settings->add(new admin_setting_configcheckbox(
    "qtype_coderunnerex/encrypt_code_and_output_transfer_from_server",
    get_string('encrypt_code_and_output_transfer_from_server', 'qtype_coderunnerex'),
    get_string('encrypt_code_and_output_transfer_from_server_desc', 'qtype_coderunnerex'),
    false
));

$settings->add(new admin_setting_configcheckbox(
    "qtype_coderunnerex/encrypt_answer_transfer_from_client",
    get_string('encrypt_answer_transfer_from_client', 'qtype_coderunnerex'),
    get_string('encrypt_answer_transfer_from_client_desc', 'qtype_coderunnerex'),
    false
));

//


$settings->add(new admin_setting_heading(
    'coderunnerex_code_helper',
    get_string('head_title_coderunnerex_codehelper', 'qtype_coderunnerex'),
    ''
));

$settings->add(new admin_setting_configcheckbox(
    "qtype_coderunnerex/default_enable_code_helper",
    get_string('default_enable_code_helper', 'qtype_coderunnerex'),
    get_string('default_enable_code_helper_desc', 'qtype_coderunnerex'),
    true
));

$settings->add(new admin_setting_configtext(
    "qtype_coderunnerex/code_helper_server_url",
    get_string('code_helper_server_url', 'qtype_coderunnerex'),
    get_string('code_helper_server_url_desc', 'qtype_coderunnerex'),
    ''
));
$settings->add(new admin_setting_configtext(
    "qtype_coderunnerex/code_helper_server_token",
    get_string('code_helper_server_token', 'qtype_coderunnerex'),
    get_string('code_helper_server_token_desc', 'qtype_coderunnerex'),
    ''
));

$settings->add(new admin_setting_configtext(
    "qtype_coderunnerex/default_code_helper_max_usage_count_per_question_attempt",
    get_string('default_code_helper_max_usage_count_per_question_attempt', 'qtype_coderunnerex'),
    get_string('default_code_helper_max_usage_count_per_question_attempt_desc', 'qtype_coderunnerex'),
    0
));

$settings->add(new admin_setting_configtextarea(
    "qtype_coderunnerex/code_helper_predefined_asks",
    get_string('code_helper_predefined_asks', 'qtype_coderunnerex'),
    get_string('code_helper_predefined_asks_desc', 'qtype_coderunnerex'),
    implode('\n', qtype_coderunnerex_util::get_default_codehelper_asks())
));

$settings->add(new admin_setting_configcheckbox(
    "qtype_coderunnerex/code_helper_simple_assistant_mode",
    get_string('code_helper_simple_assistant_mode', 'qtype_coderunnerex'),
    get_string('code_helper_simple_assistant_mode_desc', 'qtype_coderunnerex'),
    false
));

$settings->add(new admin_setting_configcheckbox(
    "qtype_coderunnerex/code_helper_disable_custom_asks",
    get_string('code_helper_disable_custom_asks', 'qtype_coderunnerex'),
    get_string('code_helper_disable_custom_asks_desc', 'qtype_coderunnerex'),
    false
));

$settings->add(new admin_setting_configcheckbox(
    "qtype_coderunnerex/code_helper_enable_user_rating",
    get_string('code_helper_enable_user_rating', 'qtype_coderunnerex'),
    get_string('code_helper_enable_user_rating_desc', 'qtype_coderunnerex'),
    false
));

$settings->add(new admin_setting_configcheckbox(
    "qtype_coderunnerex/default_code_helper_omit_code_snippet",
    get_string('default_code_helper_omit_code_snippet', 'qtype_coderunnerex'),
    get_string('default_code_helper_omit_code_snippet_desc', 'qtype_coderunnerex'),
    false
));


$history_display_modes = [
    qtype_coderunnerex_code_helper_history_display_mode::SHOWN_ALL => get_string('code_helper_history_display_mode_all', 'qtype_coderunnerex'),
    qtype_coderunnerex_code_helper_history_display_mode::SHOWN_SESSION => get_string('code_helper_history_display_mode_session', 'qtype_coderunnerex'),
    qtype_coderunnerex_code_helper_history_display_mode::SHOWN_ACTIVE    => get_string('code_helper_history_display_mode_active', 'qtype_coderunnerex'),
];

$settings->add(new admin_setting_configselect(
    "qtype_coderunnerex/code_helper_history_display_mode",
    get_string('code_helper_history_display_mode', 'qtype_coderunnerex'),
    get_string('code_helper_history_display_mode_desc', 'qtype_coderunnerex'),
    qtype_coderunnerex_code_helper_history_display_mode::SHOWN_SESSION,
    $history_display_modes
));


$settings->add(new admin_setting_configtextarea(
    "qtype_coderunnerex/code_helper_question_body_clean_patterns",
    get_string('code_helper_question_body_clean_patterns', 'qtype_coderunnerex'),
    get_string('code_helper_question_body_clean_patterns_desc', 'qtype_coderunnerex'),
    implode('\n', qtype_coderunnerex_util::get_default_codehelper_question_body_clean_patterns())
));

//

/*
if ($ADMIN->fulltree) {
    $settings = new admin_settingpage('coderunner_inherited_settings', get_string('head_title_inheritedfromcoderunner', 'qtype_coderunnerex'));
    $ADMIN->add('coderunner_inherited_settings', $settings);
    $ex_settings = new admin_settingpage('coderunnerex_custom_settings', get_string('head_title_coderunnerexcustom', 'qtype_coderunnerex'));
    $ADMIN->add('coderunnerex_custom_settings', $ex_settings);
}
*/

