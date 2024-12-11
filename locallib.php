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

defined('MOODLE_INTERNAL') || die();


/**
 * Helper class for handling boolean options in question type.
 * If the setting of question instance is not set (or set to inherited), the global setting of question type will be used.
 */
class qtype_coderunnerex_inheritable_bool_setting {
    const INHERITED = 0;
    const TRUE = 1;
    const FALSE = -1;

    static public function get_value($local_value, $parent_value) {
        if (empty($local_value))
            return $parent_value;
        else
            return ($local_value > 0) ? true : (($local_value < 0) ? false : $parent_value);
    }

    static public function from_str($str) {
        $result = !empty($str)? intval($str): qtype_coderunnerex_inheritable_bool_setting::INHERITED;
        return $result;
    }
    static public function to_str($value) {
        return $value;
    }
}

/**
 * Helper class for handling int options in question type.
 * If the setting of question instance is not set (or set to inherited), the global setting of question type will be used.
 */
class qtype_coderunnerex_inheritable_int_setting {
    const INHERITED = null;

    static public function get_value($local_value, $parent_value) {
        if (empty($local_value))
            return $parent_value;
        else
            return $local_value;
    }

    static public function from_str($str) {
        $result = !empty($str)? intval($str): qtype_coderunnerex_inheritable_int_setting::INHERITED;
        return $result;
    }
    static public function to_str($value) {
        return $value;
    }
}

class qtype_coderrunerex_codehelper_options {
    public $enabled = qtype_coderunnerex_inheritable_bool_setting::INHERITED;
    public $max_usage_count_per_question_attempt = qtype_coderunnerex_inheritable_int_setting::INHERITED;
    public $omit_code_snippet = qtype_coderunnerex_inheritable_bool_setting::INHERITED;
}
