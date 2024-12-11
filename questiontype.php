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
 * coderunnerex question definition classes.
 *
 * @package   qtype_coderunnerex
 * @copyright Ginger Jiang, China Pharmerutical University.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/coderunner/questiontype.php');
require_once($CFG->dirroot . '/question/type/coderunnerex/locallib.php');
require_once($CFG->dirroot . '/question/type/coderunnerex/lib/utils.php');

/**
 * qtype_coderunnerex extends the qtype_coderunner to add more customizations.
 */
class qtype_coderunnerex extends qtype_coderunner {
    /**
     * Returns extra question fields for coderunner-ex question,
     * stored in different table to the original coderunner type.
     * @return string[]
     */
    public function get_crex_extra_option_props() {
        return [
            'code_helper_enabled',
            'code_helper_max_usage_count_per_question_attempt',
            'code_helper_omit_code_snippet'
        ];
    }

    protected function save_question_extra_prop_to_db($question, $name, $value, $default_value = null) {
        qtype_coderunnerex_db_util::set_key_value_to_table('question_crex_props', 'questionid', $question->id, $name, $value, $default_value);
    }
    protected function load_question_extra_prop_from_db($question, $name, $default_value = null) {
        return qtype_coderunnerex_db_util::get_key_value_from_table('question_crex_props', 'questionid', $question->id, $name, $default_value);
    }

    /////////////////////////////////////////////////////

    /**
     * Override the save_question_options method to save extra options of question to db.
     * @param object $question
     */
    public function save_question_options($question) {
        parent::save_question_options($question);

        $extra_option_fields = $this->get_crex_extra_option_props();
        foreach($extra_option_fields as $field) {
            $question->options->$field = $question->$field;
        }

        /* save extra props of question to database */
        // code_helper options
        $this->save_question_extra_prop_to_db($question, 'ch_enabled', $question->code_helper_enabled, qtype_coderunnerex_inheritable_bool_setting::INHERITED);
        $this->save_question_extra_prop_to_db($question, 'ch_max_count_per_attempt', $question->code_helper_max_usage_count_per_question_attempt, qtype_coderunnerex_inheritable_int_setting::INHERITED);
        $this->save_question_extra_prop_to_db($question, 'ch_omit_code_snippet', $question->code_helper_omit_code_snippet, qtype_coderunnerex_inheritable_bool_setting::INHERITED);
    }

    /**
     * Override the get_question_options method to load extra options of question from db.
     * @param $question
     */
    public function get_question_options($question) {
        parent::get_question_options($question);

        /* load extra props of question from database */
        $value = $this->load_question_extra_prop_from_db($question, 'ch_enabled');
        $question->options->code_helper_enabled = qtype_coderunnerex_inheritable_bool_setting::from_str($value);

        $value = $this->load_question_extra_prop_from_db($question, 'ch_max_count_per_attempt');
        $question->options->code_helper_max_usage_count_per_question_attempt = qtype_coderunnerex_inheritable_int_setting::from_str($value);

        $value = $this->load_question_extra_prop_from_db($question, 'ch_omit_code_snippet');
        $question->options->code_helper_omit_code_snippet = qtype_coderunnerex_inheritable_bool_setting::from_str($value);

        return true;
    }

    /**
     * Initialise the question_definition object from the questiondata.
     * read from the database.
     * @param question_definition $question
     * @param object $questiondata
     * @return void
     */
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);

        // initial extra fields from $questiondata->options
        $extra_option_fields = $this->get_crex_extra_option_props();
        foreach($extra_option_fields as $field) {
            $question->$field = $questiondata->options->$field;
        }
    }

    // Override default question deletion code to delete all the extra data of question and testcases.
    public function delete_question($questionid, $contextid) {
        parent::delete_question($questionid, $contextid);
    }

    /**
     * Override the XML import method, handling extra options of question.
     * @param $data
     * @param $question
     * @param qformat_xml $format
     * @param $extra
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        $result = parent::import_from_xml($data, $question, $format, $extra);  // result is the question object

        // load extra props from XML
        $value = $format->getpath($data, array('#', 'enable_code_helper', 0, '#'), '');
        $result->enable_code_helper = qtype_coderunnerex_inheritable_bool_setting::from_str($value);

        $value = $format->getpath($data, array('#', 'code_helper_max_usage_count_per_question_attempt', 0, '#'), '');
        $result->code_helper_max_usage_count_per_question_attempt = qtype_coderunnerex_inheritable_int_setting::from_str($value);

        $value = $format->getpath($data, array('#', 'code_helper_omit_code_snippet', 0, '#'), '');
        $result->code_helper_omit_code_snippet = qtype_coderunnerex_inheritable_bool_setting::from_str($value);

        return $result;
    }

    /**
     * Override the XML export method, handling extra options of question and testcases.
     * @param $question
     * @param qformat_xml $format
     * @param $extra
     * @return string
     */
    public function export_to_xml($question, qformat_xml $format, $extra=null) {
        $result =  parent::export_to_xml($question, $format, $extra);
        // write extra props to XML
        // code helper options
        $extra_options_props = $this->get_crex_extra_option_props();
        foreach ($extra_options_props as $field) {
            $exportedvalue = $format->xml_escape($question->$field);
            $result .= "    <{$field}>{$exportedvalue}</{$field}>\n";
        }
        return $result;
    }
}