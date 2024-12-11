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
 * Entrance for handling the user rating of AI helper responses.
 */

require_once(__DIR__ . '/../../../../config.php');

require_once($CFG->dirroot . '/question/type/coderunnerex/lib/codehelper.php');

function handleRequest() {
    $response_step_id  = required_param('id', PARAM_INT);  // ai response step id
    $question_usage_id  = required_param('question_usage', PARAM_INT);  // question usage id
    $slot = required_param('slot', PARAM_INT);
    $rate = optional_param('value', null, PARAM_INT);
    $mode = optional_param('mode', 'get', PARAM_TEXT);

    try {
        $rate = intval($rate);
        if (!$rate)
            $rate = null;

        $new_rate = AiHelperUtils::get_or_set_rating_of_ai_response_on_question_attempt($mode, $rate, $response_step_id, $question_usage_id, $slot, true);
        return (object)['success' => true, 'result' => $new_rate, 'message' => ''];
    } catch (\Exception $e) {
        return (object)['success' => false, 'message' => $e->getMessage()];
    }
}

// return JSON format data
$result_obj = handleRequest();
header('Content-Type:application/json; charset=utf-8');
exit(json_encode($result_obj));

