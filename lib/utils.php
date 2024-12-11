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


/**
 * Utility routines for qtype_coderunnerex
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
global $DB;

/**
 * Consts of code helper history display mode.
 */
class qtype_coderunnerex_code_helper_history_display_mode {
    // only display history of current interaction
    const SHOWN_SESSION = 0;
    // display all history data, loading them from database
    const SHOWN_ALL = 1;
    // hide all history, only displays the last interaction
    const SHOWN_ACTIVE = 2;
}

/**
 * Some utility routines for qtype_coderunnerex.
 */
class qtype_coderunnerex_util {

    const ENCRYPT_HTML_PLACEHOLDER_CLASS_NAME = 'CodeRunnerEx-EncPlaceholder';

    /**
     * Returns the default user questions for input to AI helper.
     * @return string[]
     */
    public static function get_default_codehelper_asks() {
        return [
            '请分析我的代码，找出问题或进行优化。',
            '为什么我的答案错了？',
            '能提示我这道题的思路么？',
            '我要如何改进我目前的代码？'
        ];
    }

    public static function get_default_codehelper_question_body_clean_patterns() {
        return [
            '/原题链接[:：][\s]*<a href=".*">.*<\/a>/i'
        ];
    }

    /**
     * Insert a special JS module to page, to load the styles of CodeRunner question.
     * @param $page
     */
    public static function apply_inherited_cr_styles($page) {
        $page->requires->js_call_amd(
            'qtype_coderunnerex/styleinherits',
            'init',
            []
        );
    }

    /*
    public static function extract_question_info_to_json(question_definition $question) {
        $data = [
            'id' => $question->id,
            'question_type' => $question->qtype,
            'name' => $question->name,
            'question_text' => $question->questiontext,
            'question_text_format' => $question->questiontextformat,
            'general_feedback' => $question->generalfeedback,
            'general_feedback_format' => $question->generalfeedbackformat,
            'default_mark' => $question->defaultmark,
            'penalty' => $question->penalty,
            'created_time' => $question->timecreated,
            'modified_time' => $question->timemodified,
            'version' => $question->version,

        ];
    }
    */

    public static function json_encode_question(question_definition $question, $options = []) {
        $copy_fields = [
            'testcases', 'coderunnertype', 'template', 'language', 'grader', 'answers', 'student',
            'questiontext', 'questiontextformat', 'generalfeedback', 'generalfeedbackformat',
            'defaultmark', 'penalty', 'stamp', 'timecreated', 'timemodified', 'versionid', 'version', 'questionbankentryid',
            'customfields'
        ];
        $output_obj = new stdClass();
        foreach ($copy_fields as $key) {
            $output_obj->$key = $question->$key;
        }
        if (!empty($options['question_body_clean_patterns'])) {
            foreach ($options['question_body_clean_patterns'] as $pattern) {
                $output_obj->questiontext = preg_replace($pattern, '', $output_obj->questiontext);
            }
        }

        $result = json_encode($output_obj);
        return $result;
    }

    public static function json_encode_question_attempt_step_data($step_data) {
        $data = new stdClass();
        if (array_key_exists('answer', $step_data))
            $data->answer = $step_data['answer'];
        if (array_key_exists('_testoutcome', $step_data))
            $data->test_outcome = unserialize($step_data['_testoutcome']);
        return json_encode($data);
    }

    /**
     * Encrypt a string for client-server data transfer encryption.
     * @param $string
     * @return string
     */
    public static function encrypt_string($string) {
        return base64_encode($string);
    }

    /**
     * Decrypt a string for client-server data transfer encryption.
     * @param $string
     * @return string
     */
    public static function decrypt_string($string) {
        return base64_decode($string);
    }

    /**
     * Check whether need to encrypt the user input code from client to server.
     * @param object $question
     * @return bool
     */
    public static function is_question_client_answer_code_transfer_encrypted($question) {
        return boolval(get_config('qtype_coderunnerex', 'encrypt_answer_transfer_from_client'));
    }
}

/**
 * Utility routines of database functions for qtype_coderunnerex.
 */
class qtype_coderunnerex_db_util {

    /**
     * Store a key-value pair to database table.
     * The key and value related to an object appointed by $obj_id_field and $obj_id (in another table).
     * @param string $table
     * @param string $obj_id_field
     * @param int $obj_id
     * @param string $name
     * @param string $value
     * @param string $default_value
     */
    static public function set_key_value_to_table($table, $obj_id_field, $obj_id, $name, $value, $default_value = null) {
        global $DB;

        $record = new stdClass();
        $record->$obj_id_field = $obj_id;
        $record->name = $name;
        $record->value = $value;

        $old_record = $DB->get_record(
            $table,
            [$obj_id_field => $obj_id, 'name' => $name]
        );

        $new_value_equals_default = isset($default_value) && ($value === $default_value);

        if ($old_record) {
            if ($new_value_equals_default)
                $DB->delete_records($table,  [$obj_id_field => $obj_id, 'name' => $name]);
            else
                $DB->update_record($table, $record);
        } else {
            if (!$new_value_equals_default)
                $DB->insert_record($table, $record);
        }
    }

    /**
     * Retrieve a key-value pair from database table.
     * The key and value related to an object appointed by $obj_id_field and $obj_id (in another table).
     * @param string $table
     * @param string $obj_id_field
     * @param int $obj_id
     * @param string $name
     * @param string $default_value
     * @return string
     */
    static public function get_key_value_from_table($table, $obj_id_field, $obj_id, $name, $default_value = null) {
        global $DB;
        $record = $DB->get_record(
            $table,
            [$obj_id_field => $obj_id, 'name' => $name],
        );

        return isset($record)? $record->value: $default_value;
    }

    /**
     * Retrieve all key-value pairs from database table.
     * The key and value related to an object appointed by $obj_id_field and $obj_id (in another table).
     * @param string $table
     * @param string $obj_id_field
     * @param int $obj_id
     * @return array
     */
    static public function get_all_key_values_from_table($table, $obj_id_field, $obj_id) {
        global $DB;
        $records = $DB->get_records(
            $table,
            [$obj_id_field => $obj_id]
        );
        $result = [];
        foreach ($records as $record) {
            $result[$record->name] = $record->value;
        }
        return $result;
    }

    /**
     * Retrieve quiz attempt id from a Moodle activity unique id.
     * @param int $quiz_uniqueid
     * @return int
     */
    static public function get_quiz_attemptid_by_uniqueid($quiz_uniqueid) {
        global $DB;
        $quizid = null;
        $quiz_records = $DB->get_records('quiz_attempts', ['uniqueid' => $quiz_uniqueid]);
        if (count($quiz_records) > 0) {
            $quizid = array_keys($quiz_records)[0];
        }
        return $quizid;
    }
}