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

require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/engine/datalib.php');
require_once($CFG->dirroot . '/question/type/coderunnerex/lib/utils.php');

/**
 * Util functions and classes of AI helper.
 */

/**
 * Util to make http requests.
 */
class HttpUtils {
    const HTTP_GET = 1;
    const HTTP_POST = 2;
    // Send an http request to the server at the given
    // resource using the given method (self::HTTP_GET or self::HTTP_POST).
    // The body, if given, is json encoded and added to the request.
    // Return value is a 2-element
    // array containing the http response code and the response body (decoded
    // from json).
    // The code is -1 if the request fails utterly.
    // Note that the Moodle curl class documentation lies when it says the
    // return value from get and post is a bool. It's either the value false
    // if the request failed or the actual string response, otherwise.
    // If you pass a curl object, this will be used to make the request.
    static public function request($url, $method = self::HTTP_GET, $body = null, $headers = [], $curl = null) {
        if ($curl == null) {
            $curl = new curl();
        }
        $curl->setHeader($headers);

        if ($method === self::HTTP_GET) {
            if (!empty($body)) {
                throw new coding_exception("Illegal HTTP GET: non-empty body");
            }
            $response = $curl->get($url);
        } else if ($method === self::HTTP_POST) {
            if (empty($body)) {
                throw new coding_exception("Illegal HTTP POST: empty body");
            }
            $bodyjson = json_encode($body);
            $response = $curl->post($url, $bodyjson);
        } else {
            throw new coding_exception('Invalid method passed to http_request');
        }

        if ($response !== false) {
            // We got a response rather than a completely failed request.
            if (isset($curl->info['http_code'])) {
                $returncode = $curl->info['http_code'];
                $responsebody = $response;
            } else {
                // Various weird stuff lands here, such as URL blocked.
                // Hopefully the value of $response is useful.
                $returncode = -1;
                $responsebody = json_encode($response);
            }
        } else {
            // Request failed.
            $returncode = -1;
            $responsebody = '';
        }

        return [$returncode, $responsebody];
    }
}

/**
 * Util to interact with AI helper server.
 */
class AiHelperUtils {
    /**
     * Default data values send to AI helper server.
     */
    const DEFAULT_REQUEST_DATA_FIELDS = [
        'questionLanguage' => 'python',
        'omitCodeSnippet' => false,
        'token' => null,
        'questionBody' => '您是一位编程专家，您的任务是根据用户提供的题目、样例与提问，对给出的用户代码进行详细分析，指出其中可能的错误，提供相应的解决思路。请使用中文进行回答，确保您的回答既专业又易于理解。',
        'sysPrompt' => '', // '您是一位Python编程专家，您的任务是根据用户提供的题目和样例，对给出的Python代码进行详细分析。当您发现代码中存在错误时，需要明确指出错误的具体位置，并提供修正后的正确代码。请使用中文进行回答，确保您的回答既专业又易于理解。请等待用户提供需要审查的代码段，然后根据上述要求进行分析与修改建议。',
        'userQuestion' => '',
        'userCode' => ''
    ];

    /**
     * Default code snippet placeholder when the actual code is omitted.
     */
    const CODE_OMIT_PLACEHOLDER = '[Code omitted]';

    /**
     * Omit code snippets in the given content string.
     * @param string $content
     * @param string $placeholder
     * @return string
     */
    static private function omit_code_snippet($content, $placeholder = self::CODE_OMIT_PLACEHOLDER) {
        $lines = explode("\n", $content);
        $new_lines = self::omit_code_snippet_lines($lines, $placeholder);
        return implode("\n", $new_lines);
    }

    /**
     * Omit code snippets in the given content lines.
     * @param array $content_lines
     * @param string $placeholder
     * @return array Result string lines.
     */
    static private function omit_code_snippet_lines($content_lines, $placeholder = self::CODE_OMIT_PLACEHOLDER) {
        $result = [];
        $inside_code_snippet = false;

        $code_snippet_intro_pattern = '/^##.*代码.*$/';
        $code_snippet_begin_pattern = '/^```[\S]+$/';
        $code_snippet_end_pattern = '/^```$/';

        foreach ($content_lines as $line) {
            $trimmed_line = trim($line);
            if (!$inside_code_snippet && preg_match($code_snippet_intro_pattern, $trimmed_line)) {
                $inside_code_snippet = true;
                continue;
            }
            if (!$inside_code_snippet && preg_match($code_snippet_begin_pattern, $trimmed_line)) {
                $inside_code_snippet = true;
                // check if last line is a snippet intro line, if so, just remove it from result
                if (count($result) >= 1) {
                    $last_line = trim($result[count($result) - 1]);
                    if (preg_match($code_snippet_intro_pattern, $last_line))
                        array_pop($result);
                }
                continue;
            }
            if ($inside_code_snippet && preg_match($code_snippet_end_pattern, $trimmed_line)) {
                $inside_code_snippet = false;
                $result[] = $placeholder;
                continue;
            }
            if (!$inside_code_snippet)
                $result[] = $line;
        }
        if ($inside_code_snippet)  // the code snippet has no ending line
            $result[] = $placeholder;
        return $result;
    }

    /**
     * Omit code snippet section in a set of output sections.
     * @param array $sections
     * @param string $placeholder
     * @return array
     */
    static private function omit_code_snippet_section($sections, $placeholder = self::CODE_OMIT_PLACEHOLDER) {
        $result = [];
        foreach ($sections as $section) {
            if ($section->is_code_snippet) {
                $new_section = (object)[
                    'is_code_snippet' => true,
                    'is_code_omitted' => true,
                    'title' => null,
                    'contents' => [$placeholder]
                ];
                $result[] = $new_section;
            } else {
                $result[] = $section;
            }
        }
        return $result;
    }

    /**
     * Send a request to the AI helper server.
     * If error occurs, an exception will be thrown.
     * @param string $serverUrl
     * @param object $data
     * @return object Result object with fields {response, s_time}.
     */
    static public function send_request($serverUrl, $data) {
        // $data is a object to be converted into a JSON string, including the following fields:
        // {sysPrompt = null, questionBody = '', testCases = [], userCode, userQuestion = '', questionLanguage = 'python', omitCodeSnippet = false, token = null}

        // fill default values of $data
        $concrete_data = null;
        if (!isset($data))
            $concrete_data = self::DEFAULT_REQUEST_DATA_FIELDS;
        else {
            $concrete_data = clone $data;
            foreach (self::DEFAULT_REQUEST_DATA_FIELDS as $key => $value) {
                if (!isset($data->$key))
                    $concrete_data->$key = $value;
            }
        }

        // organize the test cases
        $testCaseLines = [];
        if (isset($data->testCases)) {
            for ($i = 0, $len = count($concrete_data->testCases); $i < $len; ++$i) {
                $testcase = $concrete_data->testCases[$i];
                $testcase_str = implode("\n", [
                    "### Sample_Input_$i",
                    $testcase->stdin,
                    "### Sample_Output_$i",
                    $testcase->expected
                ]);
                $testCaseLines[] = $testcase_str;
            }
        }
        $testCaseLines_str = implode("\n", $testCaseLines);

        // organize the user input data
        $user_prompt = <<<EOF
$concrete_data->userQuestion
## 题目：
$concrete_data->questionBody
## 样例：
```$concrete_data->questionLanguage
$testCaseLines_str
```
## 提供的代码：
```$concrete_data->questionLanguage
$concrete_data->userCode```
EOF;

        // send data by curl
        $headers = ["Content-type:application/json;charset='utf-8'", "Accept:application/json"];
        if ($concrete_data->token)
            $headers[] = "Authorization: Bearer " . $concrete_data->token;

        $req_data = new stdClass();
        $req_data->system_prompt = $concrete_data->sysPrompt;
        $req_data->user_prompt = $user_prompt;

        [$returncode, $responsebody] = HttpUtils::request($serverUrl, HttpUtils::HTTP_POST, $req_data, $headers);

        if ($returncode < 0) {  // request failed
            throw new Exception("Error: request failed with return code $returncode");
        } else {   // success
            $response_obj = json_decode($responsebody);
            if (isset($response_obj->error)) {   // server returned error
                throw new Exception(get_string('err_code_helper_server_returns_error', 'qtype_coderunnerex',
                    [ 'error_type' => $response_obj->error->type, 'error_msg' => $response_obj->error->msg]));
            } else {
                $response_time_str = $response_obj->time;
                $response_str = implode('', $response_obj->response);

                // erase on tailing blanks of lines (but preserve most of the leadings, since they may be indent of program code)
                $lines = explode("\n", $response_str);
                $trimmed_lines = [];
                foreach ($lines as $line) {
                    $trimmed_lines[] = rtrim(ltrim($line, "\n\r\v\0"));
                }

                $response_str = implode("\n", $trimmed_lines);

                $result = new stdClass();
                $result->response = $response_str;
                $result->s_time = $response_time_str;
                return $result;
            }
        }
    }

    static protected function strip_html_tags($content) {
        // TODO: unimplemented yet
        return $content;
    }

    /**
     * Get question text, question language and question testcases from a question attempt.
     * @param object $question
     * @param object $question_attempt
     * @return object
     */
    static protected function extract_question_attempt_info($question, $question_attempt) {
        $result = (object)[
            'questionLanguage' => $question->language,
            'questionBody' => $question->questiontext,
            'testCases' => $question->testcases
        ];
        return $result;
    }

    /**
     * Get or generate quiz attempt, question attempt, course module, context module objects from given ids and slot.
     * @param int $question_attempt_id
     * @param int $question_attempt_step_id
     * @param int $question_usage_id
     * @param int $slot
     * @return array
     */
    static protected function retrieve_question_attempt_related_objects($question_attempt_id, $question_attempt_step_id, $question_usage_id, $slot) {
        $quiz_attempt_id = qtype_coderunnerex_db_util::get_quiz_attemptid_by_uniqueid($question_usage_id);
        if (isset($question_usage_id) && isset($slot) && isset($quiz_attempt_id)) {
            // in normal quiz
            $quiz_attempt = quiz_create_attempt_handling_errors($quiz_attempt_id, null);
            $question_attempt = $quiz_attempt->get_question_attempt($slot);
            $course_module = $quiz_attempt->get_cm();    // actually a course module
            $context_module = context_module::instance($course_module->id);

            return [
                'quiz_attempt' => $quiz_attempt,
                'question_attempt' => $question_attempt,
                'course_module' => $course_module,
                'context_module' => $context_module
            ];
        }
        else if (isset($question_usage_id) && isset($slot)) {
            // not in a quiz, maybe a question preview of teacher?
            // just create question attempt directly
            $activity = question_engine::load_questions_usage_by_activity($question_usage_id);
            $question_attempt = $activity->get_question_attempt($slot);
            $context_module = $activity->get_owning_context();  // actually a user module

            return [
                'question_attempt' => $question_attempt,
                'context_module' => $context_module
            ];
        }
        else
            return [];
    }

    /**
     * Check if current logged user has the privilege to evoke the code helper and ask questions.
     * @param object $question_attempt
     * @param objct $quiz_attempt
     * @param object $context_module
     * @return bool
     */
    static protected function check_usage_privileges($question_attempt, $quiz_attempt, $context_module) {
        global $USER;
        if (!empty($quiz_attempt)) { // normal quiz, check if has the privilege to attempt this quiz and is the same user
            $result = has_capability('mod/quiz:attempt', $context_module);
            if ($result) {
                // check if the quiz->userid is same to current login user
                $user_id = $quiz_attempt->get_userid();
                if ($user_id !== $USER->id) {
                    $result = false;
                }
            }
            return $result;
        }
        else {   // no quiz, perhaps a question preview? check if user can edit this question
            return has_capability('moodle/question:usemine', $context_module);
        }
    }

    /**
     * Check if current logged user has the privilege to review the usage history in code helper (e.g. teacher reviews submitted quiz from students).
     * @param object $question_attempt
     * @param object $quiz_attempt
     * @param object $context_module
     * @return bool
     */
    static protected function check_review_privileges($question_attempt, $quiz_attempt, $context_module) {
        if (!empty($quiz_attempt)) { // normal quiz, check if has the privilege to attempt this quiz and is the same user
            $result = has_capability('mod/quiz:attempt', $context_module);
            if ($result) {
                global $USER;
                $user_id = $quiz_attempt->get_userid(); // check if the quiz->userid is same to current login user
                if ($user_id !== $USER->id)
                    $result = has_capability('mod/quiz:manage', $context_module);  // if user is the quiz manage, he can see the history in quiz review page
            }
            return $result;
        }
        else    // no quiz, perhaps a question preview? check if user can edit this question
            return has_capability('moodle/question:usemine', $context_module);
    }

    /**
     * Get hash value of a code helper request.
     * This hash value is used to cache the response in database.
     * @param int $question_id
     * @param object $req_data
     * @return string
     */
    static protected function get_hash_of_request($question_id, $req_data) {
        $src_obj = (object)[
            'question_id' => $question_id,
            'user_code' => $req_data->userCode,
            'user_question' => $req_data->userQuestion,
            'omit_code_snippet' => $req_data->omitCodeSnippet
        ];
        $str = json_encode($src_obj);
        $result = hash('sha256', $str);
        return $result;
    }

    /**
     * Store a code helper request to database.
     * @param string $data_hash
     * @param int $question_id
     * @param int $question_attempt_id
     * @param int $question_attempt_step_id
     * @param int $user_id
     * @param object $req_data
     * @param object $response_result
     */
    static protected function save_request_on_question_attempt_to_db($data_hash, $question_id, $question_attempt_id, $question_attempt_step_id, $user_id, $req_data, $response_result) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        try {
            // insert attempt record
            $record = (object)[
                'questionattemptid' => $question_attempt_id,
                'questionattemptstepid' => $question_attempt_step_id,
                'userid' => $user_id,
                'reqhash' => $data_hash,
                'timecreated' => time(),
            ];
            $stepid = $DB->insert_record('question_crex_chelp_steps', $record);

            // then the detailed key-value data
            //   options
            $step_options = (object)[
                'omitCodeSnippet' => $req_data->omitCodeSnippet
            ];
            qtype_coderunnerex_db_util::set_key_value_to_table('question_crex_chelp_stepdata', 'stepid', $stepid, 'options', json_encode($step_options));
            //   user code
            qtype_coderunnerex_db_util::set_key_value_to_table('question_crex_chelp_stepdata', 'stepid', $stepid, 'user_code', $req_data->userCode);
            //   user question
            qtype_coderunnerex_db_util::set_key_value_to_table('question_crex_chelp_stepdata', 'stepid', $stepid, 'user_question', $req_data->userQuestion);
            //   server response text
            qtype_coderunnerex_db_util::set_key_value_to_table('question_crex_chelp_stepdata', 'stepid', $stepid, 'response', $response_result->response);

            $transaction->allow_commit();

            return $stepid;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            return null;
        }
    }

    /**
     * Retrieve code helper request data from database.
     * @param string $data_hash
     * @param object $req_data
     * @param int $question_id
     * @param int $question_attempt_id
     * @return array
     */
    static protected function get_request_details_from_db($data_hash, $req_data, $question_id = null, $question_attempt_id = null) {
        global $DB;
        $records = $DB->get_records('question_crex_chelp_steps', ['reqhash' => $data_hash]);

        $matched_record = null;
        // ensure the question id match
        foreach ($records as $id => $record) {
            $matched_record = $record;
            if (isset($question_attempt_id) && $record->questionattemptid != $question_attempt_id) {
                $matched_record = null;
                continue;
            }
            if (isset($question_id)) {
                $question_attempt_record = $DB->get_record('question_attempts', ['id' => $record->questionattemptid]);
                if ($question_attempt_record->questionid != $question_id) {
                    $matched_record = null;
                    continue;
                }
            }

            if (!empty($matched_record))
                break;
        }

        if ($matched_record) {
            $result = qtype_coderunnerex_db_util::get_all_key_values_from_table('question_crex_chelp_stepdata', 'stepid', $matched_record->id);
        } else {
            $result = [];
        }
        return $result;
    }

    /**
     * Do a code helper request on a question attempt.
     * @param string $serverUrl Url of AI helper server.
     * @param object $data The detailed data send to server, including {question_attempt_id, question_attempt_step_id, question_usage_id, slot, user_code}  and so on.
     * @param bool $check_privileges Whether check the user privilege before sending the request.
     * @param bool $save_to_db Whether stores the request and response data to database.
     * @param bool $structurized_output Whether organize the response data into sections.
     * @return object Response data.
     */
    static public function request_on_question_attempt($serverUrl, $data, $check_privileges = true, $save_to_db = false, $structurized_output = true) {
        global $CFG, $DB;

        $cm_id = $data->cm_id;  // not used
        $question_attempt_id = $data->question_attempt_id;
        $question_attempt_step_id = $data->question_attempt_step_id;
        $question_usage_id = $data->question_usage_id;
        $quiz_attempt_id = qtype_coderunnerex_db_util::get_quiz_attemptid_by_uniqueid($question_usage_id);

        $slot = $data->slot;
        $user_code = $data->user_code;

        $user_asked_question = null;
        $predefined_questions = get_config('qtype_coderunnerex', 'code_helper_predefined_asks');
        $predefined_questions = explode("\n", $predefined_questions);
        $enable_custom_question = !get_config('qtype_coderunnerex', 'code_helper_disable_custom_asks');
        $simple_assist_mode = boolval(get_config('qtype_coderunnerex', 'code_helper_simple_assistant_mode'));
        if ($simple_assist_mode) {
            $user_asked_question = $predefined_questions[0];
        }
        else {
            if ($enable_custom_question)
                $user_asked_question = $data->user_asked_question;
            else {
                $user_asked_question_index = $data->user_asked_question_index;
                if (isset($user_asked_question_index)) {
                    $user_asked_question = $predefined_questions[$user_asked_question_index];
                }
            }
        }

        $token = object_property_exists($data, 'token')? $data->token: null;
        if (empty($token))
            $token = get_config('qtype_coderunnerex', 'code_helper_server_token');
        if (empty($serverUrl))
            $serverUrl = get_config('qtype_coderunnerex', 'code_helper_server_url');

        [
            'quiz_attempt' => $quiz_attempt,
            'question_attempt' => $question_attempt,
            'course_module' => $course_module,
            'context_module' => $context_module
        ] = self::retrieve_question_attempt_related_objects($question_attempt_id, $question_attempt_step_id, $question_usage_id, $slot);

        if (empty($question_attempt)) {
            throw new Exception(get_string('err_code_helper_can_not_ini_question_attempt_from_params', 'qtype_coderunnerex'));
        }
        else {  // if (!empty($question_attempt)) {   // extract information from question attempt
            if ($question_attempt->get_database_id() != $question_attempt_id) {  // get_database_id() may returns a string, use != to compare to int
                // id not match, throw error
                throw new Exception(get_string('err_question_attempid_not_match', 'qtype_coderunnerex'));
            }

            if ($check_privileges && !self::check_usage_privileges($question_attempt, $quiz_attempt, $context_module))
                throw new Exception(get_string('err_code_helper_user_no_privilege', 'qtype_coderunnerex'));

            $question = $question_attempt->get_question(false);
            $question_id = $question_attempt->get_question_id();
            $in_normal_quiz = !empty($quiz_attempt);
            $user_id = $in_normal_quiz? $quiz_attempt->get_userid(): null;

            if (!($question instanceof qtype_coderunnerex_question))
                throw new Exception(get_string('err_not_coderunnerex_question', 'qtype_coderunnerex'));

            $remaining_usage_count = null;
            if ($in_normal_quiz) {
                // in a normal quiz, we need to check the remaining usage count of this attempt
                $remaining_usage_count = $question->get_remaining_code_helper_usage_count_on_attempt($question_attempt, $quiz_attempt_id);
                if (isset($remaining_usage_count) && $remaining_usage_count <= 0) {
                    throw new Exception(get_string('err_code_helper_usage_count_exceeded', 'qtype_coderunnerex'));
                }
            }

            // request data to post to server
            $req_data = self::extract_question_attempt_info($question, $question_attempt);
            // add user asked question and additional info
            $req_data->userQuestion = $user_asked_question;
            $req_data->userCode = $user_code;
            $req_data->omitCodeSnippet = $question->is_code_helper_code_snippet_omitted();
            $req_data->token = $token;

            $data_hash = self::get_hash_of_request($question_id, /*$user_id,*/ $req_data);

            // check if this request has been done before, if so, we get response directly from database
            $db_cached_result = self::get_request_details_from_db($data_hash, $req_data);
            if (!empty($db_cached_result) && array_key_exists('response', $db_cached_result)) {
                $result = (object)[
                    'response' => $db_cached_result['response'],
                    'fromCache' => true,
                    'hash' => $data_hash
                ];
                // TODO: if we get response from DB, whether should this request be save to db again?
            } else {
                $result = self::send_request($serverUrl, $req_data);
            }

            // no exception in send_request, we got the normal response from server, saving to db
            if ($save_to_db && $in_normal_quiz) {
                $db_saved_id = self::save_request_on_question_attempt_to_db($data_hash, $question_attempt->get_question_id(), $question_attempt->get_database_id(), $question_attempt_step_id, $user_id, $req_data, $result);
                if ($db_saved_id)
                    $result->id = $db_saved_id;   // returns step id in response, enable the client to rate this response
            }

            if (isset($remaining_usage_count)) {  // returns new remaining usage count to client
                $result->remainingUsageCount = $question->get_remaining_code_helper_usage_count_on_attempt($question_attempt, $quiz_attempt_id);
            }

            if ($structurized_output) {
//                $result->original_response = $result->response;  // debug
                $result->response = self::convert_ai_response_str_to_structure($result->response);
                if ($req_data->omitCodeSnippet) {
                    $result->response = self::omit_code_snippet_section($result->response);
                }
            } else {
                if ($req_data->omitCodeSnippet) {
                    $result->response = self::omit_code_snippet($result->response);
                }
            }

            return $result;
        }
    }

    /**
     * Convert the response string from AI server to sections.
     * @param string $response_str
     * @param string $code_omit_placeholder
     * @return array
     */
    static protected function convert_ai_response_str_to_structure($response_str, $code_omit_placeholder = self::CODE_OMIT_PLACEHOLDER) {
        $lines = explode("\n", $response_str);

        $curr_section = null;
        $sections = array();

        $title_pattern = '/^##(.+)$/';
        $code_snippet_intro_pattern = '/^##.*代码.*$/';
        $code_snippet_begin_pattern = '/^```([\S]+)$/';
        $code_snippet_end_pattern = '/^```$/';

        $last_line_role = null;

        $push_prev_section = function() use (&$sections, &$curr_section) {
            if (!empty($curr_section))
                $sections[] = $curr_section;
        };

        foreach($lines as $line) {
            $line = rtrim($line);
            $trimmed_line = trim($line);
            if (preg_match($title_pattern, $trimmed_line, $matches)) {
                // we meet a new title, need to put the prev section to list and create a new section
                $push_prev_section();
                $curr_section = (object)[
                    'title' => trim($matches[1]),
                    'contents' => []
                ];
                if (preg_match($code_snippet_intro_pattern, $line)) {
                    $curr_section->is_code_snippet = true;
                }
                $last_line_role = 'title';
            }
            else if (preg_match($code_snippet_begin_pattern, $trimmed_line, $matches)) {
                // sometimes the AI will not output the code title ##, but directly introduce a code snippet
                if (empty($curr_section) || !$curr_section->is_code_snippet) {
                    // we need to create a new section
                    $push_prev_section();
                    $curr_section = (object)[
                        'title' => null,
                        'contents' => [],
                        'is_code_snippet' => true,
                        'code_language' => $matches[1]
                    ];
                } else {
                    if ($last_line_role == 'title')
                        $curr_section->code_language = $matches[1];
                }
            } else if (preg_match($code_snippet_end_pattern, $trimmed_line)) {
                // means the end of a code snippet section. the following lines should be a new section
                $push_prev_section();
                $curr_section = null;
            }
            else {
                // should be section content, but if no title, we should create a default section
                if (empty($curr_section))
                    $curr_section = (object)[
                        'title' => null,
                        'contents' => []
                    ];
                $curr_section->contents[] = $line;
                $last_line_role = 'content';
            }
        }
        $push_prev_section();

        return $sections;
    }

    /**
     * Get code helper request history data for a question attempt.
     * @param string $question_usage_id
     * @param int $slot
     * @param int $question_attempt_id
     * @param int $question_attempt_step_id
     * @param bool $check_privileges
     * @param int $limitfrom
     * @param int $limitnum
     * @return array
     */
    static public function retrieve_history_on_question_attempt($question_usage_id, $slot, $question_attempt_id, $question_attempt_step_id = null, $check_privileges = true, $limitfrom = 0, $limitnum = 0) {
        [
            'quiz_attempt' => $quiz_attempt,
            'question_attempt' => $question_attempt,
            'course_module' => $course_module,
            'context_module' => $context_module
        ] = self::retrieve_question_attempt_related_objects($question_attempt_id, $question_attempt_step_id, $question_usage_id, $slot);

        if (empty($question_attempt)) {
            throw new Exception(get_string('err_code_helper_can_not_ini_question_attempt_from_params', 'qtype_coderunnerex'));
        }
        else {
            if ($question_attempt->get_database_id() != $question_attempt_id) {  // get_database_id() may returns a string, use != to compare to int
                // id not match, throw error
                throw new Exception(get_string('err_question_attempid_not_match', 'qtype_coderunnerex'));
            }

            if ($check_privileges && !self::check_review_privileges($question_attempt, $quiz_attempt, $context_module))
                throw new Exception(get_string('err_code_helper_user_no_privilege', 'qtype_coderunnerex'));

            $query_condition = null;
            if (isset($question_attempt_step_id)) {
                // only retrieve history of current question attempt step
                $query_condition = [
                    'questionattemptid' => $question_attempt_id,
                    'questionattemptstepid' => $question_attempt_step_id
                ];
            } else {
                // no step id set, retrieve history of all attempt steps
                // e.g. if in review mode, we should retrieve history of all question attempt steps
                $query_condition = [
                    'questionattemptid' => $question_attempt_id
                ];
            }

            global $DB;
            // in time rev order, the newest record will be on top
            $records = $DB->get_records('question_crex_chelp_steps', $query_condition, 'timecreated DESC', 'id, timecreated', $limitfrom, $limitnum);

            // retrieved detailed data of records and create return results
            $result = [];
            foreach ($records as $record) {
                $id = $record->id;
                $details = qtype_coderunnerex_db_util::get_all_key_values_from_table('question_crex_chelp_stepdata', 'stepid', $id);
                $details['id'] = $id;
                $details['timestamp'] = intval($record->timecreated);
                if (array_key_exists('user_rate', $details)) {
                    $details['user_rate'] = intval($details['user_rate']);
                }
                $result[] = (object)$details;
            }

            return $result;
        }
    }

    static public function get_rating_of_ai_response_on_question_attempt($response_step_id, $question_usage_id, $slot, $check_privileges = true) {
        return self::get_or_set_rating_of_ai_response_on_question_attempt('get', null, $response_step_id, $question_usage_id, $slot, $check_privileges);
    }
    static public function set_rating_of_ai_response_on_question_attempt($rate, $response_step_id, $question_usage_id, $slot, $check_privileges = true) {
        return self::get_or_set_rating_of_ai_response_on_question_attempt('set', $rate, $response_step_id, $question_usage_id, $slot, $check_privileges);
    }

    /**
     * Get or set the user rating of a code helper response.
     * @param string $mode 'get' or 'set'.
     * @param int $rate Rating value.
     * @param int $response_step_id
     * @param int $question_usage_id
     * @param int $slot
     * @param bool $check_privileges
     * @return int If mode is 'get', returns the rating value.
     */
    static public function get_or_set_rating_of_ai_response_on_question_attempt($mode, $rate, $response_step_id, $question_usage_id, $slot, $check_privileges = true) {
        global $DB;
        $step_record = $DB->get_record('question_crex_chelp_steps', ['id' => $response_step_id]);

        if (empty($step_record))
            throw new Exception(get_string('err_code_helper_record_not_found', 'qtype_coderunnerex'));

        $question_attempt_id = $step_record->questionattemptid;
        $question_attempt_step_id = $step_record->questionattemptstepid;

        [
            'quiz_attempt' => $quiz_attempt,
            'question_attempt' => $question_attempt,
            'course_module' => $course_module,
            'context_module' => $context_module
        ] = self::retrieve_question_attempt_related_objects($question_attempt_id, $question_attempt_step_id, $question_usage_id, $slot);

        if (empty($question_attempt)) {
            throw new Exception(get_string('err_code_helper_can_not_ini_question_attempt_from_params', 'qtype_coderunnerex'));
        } else {
            if ($check_privileges) {
                $passed = ($mode == 'set')?
                    self::check_usage_privileges($question_attempt, $quiz_attempt, $context_module):
                    self::check_review_privileges($question_attempt, $quiz_attempt, $context_module);
                if (!passed)
                    throw new Exception(get_string('err_code_helper_user_no_privilege', 'qtype_coderunnerex'));
            }

            if ($mode == 'set') {
                $old_rate = qtype_coderunnerex_db_util::get_key_value_from_table('question_crex_chelp_stepdata', 'stepid', $response_step_id, 'user_rating', null);
                if ($old_rate != null)
                    throw new Exception(get_string('err_user_already_rated', 'qtype_coderunnerex'));
                qtype_coderunnerex_db_util::set_key_value_to_table('question_crex_chelp_stepdata', 'stepid', $response_step_id, 'user_rating', $rate);
                return $rate;
            } else {   // get
                $rate = qtype_coderunnerex_db_util::get_key_value_from_table('question_crex_chelp_stepdata', 'stepid', $response_step_id, 'user_rating');
                $rate = is_number($rate)? intval($rate): null;
                return $rate;
            }
        }
    }
}