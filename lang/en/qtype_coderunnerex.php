<?php

// This file is part of CodeRunnerEx
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

$string['pluginname'] = 'CodeRunner-ex';
$string['pluginname_help'] = 'Customized question type inherited from CodeRunner';
$string['pluginname_link'] = 'question/type/coderunnerex';

$string['pluginnameadding'] = 'Adding a CodeRunnerEx question';
$string['pluginnameediting'] = 'Editing a CodeRunnerEx question';
$string['pluginnamesummary'] = 'CodeRunnerEx: runs student-submitted code in a sandbox';
$string['pluginname_help'] = 'Use the \'Question type\' combo box to select the
computer language and question type that will be used to run the student\'s submission.
Specify the problem that the student must write code for, then define
a set of tests to be run on the student\'s submission';

// Strings for testcase table tools
$string['copy_to_clipboard_caption'] = 'Copy';
$string['copy_to_clipboard_hint'] = 'Copy to clipboard';

// Strings for Code helper
$string['ask_ai_helper'] = 'Ask to AI helper';
$string['ask_ai_helper_submit'] = 'Submit';

$string['codehelper_thumbnail_caption'] = 'Code Helper';
$string['codehelper_thumbnail_hint'] = 'Open or close Code Helper panel';
$string['codehelper_thumbnail_caption_simple_mode'] = 'Use Code Helper to check your code';
$string['codehelper_thumbnail_caption_simple_mode_hint'] = 'Use AI to check the errors or suggest improvements with your code';

$string['codehelper_collapsor_hint'] = 'Close Code Helper Panel';

$string['codehelper_ask_submit_caption'] = 'Submit';
$string['codehelper_ask_submit_hint'] = 'Submit question to AI helper';
$string['codehelper_ai_usage_count_reminder_hint'] = 'Remaining usage count: {}';
$string['codehelper_ask_input_placeholder'] = 'Please input or select your question';
$string['codehelper_ask_select_placeholder'] = 'Please select your question';
$string['codehelper_no_history_item'] = '[ History is empty ]';

$string['codehelper_user_rate_positive'] = 'Useful';
$string['codehelper_user_rate_negative'] = 'Unuseful';
$string['codehelper_user_rate_label_before_rating'] = 'Please rate this feedback';
$string['codehelper_user_rate_label_after_rating'] = 'Thanks for the rating, you think this feedback is';

// Tool buttons -- unused
$string['caption_ask_ai_question_explain'] = 'Question Explanation';
$string['hint_ask_ai_question_explain'] = 'Ask AI to explain the question requests and give hints';
$string['caption_ask_ai_feedback_explain'] = 'Feedback Explanation';
$string['hint_ask_ai_feedback_explain'] = 'Ask AI to explain the result feedback and give hints';

// Strings for plugin settings page
$string['head_title_inheritedfromcoderunner'] = 'CodeRunner Original Settings';
$string['head_title_coderunnerexcustom'] = 'CodeRunnerEx Customization Settings';
$string['head_title_coderunnerex_codehelper'] = 'Code Helper Settings';

$string['copy_button_for_example_table'] = 'Copy button for example table';
$string['copy_button_for_example_table_desc'] = 'Whether display a copy to clipboard button for example testcases';
$string['copy_button_for_test_code'] = 'Copy button for test result code';
$string['copy_button_for_test_code_desc'] = 'Whether display a copy to clipboard button for test code in testcase result table';
$string['copy_button_for_test_input'] = 'Copy button for test result input';
$string['copy_button_for_test_input_desc'] = 'Whether display a copy to clipboard button for stdin in testcase result table';
$string['copy_button_for_test_output'] = 'Copy button for test result output';
$string['copy_button_for_test_output_desc'] = 'Whether display a copy to clipboard button for output in testcase result table';
$string['copy_button_for_test_expected'] = 'Copy button for test result expected';
$string['copy_button_for_test_expected_desc'] = 'Whether display a copy to clipboard button for expected output in testcase result table';


$string['default_setting_prefix'] = 'Default setting: ';

$string['default_coderunner_type'] = 'Default CodeRunner question type';
$string['default_coderunner_type_desc'] = 'Default CodeRunner question type, e.g. nodejs, python3, ...';
$string['default_code_editor_row_count'] = 'Default editor rows';
$string['default_code_editor_row_count_desc'] = 'Default row count of code editor in question page';
$string['default_feedback_mode'] = 'Default feedback mode';
$string['default_feedback_mode_desc'] = 'Default feedback mode';
$string['default_allornothing'] = 'Default all-or-nothing mode';
$string['default_allornothing_desc'] = 'Default all-or-nothing mode';
/*
$string['encrypt_code_transfer'] = 'Encrypt code transfer';
$string['encrypt_code_transfer_desc'] = 'Whether encrypting user input code before sending to server, avoid some security issues of server protection';
*/
$string['encrypt_code_and_output_transfer_from_server'] = 'Encrypt code and output transfer from server';
$string['encrypt_code_and_output_transfer_from_server_desc'] = 'Whether encrypting code and running output from server to client when transferring, avoid some security issues of server protection';
$string['encrypt_answer_transfer_from_client'] = 'Encrypt answer transfer from client';
$string['encrypt_answer_transfer_from_client_desc'] = 'Whether encrypting user answered code from client to server when transferring, avoid some security issues of server protection';

$string['default_enable_code_helper'] = 'Enable code helper by default';
$string['default_enable_code_helper_desc'] = 'If set to true, the AI code helper will be displayed when students challenging the question by default';

$string['code_helper_server_url'] = 'Code helper server URL';
$string['code_helper_server_url_desc'] = 'The full URL address of code helper server, including http/https protocol';
$string['code_helper_server_token'] = 'Code helper server token';
$string['code_helper_server_token_desc'] = 'Token for the code helper AI server';
$string['code_helper_predefined_asks'] = 'Predefined questions to AI';
$string['code_helper_predefined_asks_desc'] = 'User selectable questions to AI, one question per line';
$string['code_helper_disable_custom_asks'] = 'Disable custom questions to AI';
$string['code_helper_disable_custom_asks_desc'] = 'If set to true, students can only select predefined questions to AI';
$string['code_helper_question_body_clean_patterns'] = 'Moodle question body clean patterns';
$string['code_helper_question_body_clean_patterns_desc'] = 'One regexp pattern per line, content meet these patterns will be cleaned from question body before transferring to the AI server';
$string['default_code_helper_omit_code_snippet'] = 'Omit code snippets in response';
$string['default_code_helper_omit_code_snippet_desc'] = 'Hide code snippets from AI response by default';
$string['default_code_helper_max_usage_count_per_question_attempt'] = 'Max usage count per question attempt';
$string['default_code_helper_max_usage_count_per_question_attempt_desc'] = 'Max usage count of AI helper for one question attempt, set to 0 for unlimited usage';
$string['code_helper_enable_user_rating'] = 'Enable user rating';
$string['code_helper_enable_user_rating_desc'] = 'If true, user can rate on the AI response, tells the server whether this response is useful';
$string['code_helper_history_display_mode'] = 'AI helper history display mode';
$string['code_helper_history_display_mode_desc'] = 'The mode to display AI helper history';
$string['code_helper_history_display_mode_all'] = 'All';
$string['code_helper_history_display_mode_session'] = 'Session';
$string['code_helper_history_display_mode_active'] = 'Latest';
$string['code_helper_simple_assistant_mode'] = 'Code helper in simple mode';
$string['code_helper_simple_assistant_mode_desc'] = 'If checked, the code helper will only provide a single button to ask AI for help (with the first question of the predefined list), without the ability to choose or input user questions';

// Strings for question edit form
$string['code_helper_enabled'] = 'Enable code helper';
//$string['code_helper_enabled_desc'] = 'If set to true, the AI code helper will be displayed for this question';
$string['code_helper_omit_code_snippet'] = 'Omit code snippets in code helper response';
$string['code_helper_limit_usage_count_per_question_attempt'] = 'Limit usage count of code helper';
$string['code_helper_max_usage_count_per_question_attempt'] = 'Max usage count of code helper per attempt';
$string['code_helper_max_usage_count_per_question_attempt_help'] = <<<EOF
The maximum number of times that the AI code helper can be used for one question attempt of student.
Set 0 for unlimited usage.
Leave empty for using the default setting of this type of question.
EOF;

$string['bool_setting_true'] = 'Yes';
$string['bool_setting_false'] = 'No';
$string['default_setting'] = 'Default';
//$string['bool_setting_inherited'] = 'Inherited';

// question edit form validation
$string['err_integer_value_required'] = 'Requires a integer value';

// error messages
$string['err_code_helper_server_fetch_failed'] = 'Error: failed to fetch data from code helper server';
$string['err_code_helper_server_returns_error'] = 'Error: {$a->error_type}, {$a->error_msg}}';
$string['err_question_attempid_not_match'] = 'Error: question attempt id not match';
$string['err_code_helper_user_no_privilege'] = 'Error: user does not have permission to use code helper on this question attempt';
$string['err_code_helper_can_not_ini_question_attempt_from_params'] = 'Error: can not initialize question attempt from params';
$string['err_not_coderunnerex_question'] = 'Error: question is not a coderunner-ex question';
$string['err_code_helper_usage_count_exceeded'] = 'Error: code helper usage count exceeded';
$string['err_code_helper_empty_ask'] = 'Error: empty question asked';
$string['err_code_helper_record_not_found'] = 'Error: code helper record not found';
$string['err_user_already_rated'] = 'Error: user has already rated this response';




