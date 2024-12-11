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
 * Entrance point for handling AI code helper requests.
 */

require_once(__DIR__ . '/../../../../config.php');

require_once($CFG->dirroot . '/question/type/coderunnerex/lib/codehelper.php');

function handleRequest() {
    // input params
    // sysPrompt = null, questionBody = '', testCases = [], userCode, userQuestion = '', questionLanguage = 'python', omitCodeSnippet = false, token = null
    $question_attempt_id = required_param('question_attempt', PARAM_INT);  // question attempt id
    $question_attempt_step_id = required_param('question_attempt_step', PARAM_INT);  // question attempt step id
    $question_usage_id  = required_param('question_usage', PARAM_INT);  // question usage id
    $slot = required_param('slot', PARAM_INT);
    $cm_id = optional_param('cm', null, PARAM_INT);  // context module id
    $do_retrieve_history = optional_param('history', false, PARAM_BOOL);

    if ($do_retrieve_history) {
        try {
            // TODO: currently retrieve all history records of this attempt
            $history = AiHelperUtils::retrieve_history_on_question_attempt($question_usage_id, $slot, $question_attempt_id, null, true);
            return (object)['success' => true, 'result' => $history, 'message' => ''];
        } catch (\Exception $e) {
            return (object)['success' => false, 'message' => $e->getMessage()];
        }
    }
    else {
        $user_asked_question = optional_param('user_question', '', PARAM_TEXT);
        $user_asked_question_index = optional_param('user_question_index', null, PARAM_INT);
        $user_code = optional_param('user_code', '', PARAM_TEXT);

        $data = (object)[
            'cm_id' => $cm_id,
            'question_attempt_id' => $question_attempt_id,
            'question_attempt_step_id' => $question_attempt_step_id,
            'question_usage_id' => $question_usage_id,
            'slot' => $slot,
            'user_asked_question' => $user_asked_question,
            'user_asked_question_index' => $user_asked_question_index,
            'user_code' => $user_code
        ];

        try {
            $result = AiHelperUtils::request_on_question_attempt('', $data, true, true);
            return (object)['success' => true, 'result' => $result, 'message' => ''];
        } catch (\Exception $e) {
            return (object)['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// return JSON format data
$result_obj = handleRequest();
header('Content-Type:application/json; charset=utf-8');
exit(json_encode($result_obj));



