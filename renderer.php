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
 * CodeRunnerEx renderer class.
 *
 * @package   qtype_coderunnerex
 * @copyright Ginger Jiang, China Pharmerutical University.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/question/type/coderunner/renderer.php');
require_once($CFG->dirroot . '/question/type/coderunnerex/lib/classInvader.php');
require_once($CFG->dirroot . '/question/type/coderunnerex/lib/utils.php');


/**
 * Util class for the qtype_coderunnerex_renderer.
 */
class qtype_coderunnerex_renderer_helper {
    /**
     * Check if the test case is shown but failed, whether the expected result need to be hidden
     * @param object $testresult
     * @return bool
     */
    private static function need_to_hide_expection($testresult) {
        $DEFAULT_HIDE = false;

        if ($testresult->iscorrect || $testresult->useasexample)
            return false;
        else if ($DEFAULT_HIDE) {
            if (strlen($testresult->extra) <= 0)
                return true;
            else {
                $lines = explode('\n', $testresult->extra);
                foreach ($lines as $line) {
                    if (strcmp(trim($line), '{$showExpectedWhenFailed}') == 0) {
                        return false;
                    }
                }
                return true;
            }
        }
        else if (!$DEFAULT_HIDE) {
            if (strlen($testresult->extra) <= 0)
                return false;
            else {
                $lines = explode('\n', $testresult->extra);
                foreach ($lines as $line) {
                    if (strcmp(trim($line), '{$hideExpectedWhenFailed}') == 0) {
                        return true;
                    }
                }
                return false;
            }
        }
    }

    /**
     * Create a table cell object with content for a field.
     * @param string $content
     * @param string $field
     * @return array
     */
    private static function _create_table_cell_info($content, $field) {
        return array('field' => $field, 'content' => $content);
    }

    /**
     * A modified version of qtype_coderunner_testing_outcome::build_results_table.
     * @param object $question
     * @param object $outcome
     * @return array
     */
    public static function render_test_outcome_results_table($question, $outcome) {
        $outcome_invader = invade($outcome);

        $resultcolumns = $question->result_columns();

        $canviewhidden = qtype_coderunner_testing_outcome::can_view_hidden();

        // Build the table header, containing all the specified field headers,
        // unless all rows in that column would be blank.

        $columnheaders = array('iscorrect'); // First column is a tick or cross, like last column.
        $hiddencolumns = array();  // Array of true/false for each element of $colspec.
        $numvisiblecolumns = 0;

        /////////// Added by GINGER, record the column field names ////////////
        $column_field_map = array('iscorrect' => 'iscorrect');
        /////////// Added end /////////////////////////////////////////////////

        foreach ($resultcolumns as $colspec) {

            $len = count($colspec);
            if ($len < 3) {
                $colspec[] = '%s';  // Add missing default format.
            }
            $header = $colspec[0];
            $field = $colspec[1];  // Primary field - there may be more.
            $numnonblank = $outcome_invader->count_non_blanks($field, $outcome->testresults);
            if ($numnonblank == 0) {
                $hiddencolumns[] = true;
            } else {
                $columnheaders[] = $header;
                $hiddencolumns[] = false;
                $numvisiblecolumns += 1;
                /////////// Added by GINGER, record the column field names ////////////
                $column_field_map[$header] = $field;
                /////////// Added end /////////////////////////////////////////////////
            }
        }
        if ($numvisiblecolumns > 1) {
            $columnheaders[] = 'iscorrect';  // Tick or cross at the end, unless <= 1 visible columns.
        }
        $columnheaders[] = 'ishidden';   // Last column controls if row hidden or not.
        $column_field_map['ishidden'] = 'ishidden';

        /////////// Modified by GINGER, record the column field info ////////////
        // $table = array($columnheaders);  // original
        /////////////////////////////////////////////////////////////////////////
        $column_header_info = array();
        for ($i = 0, $l = count($columnheaders); $i < $l; $i++) {
            $column_header_info[] = self::_create_table_cell_info($columnheaders[$i], $column_field_map[$columnheaders[$i]]);
        }

        $table = array($column_header_info);
        /////////// Modification end ////////////////////////////////////////////

        // Process each row of the results table.
        $hidingrest = false;
        foreach ($outcome->testresults as $testresult) {
            $testisvisible = $outcome_invader->should_display_result($testresult) && !$hidingrest;
            if ($canviewhidden || $testisvisible) {
                $fraction = $testresult->awarded / $testresult->mark;
                $tablerow = array($fraction);   // Will be rendered as tick or cross.
                $icol = 0;

                /// ADDED by GINGER
                $hideExpection = self::need_to_hide_expection($testresult);
                /// ADDED END

                foreach ($resultcolumns as $colspec) {
                    $len = count($colspec);
                    if ($len < 3) {
                        $colspec[] = '%s';  // Add missing default format.
                    }
                    if (!$hiddencolumns[$icol]) {
                        $len = count($colspec);
                        $format = $colspec[$len - 1];

                        /////////// Modified by GINGER, record the column field info with new create_table_cell_info function ////////////

                        /// ADDED by GINGER
                        if ($colspec[1] === 'expected' && $hideExpection) {
                            $tablerow[] = self::_create_table_cell_info('-', $column_field_map[$colspec[0]]);
                        } else {
                            /// ADDED END

                            if ($format === '%h') {  // If it's an html format, use value wrapped in an HTML wrapper.
                                $value = $testresult->gettrimmedvalue($colspec[1]);
                                $tablerow[] = self::_create_table_cell_info(new qtype_coderunner_html_wrapper($value), $column_field_map[$colspec[0]]);
                            } else if ($format !== '') {  // Else if it's a non-null column.
                                $args = array($format);
                                for ($j = 1; $j < $len - 1; $j++) {
                                    $value = $testresult->gettrimmedvalue($colspec[$j]);
                                    $args[] = $value;
                                }
                                $content = call_user_func_array('sprintf', $args);
                                $tablerow[] = self::_create_table_cell_info($content, $column_field_map[$colspec[0]]);
                            }
                        }
                        /////////// Modification end ////////////////////////////////////////////
                    }

                    $icol += 1;
                }
                if ($numvisiblecolumns > 1) { // Suppress trailing tick or cross in degenerate case.
                    $tablerow[] = $fraction;
                }
                $tablerow[] = !$testisvisible;
                $table[] = $tablerow;
            }

            if ($testresult->hiderestiffail && !$testresult->iscorrect) {
                $hidingrest = true;
            }

        }

        return $table;
    }
}

/**
 * Subclass for generating the bits of output specific to coderunnerex questions.
 * In this class, we only modify method build_results_table a little, to generate a
 * slightly different output from testoutcome object.
 *
 * @copyright Ginger Jiang, China Pharmerutical University.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_coderunnerex_renderer extends qtype_coderunner_renderer {
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        // apply inherited CodeRunner styles
        qtype_coderunnerex_util::apply_inherited_cr_styles($this->page);
    }

    public function head_code(question_attempt $qa) {
        $result = parent::head_code($qa);

        $encrypt_server_transfer = $this->is_server_code_transfer_encrypted($qa);
        $encrypt_client_transfer = $this->is_client_answer_code_transfer_encrypted($qa);

        $response_field_id = $this->get_ex_output_html_elem_id($qa, 'answer');

        if ($encrypt_server_transfer || $encrypt_client_transfer) {
            $jsInitParams = new stdClass();
            $jsInitParams->encryptServerDataTransfer = $encrypt_server_transfer;
            $jsInitParams->encryptClientDataTransfer = $encrypt_client_transfer;
            $jsInitParams->responseFieldId = $response_field_id;
            $this->page->requires->js_call_amd(
                'qtype_coderunnerex/encrypttransfer',
                'init',
                [$jsInitParams]
            );
        }

        if ($this->is_testcase_tools_enabled($qa)) {
            // load testcase tool JS module
            $jsInitParams = (object)[
                'displayCopyButtonForExampleTable' => boolval(get_config('qtype_coderunnerex', 'copy_button_for_example_table')),
                'displayCopyButtonForTestCaseCode' => boolval(get_config('qtype_coderunnerex', 'copy_button_for_test_code')),
                'displayCopyButtonForTestCaseExpected' => boolval(get_config('qtype_coderunnerex', 'copy_button_for_test_expected')),
                'displayCopyButtonForTestCaseInput' => boolval(get_config('qtype_coderunnerex', 'copy_button_for_test_input')),
                'displayCopyButtonForTestCaseOutput' => boolval(get_config('qtype_coderunnerex', 'copy_button_for_test_output'))
            ];
            $this->page->requires->js_call_amd(
                'qtype_coderunnerex/testcasetools',
                'init',
                [$jsInitParams]
            );
        }

        return $result;
    }



    /////////////////////////////////////////////
    /// Added by GINGER
    private static function _create_table_cell_obj($content, $field) {
        $cell_obj = new html_table_cell($content);
        $cell_obj->attributes['class'] = 'col_' . $field;
        return $cell_obj;
    }
    ////Added end ///////////////////////////////
    ///
    // Generate the main feedback, consisting of (in order) any prologuehtml,
    // a table of results and any epiloguehtml. Finally append a warning if
    // question is being tested using the University of Canterbury's testing
    // Jobe server.
    protected function build_results_table($outcome, qtype_coderunner_question $question) {
        global $CFG;
        $fb = $outcome->get_prologue();

        ////////////////////////////// Original /////////////////////////////////////
        // $testresults = $outcome->get_test_results($question);
        /////////////////////////////////////////////////////////////////////////////
        ////////////////////////////// Modified by Ginger ///////////////////////////
        /// More controlling of test results table built from $outcome
        $testresults = qtype_coderunnerex_renderer_helper::render_test_outcome_results_table($question,$outcome);
        ////////////////////////////// Modification End /////////////////////////////


        if (is_array($testresults) && count($testresults) > 1) {
            $table = new html_table();
            $table->attributes['class'] = 'coderunner-test-results';
            /////////////////////////////////////////////
            ///  Modified by GINGER, instead of simple string, now $tablerow[] will be an html_table_cell instance to output more complicated HTML
            //  $table->head[] = strtolower($header) === 'iscorrect' ? '' : $header;  // original
            ////////////////////////////////////////////////
            $header_infos = $testresults[0];
            $headers = [];
            foreach ($header_infos as $header) {
                $header_field = is_array($header)? $header['field']: null;
                $header_content = is_array($header)? $header['content']: $header;
                $headers[] = $header_content;
                $header_content = strtolower($header_content) === 'iscorrect' ? '' : $header_content;
                if (strtolower($header_content) != 'ishidden') {
                    if (!empty($header_field) && !empty($header_content)) {
                        $table->head[] = self::_create_table_cell_obj($header_content, $header_field);
                    } else
                        $table->head[] = $header_content;
                }
            }
            /// Modificaton end ////////////////////////////

            $rowclasses = [];
            $tablerows = [];

            $n = count($testresults);
            /////////////////////////////////////////////
            ///  Modified by GINGER, instead of simple string, now $tablerow[] will be an html_table_cell instance to output more complicated HTML
            for ($i = 1; $i < $n; $i++) {
                $cells = $testresults[$i];
                $rowclass = $i % 2 == 0 ? 'r0' : 'r1';
                $tablerow = [];
                $j = 0;
                foreach ($cells as $cell) {
                    $table_cell_content = false;

                    $cell_content = is_array($cell)? $cell['content']: $cell;
                    $cell_field = is_array($cell)? $cell['field']: null;

                    if (strtolower($headers[$j]) === 'iscorrect') {
                        // $markfrac = (float) $cell;
                        $markfrac = (float) $cell_content;
                        $table_cell_content = $this->feedback_image($markfrac);
                    } else if (strtolower($headers[$j]) === 'ishidden') { // Control column.
                        // if ($cell) { // Anything other than zero or false means hidden.
                        if ($cell_content) {
                            $rowclass .= ' hidden-test';
                        }
                    // } else if ($cell instanceof qtype_coderunner_html_wrapper) {
                    } else if ($cell_content instanceof qtype_coderunner_html_wrapper) {
                        // $table_cell_content = $cell->value();  // It's already HTML.
                        $table_cell_content = $cell_content->value();  // It's already HTML.
                    } else {
                        // $table_cell_content = qtype_coderunner_util::format_cell($cell);
                        $table_cell_content = qtype_coderunner_util::format_cell($cell_content);
                    }
                    // $tablerow[] = $table_cell_content;
                    if ($table_cell_content !== false) {
                        if (!empty($cell_field) && !empty($table_cell_content)) {
                            $tablerow[] = self::_create_table_cell_obj($table_cell_content, $cell_field);
                        } else
                            $tablerow[] = $table_cell_content;
                    }

                    $j++;
                }
                $tablerows[] = $tablerow;
                $rowclasses[] = $rowclass;
            }
            ///////// Modification end ///////////////////////////////////////////
            $table->data = $tablerows;
            $table->rowclasses = $rowclasses;
            $fb .= html_writer::table($table);
        }
        $fb .= $outcome->get_epilogue();

        // Issue a bright yellow warning if using jobe2, except when running behat.
        $sandboxinfo = $outcome->get_sandbox_info();
        if (isset($sandboxinfo['jobeserver'])) {
            $jobeserver = $sandboxinfo['jobeserver'];
            $apikey = $sandboxinfo['jobeapikey'];
            if (qtype_coderunner_sandbox::is_canterbury_server($jobeserver)
                && (!qtype_coderunner_sandbox::is_using_test_sandbox())) {
                if ($apikey == constants::JOBE_HOST_DEFAULT_API_KEY) {
                    $fb .= get_string('jobe_warning_html', 'qtype_coderunner');
                } else {
                    $fb .= get_string('jobe_canterbury_html', 'qtype_coderunner');
                }
            }
        }

        return $fb;
    }

    private function is_server_code_transfer_encrypted(question_attempt $question_attempt) {
        $result = boolval(get_config('qtype_coderunnerex', 'encrypt_code_and_output_transfer_from_server'));
        if ($result) {
            // check if question is using ace ui plugin, if not, currently can not handle
            $question = $question_attempt->get_question(false);
            $ui_plugin = $question->uiplugin;
            if (!empty($ui_plugin) && !($ui_plugin === 'ace' || $ui_plugin === 'scratchpad' || $ui_plugin === 'none')) {
                $result = false;
            }
        }
        return $result;
    }

    private function is_client_answer_code_transfer_encrypted(question_attempt $question_attempt) {
        $question = $question_attempt->get_question(false);
        return qtype_coderunnerex_util::is_question_client_answer_code_transfer_encrypted($question);
    }

    private function is_testcase_tools_enabled(question_attempt $question_attempt) {
        $result = boolval(get_config('qtype_coderunnerex', 'copy_button_for_test_input'))
            || boolval(get_config('qtype_coderunnerex', 'copy_button_for_test_output'))
            || boolval(get_config('qtype_coderunnerex', 'copy_button_for_test_code'))
            || boolval(get_config('qtype_coderunnerex', 'copy_button_for_test_expected'))
            || boolval(get_config('qtype_coderunnerex', 'copy_button_for_example_table'))
        ;
        return $result;
    }

    private function get_ex_output_html_elem_id($qa, $fieldname) {
        return 'id_' . $qa->get_qt_field_name($fieldname);
    }

    private function question_and_outcome_ai_helper(question_attempt $qa, string $elem_id, question_display_options $options) {
        $question = $qa->get_question();

        // question meta
        $clean_patterns = get_config('qtype_coderunnerex', 'code_helper_question_body_clean_patterns');
        if (!empty($clean_patterns))
            $clean_patterns = explode("\n", $clean_patterns);
        else
            $clean_patterns = qtype_coderunnerex_util::get_default_codehelper_question_body_clean_patterns();
        $question_export_options = [];
        $question_export_options['question_body_clean_patterns'] = $clean_patterns;

        $cmid = !empty($this->page->cm)? $this->page->cm->id: null;

        $is_reviewing = $options->readonly && $options->history;

        $result = html_writer::tag('div', '', [
            'id' => $elem_id,
            'class' => 'CodeRunnerEx-Hidden',
            'data-question-attempt-id' => $qa->get_database_id(),
            'data-question-attempt-step-id' => $qa->get_last_step()->get_id(),
            'data-question-usage-id' => $qa->get_usage_id(),
            'data-slot' => $qa->get_slot(),
            'data-cm-id' => $cmid,  // important Context module ID for privilege check, but in question preview page, $this->page->cm is null and $cmid can not be got
        ]);

        return $result;
    }
    private function code_helper_placeholder(question_attempt $qa, string $elem_id) {
        $result = html_writer::start_tag('button', [
            'id' => $elem_id,
            'class' => 'CodeRunnerEx-CodeHelper-Thumbnail btn btn-secondary',
            'title' => get_string('codehelper_thumbnail_hint', 'qtype_coderunnerex'),
            'disabled' => 'disabled'
        ]);
        $result .= html_writer::tag('span', get_string('codehelper_thumbnail_caption', 'qtype_coderunnerex'), [
            'class' => 'ButtonText'
        ]);
        $result .= html_writer::end_tag('button');
        return $result;
    }

    /*
    public function feedback(question_attempt $qa, question_display_options $options) {
        $result = '<h1>FEEDBACK</h1>';

        $result .= parent::feedback($qa, $options);

        return $result;
    }
    */


    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {

        $result = parent::formulation_and_controls($qa, $options);

        $current_answer_encrypted = $qa->get_last_qt_var('answer_encrypted');
        if (!empty($current_answer_encrypted)) {
            // the client response code is encrypted, create an element to hold it and preparing to decrypt it by JS
            $decrypted_answer = qtype_coderunnerex_util::decrypt_string($current_answer_encrypted);
            $result = $this->update_content_of_answer_code_editor($qa, $result, $decrypted_answer);
        }

        // TODO: if client/server encryption is both enable, here we decrypt the response in <textarea> first, then encrypt it again here. This process may be improved.
        if ($this->is_server_code_transfer_encrypted($qa)) {
            // create a placeholder
            $result = $this->encrypt_answer_code_editor($qa, $result, 'CodeRunnerEx-Encrypt-Answer-Input-Placeholder');
        }


        $question_attempt_meta_elem_id = $this->get_ex_output_html_elem_id($qa, 'question_meta');
        $codehelper_placeholder_id = $this->get_ex_output_html_elem_id($qa, 'codehelper_placeholder');
        $responsefieldid = $this->get_ex_output_html_elem_id($qa, 'answer');

        $enable_code_helper = $qa->get_question()->is_code_helper_enabled();

        if ($enable_code_helper) {
            global $CFG;

            $ai_request_url = $CFG->wwwroot . '/question/type/coderunnerex/services/aihelper.php';
            $ai_request_rate_url = $CFG->wwwroot . '/question/type/coderunnerex/services/rate_ai_response.php';

            // the placeholder of code helper widgets (created by JS)
            $result .= $this->code_helper_placeholder($qa, $codehelper_placeholder_id);

            // add a hidden element to pass question and test outcome data to JavaScript
            $result .= $this->question_and_outcome_ai_helper($qa, $question_attempt_meta_elem_id, $options);

            // UI Javascript
            $predefined_questions = get_config('qtype_coderunnerex', 'code_helper_predefined_asks');

            if (!empty($predefined_questions)) {
                $predefined_questions = explode("\n", $predefined_questions);
            } else {
                $predefined_questions = qtype_coderunnerex_util::get_default_codehelper_asks();
            }

            $history_display_mode = intval(get_config('qtype_coderunnerex', 'code_helper_history_display_mode'));
            $enable_custom_question = !get_config('qtype_coderunnerex', 'code_helper_disable_custom_asks');
            $enable_user_rating = boolval(get_config('qtype_coderunnerex', 'code_helper_enable_user_rating'));
            $simple_mode = boolval(get_config('qtype_coderunnerex', 'code_helper_simple_assistant_mode'));

            $jsInitParams = new stdClass();
            $jsInitParams->codeHelperPlaceHolderId = $codehelper_placeholder_id;
            $jsInitParams->questionMetaElemId = $question_attempt_meta_elem_id;
            $jsInitParams->targetEditorId = $responsefieldid;
            $jsInitParams->aiHelperRequestUrl = $ai_request_url;
            $jsInitParams->aiHelperRateUrl = $ai_request_rate_url;
            $jsInitParams->aiHelperPredefinedQuestions = $predefined_questions;
            $jsInitParams->aiHelperRemainingUsageCount = $qa->get_question()->get_remaining_code_helper_usage_count_on_attempt($qa);
            $jsInitParams->enableCustomQuestion = $enable_custom_question;
            $jsInitParams->enableUserRating = $enable_user_rating;
            $jsInitParams->readOnly = $options->readonly;
            $jsInitParams->historyDisplayMode = $history_display_mode;
            $jsInitParams->codeHelperInSimpleMode = $simple_mode;
            $this->page->requires->js_call_amd(
                'qtype_coderunnerex/codehelpers',
                'init',
                [$jsInitParams]
            );
        }

        return $result;
    }

    private function get_answer_code_editor_html_pattern() {
        return '/(<textarea[^>]*>)\n?(.*?)(<\/textarea>)/s';  // \n? is used to remove the possible leading empty line inside textarea
    }
    protected function update_content_of_answer_code_editor($qa, $html, $code) {
        $pattern = $this->get_answer_code_editor_html_pattern();
        $result = preg_replace_callback($pattern, function ($matches) use ($code) {
            $result = $matches[1] . $code . $matches[3];
            return $result;
        }, $html);
        return $result;
    }
    protected function encrypt_answer_code_editor($qa, $html, $class_name) {
        $question = $qa->get_question(false);
        $ui_plugin = $question->uiplugin;
        if (empty($ui_plugin))
            $ui_plugin = 'ace';
        $pattern = $this->get_answer_code_editor_html_pattern();
        $result = preg_replace_callback($pattern, function ($matches) use ($class_name, $ui_plugin) {
            /*
            $textarea_start = $matches[1];
            $textarea_content = $matches[2];
            $textarea_end = $matches[3];
            $encrypted_textarea_content = qtype_coderunnerex_util::encrypt_string($textarea_content);
            return $textarea_start . $encrypted_textarea_content . $textarea_end;
            */

            // retrieve id of this start tag
            $textarea_start = $matches[1];
            $id_attr_pattern = '/id=[\'"]([^\'"]+)[\'"]/';
            $id_matches = [];
            preg_match($id_attr_pattern, $textarea_start, $id_matches);
            $textarea_id = $id_matches[1];

            $textarea_html = $matches[0];
            $textarea_content = $matches[2];
            $encrypt_html = $this->output_encrypt_placeholder_html_elem($textarea_content, 'div', $class_name, [
                'data-target-id' => $textarea_id,
                'data-code-editor' => $ui_plugin
            ]);
            $encrypt_html .= $matches[1] . $matches[3];   // provide an empty textarea, so that the coderunner can use it to create the ace editor
            return $encrypt_html;
        }, $html);
        return $result;
    }

    /**
     * Return the HTML to display the sample answer, if given.
     * @param question_attempt $qa
     * @return string The html for displaying the sample answer.
     */
    public function correct_response(question_attempt $qa) {
        $html = parent::correct_response($qa);
        if ($this->is_server_code_transfer_encrypted($qa)) {
            // create a placeholder
            return $this->encrypt_answer_code_editor($qa, $html, 'CodeRunnerEx-Encrypt-Correct-Answer-Placeholder');
        } else {
            return $html;
        }
    }

    /**
     * Generate the specific feedback. This is feedback that varies according to
     * the response the student gave.
     * @param question_attempt $qa the question attempt to display.
     * @return string HTML fragment.
     */
    protected function specific_feedback(question_attempt $qa) {
        $result = parent::specific_feedback($qa);
        if ($this->is_server_code_transfer_encrypted($qa)) {
            // create a placeholder
            $result = $this->output_encrypt_placeholder_html_elem($result, 'div', 'CodeRunnerEx-Encrypt-Feedback-Placeholder');
        } else {
            // do nothing
        }

        return $result;
    }

    protected function output_encrypt_placeholder_html_elem_with_encrypted_content($encrypted_html, $place_holder_tag_name = 'div', $html_class_name = '', $attributes = []) {
        $attrs = array_merge($attributes, [
            'class' => $html_class_name . ' ' . qtype_coderunnerex_util::ENCRYPT_HTML_PLACEHOLDER_CLASS_NAME,
            'data-raw' => $encrypted_html,
            'style' => 'display: none'
        ]);
        $result = html_writer::start_tag($place_holder_tag_name, $attrs);
        $result .= html_writer::end_tag('div');
        return $result;
    }
    protected function output_encrypt_placeholder_html_elem($origin_html, $place_holder_tag_name = 'div', $html_class_name = '', $attributes = []) {
        $encrypt_html = qtype_coderunnerex_util::encrypt_string($origin_html);
        return $this->output_encrypt_placeholder_html_elem_with_encrypted_content($encrypt_html, $place_holder_tag_name, $html_class_name, $attributes);
    }
}