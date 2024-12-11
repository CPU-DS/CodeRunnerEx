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
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/behaviour/adaptive/behaviour.php');
require_once($CFG->dirroot . '/question/engine/questionattemptstep.php');
require_once($CFG->dirroot . '/question/behaviour/adaptive_adapted_for_coderunner/behaviour.php');
require_once($CFG->dirroot . '/question/type/coderunner/questiontype.php');
require_once($CFG->dirroot . '/question/type/coderunner/question.php');
require_once($CFG->dirroot . '/question/type/coderunnerex/locallib.php');
require_once($CFG->dirroot . '/question/type/coderunnerex/lib/utils.php');

/**
 * Represents a CodeRunnerEx question.
 */
class qtype_coderunnerex_question extends qtype_coderunner_question {
    const ENCRYPT_CODE_PREFIX = '[[enc]]';

    // extra fields for coderunnerex question type.
    public $code_helper_enabled = qtype_coderunnerex_inheritable_bool_setting::INHERITED;
    public $code_helper_max_usage_count_per_question_attempt = qtype_coderunnerex_inheritable_int_setting::INHERITED;
    public $code_helper_omit_code_snippet = qtype_coderunnerex_inheritable_bool_setting::INHERITED;


    public function __construct() {
        parent::__construct();
    }

    /**
     * Is the user answered code need to be transfer encrypted to server?
     * @return bool
     */
    public function is_client_answer_code_transfer_encrypted() {
        return qtype_coderunnerex_util::is_question_client_answer_code_transfer_encrypted($this);
    }

    /**
     * Is code helper enabled?
     * @return bool
     */
    public function is_code_helper_enabled() {
        $default_value = boolval(get_config('qtype_coderunnerex', 'default_enable_code_helper'));
        $result = qtype_coderunnerex_inheritable_bool_setting::get_value($this->code_helper_enabled, $default_value);
        return $result;
    }

    /**
     * Is code snippet should be omitted in code helper?
     * @return bool
     */
    public function is_code_helper_code_snippet_omitted() {
        $default_value = boolval(get_config('qtype_coderunnerex', 'default_code_helper_omit_code_snippet'));
        $result = qtype_coderunnerex_inheritable_bool_setting::get_value($this->code_helper_omit_code_snippet, $default_value);

        return $result;
    }

    /**
     * Decrypt the encrypted user response code.
     * @param string $encrypted_code
     * @return string
     */
    private function decrypt_user_response_code($encrypted_code) {
        return qtype_coderunnerex_util::decrypt_string($encrypted_code);
    }

    /**
     * Get the max usage count of code helper for this question.
     * @return int
     */
    public function get_concrete_code_helper_max_usage_count_per_question_attempt() {
        $default_value = intval(get_config('qtype_coderunnerex', 'default_code_helper_max_usage_count_per_question_attempt'));
        $result = qtype_coderunnerex_inheritable_bool_setting::get_value($this->code_helper_max_usage_count_per_question_attempt, $default_value);
        return $result;
    }

    /**
     * Get the remaining usage count of code helper for this question attempt.
     * @param object $question_attempt
     * @param int $quiz_attempt_id
     * @return int|null null means unlimited.
     */
    public function get_remaining_code_helper_usage_count_on_attempt($question_attempt, $quiz_attempt_id = null) {
        if (!isset($quiz_attempt_id))
            $quiz_attempt_id = qtype_coderunnerex_db_util::get_quiz_attemptid_by_uniqueid($question_attempt->get_usage_id());
        if (!isset($quiz_attempt_id)) {
            // not in quiz, perhaps a question preview? returns no limit
            return null;
        }

        $max_count = $this->get_concrete_code_helper_max_usage_count_per_question_attempt();

        if (empty($max_count)) {   // unlimited
            return null;
        } else {
            global $DB;
            $condition = array('questionattemptid' => $question_attempt->get_database_id());
            $used_count = $DB->count_records('question_crex_chelp_steps', $condition);
            return max(0, $max_count - $used_count);
        }
    }

    /**
     * Override default behaviour of coderunner, enable using special customized behavior class for coderunner questions.
     *
     * @param question_attempt $qa the attempt we are creating an behaviour for.
     * @param string $preferredbehaviour the requested type of behaviour.
     * @return question_behaviour the new behaviour object.
     */
    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        $defResult = question_engine::make_archetypal_behaviour($preferredbehaviour, $qa);
        if ($defResult instanceof qbehaviour_adaptive_adapted_for_coderunner) {
            return $defResult;
        }
        else
            return parent::make_behaviour($qa, $preferredbehaviour);
    }

    /**
     * What data may be included in the form submission when a student submits
     * this question in its current state?
     *
     * @return array|string variable name => PARAM_... constant
     */
    public function get_expected_data() {
        $result = parent::get_expected_data();
        if ($this->is_client_answer_code_transfer_encrypted()) {
            // encrypted answer is stored in this field
            $result['answer_encrypted'] = PARAM_RAW;
        }
        return $result;
    }

    protected function _fill_reponse_answer_from_encrypted_data(array $response) {
        if (!array_key_exists('answer', $response) && array_key_exists('answer_encrypted', $response)) {
            $response['answer'] = $this->decrypt_user_response_code($response['answer_encrypted']);
        }
        return $response;
    }

    /**
     * Override the original summarise_response method of coderunner, handling the encryption of user response.
     * @param array $response
     * @return mixed|string|null
     */
    public function summarise_response(array $response) {
        $response = $this->_fill_reponse_answer_from_encrypted_data($response);
        return parent::summarise_response($response);
    }

    /**
     * Override the original validate_response method of coderunner, handling the encryption of user response.
     * @param array $response
     * @return void
     */
    public function validate_response(array $response) {
        $response = $this->_fill_reponse_answer_from_encrypted_data($response);
        return parent::validate_response($response);
    }

    /**
     * Override the original is_same_response method of coderunner, handling the encryption of user response.
     * @param array $prevresponse
     * @param array $newresponse
     * @return void
     */
    public function is_same_response(array $prevresponse, array $newresponse) {
        $prevresponse = $this->_fill_reponse_answer_from_encrypted_data($prevresponse);
        $newresponse = $this->_fill_reponse_answer_from_encrypted_data($newresponse);
        return parent::is_same_response($prevresponse, $newresponse);
    }

    /**
     * Override the original grade method of coderunner, handling the encryption of user response.
     * @param array $response
     * @param bool $isprecheck
     * @param bool $isvalidationrun
     * @return void
     */
    public function grade_response(array $response, bool $isprecheck = false, $isvalidationrun = false) {
        $response = $this->_fill_reponse_answer_from_encrypted_data($response);
        return parent::grade_response($response, $isprecheck, $isvalidationrun);
    }
}