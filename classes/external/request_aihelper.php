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

namespace qtype_coderunnerex\external;

require_once($CFG->dirroot . '/question/type/coderunnerex/lib/codehelper.php');

use AiHelperUtils;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;


class request_aihelper extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            new external_single_structure([
                '$question_attempt_id' => new external_value(PARAM_INT, 'id of current question attempt'),
                '$question_usage_id' => new external_value(PARAM_INT, 'id of current question usage (e.g., quiz)'),
                '$slot' => new external_value(PARAM_INT, 'question slot of current question usage'),
                '$user_asked_question' => new external_value(
                    PARAM_TEXT,
                    'Question asked by the user to AI'
                )
            ])
        ]);
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'success' => new external_value(PARAM_BOOL, 'true if successful'),
                'message' => new external_value(PARAM_TEXT, 'error message if unsuccessful'),
                'result' => new external_single_structure([
                    'response' => new external_value(PARAM_TEXT, 'AI response'),
                    's_time' => new external_value(PARAM_TEXT, 'AI response time string'),
                ])
            ])
        );
    }

    public static function execute($question_attempt_id, $question_usage_id, $slot, $user_asked_question) {

        $server_url = get_config('qtype_coderunnerex', 'code_helper_server_url');
        $data = (object)[
            'question_attempt_id' => $question_attempt_id,
            'question_usage_id' => $question_usage_id,
            'slot' => $slot,
            'user_asked_question' => $user_asked_question
        ];

        try {
            $result = AiHelperUtils::request_on_question_attempt($server_url, $data, true);
            return (object)['success' => true, 'result' => $result, 'message' => ''];
        } catch (\Exception $e) {
            return (object)['success' => false, 'message' => $e->getMessage()];
        }
    }
}