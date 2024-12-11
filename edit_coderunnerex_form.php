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
 * Defines the editing form for the coderunnerex question type.
 *
 * @package   qtype_coderunnerex
 * @copyright Ginger Jiang, China Pharmerutical University.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use qtype_coderunner\constants;

require_once($CFG->dirroot . '/question/type/coderunner/edit_coderunner_form.php');
require_once($CFG->dirroot . '/question/type/coderunnerex/lib/classInvader.php');
require_once($CFG->dirroot . '/question/type/coderunnerex/lib/utils.php');
require_once($CFG->dirroot . '/question/type/coderunnerex/locallib.php');

/**
 * CodeRunner editing form definition.
 *
 * Note: In this class, most of the codes are directly copied from qtype_coderunner_edit_form.
 * Method make_questiontype_panel is modified to apply extra default setting values.
 * Some extra methods are also introduced to add new controls in the form.
 *
 * ( Since most of the methods are marked as private in qtype_coderunner_edit_form and can not be overridden,
 *   we have to use this dirty copy approach. )
 */
class qtype_coderunnerex_edit_form extends qtype_coderunner_edit_form {

    public function __construct($submiturl, $question, $category, $contexts, $formeditable = true) {
        parent::__construct($submiturl, $question, $category, $contexts, $formeditable);
        // apply inherited CodeRunner styles
        global $PAGE;
        qtype_coderunnerex_util::apply_inherited_cr_styles($PAGE);
    }


    // override the form definition method of qtype_coderunner_edit_form,
    // but this method originally uses too many private fields and methods, we have to hack it with Reflection.
    protected function definition() {
        /* Original code:
        global $PAGE;
        $mform = $this->_form;

        if (!empty($this->question->options->language)) {
            $this->lang = $this->acelang = $this->question->options->language;
        } else {
            $this->lang = $this->acelang = '';
        }
        if (!empty($this->question->options->acelang)) {
            $this->acelang = $this->question->options->acelang;
        }
        $this->make_error_div($mform);
        $this->make_questiontype_panel($mform);
        $this->make_questiontype_help_panel($mform);
        $this->make_customisation_panel($mform);
        $this->make_advanced_customisation_panel($mform);
        qtype_coderunner_util::load_ace();

        $PAGE->requires->js_call_amd('qtype_coderunner/textareas', 'setupAllTAs');
        $PAGE->requires->js_call_amd('qtype_coderunner/authorform', 'initEditForm');

        parent::definition($mform);  // The superclass adds the "General" stuff.
        */
        global $PAGE;
        $mform = $this->_form;

        $invader = invade_parent($this);

        if (!empty($this->question->options->language)) {
            $invader->lang = $invader->acelang = $this->question->options->language;
        } else {
            $invader->lang = $invader->acelang = '';
        }
        if (!empty($this->question->options->acelang)) {
            $invader->acelang = $this->question->options->acelang;
        }
        $invader->make_error_div($mform);
        $this->make_questiontype_panel($mform);
        $invader->make_questiontype_help_panel($mform);
        $invader->make_customisation_panel($mform);
        $invader->make_advanced_customisation_panel($mform);
        qtype_coderunner_util::load_ace();

        $PAGE->requires->js_call_amd('qtype_coderunner/textareas', 'setupAllTAs');
        $PAGE->requires->js_call_amd('qtype_coderunner/authorform', 'initEditForm');

        question_edit_form::definition($mform);
    }


    // we only modified the following methods:
    // Make some modifications to original question edit form, applying some default values in setting, and add new extra fields.
    private function make_questiontype_panel($mform) {
        ////////////////////////////// Original /////////////////////////////////////
        /// [, $types] = $this->get_languages_and_types();
        /////////////////////////////////////////////////////////////////////////////
        ////////////////////////////// Modified by Ginger ///////////////////////////
        /// Hack to call this private method
        [, $types] = invade_parent($this)->get_languages_and_types();
        ////////////////////////////// Modification End /////////////////////////////

        $hidemethod = method_exists($mform, 'hideIf') ? 'hideIf' : 'disabledIf';

        $mform->addElement('header', 'questiontypeheader', get_string('type_header', 'qtype_coderunner'));

        // Insert the (possible) bad question load message as a hidden field before broken question. JavaScript
        // will be used to show it if non-empty.
        $mform->addElement(
            'hidden',
            'badquestionload',
            '',
            ['id' => 'id_bad_question_load', 'class' => 'badquestionload']
        );
        $mform->setType('badquestionload', PARAM_RAW);

        // Insert the (possible) missing prototype message as a hidden field. JavaScript
        // will be used to show it if non-empty.
        $mform->addElement(
            'hidden',
            'brokenquestionmessage',
            '',
            ['id' => 'id_broken_question', 'class' => 'brokenquestionerror']
        );
        $mform->setType('brokenquestionmessage', PARAM_RAW);

        // The Question Type controls (a group with the question type and the warning, if it is one).
        $typeselectorelements = [];
        $expandedtypes = array_merge(['Undefined' => 'Undefined'], $types);
        ////////////////////////////// Original /////////////////////////////////////
        /// $typeselectorelements[] = $mform->createElement(
        //            'select',
        //            'coderunnertype',
        //            null,
        //            $expandedtypes
        //        );
        /////////////////////////////////////////////////////////////////////////////
        ////////////////////////////// Modified by Ginger ///////////////////////////
        /// apply default coderunner type setting
        $coderunner_type_selector = $mform->createElement(
            'select',
            'coderunnertype',
            null,
            $expandedtypes
        );
        $typeselectorelements[] = $coderunner_type_selector;
        $default_type = get_config('qtype_coderunnerex', 'default_coderunner_type');
        if (empty($default_type))
            $default_type = '';
        $mform->setDefault('coderunnertype', $default_type);
        // $coderunner_type_selector->setSelected($default_type);
        ///////////////////////////// Modification End ////////////////////////////

        $prototypelangstring = get_string('prototypeexists', 'qtype_coderunner');
        $typeselectorelements[] = $mform->createElement(
            'html',
            "<div id='id_isprototype' class='qtype_coderunner_prototype_message' hidden>"
            . "{$prototypelangstring}</div>"
        );
        $mform->addElement(
            'group',
            'coderunner_type_group',
            get_string('coderunnertype', 'qtype_coderunner'),
            $typeselectorelements,
            null,
            false
        );
        $mform->addHelpButton('coderunner_type_group', 'coderunnertype', 'qtype_coderunner');

        // Customisation checkboxes.
        $typeselectorcheckboxes = [];
        $typeselectorcheckboxes[] = $mform->createElement(
            'advcheckbox',
            'customise',
            null,
            get_string('customise', 'qtype_coderunner')
        );
        $typeselectorcheckboxes[] = $mform->createElement(
            'advcheckbox',
            'showsource',
            null,
            get_string('showsource', 'qtype_coderunner')
        );
        $mform->setDefault('showsource', false);
        $mform->addElement(
            'group',
            'coderunner_type_checkboxes',
            get_string('questioncheckboxes', 'qtype_coderunner'),
            $typeselectorcheckboxes,
            null,
            false
        );
        $mform->addHelpButton('coderunner_type_checkboxes', 'questioncheckboxes', 'qtype_coderunner');

        // Answerbox controls.
        $answerboxelements = [];
        $answerboxelements[] = $mform->createElement(
            'text',
            'answerboxlines',
            get_string('answerboxlines', 'qtype_coderunner'),
            ['size' => 3, 'class' => 'coderunner_answerbox_size']
        );
        $mform->setType('answerboxlines', PARAM_INT);
        ////////////////////////////// Original /////////////////////////////////////
        // $mform->setDefault('answerboxlines', self::DEFAULT_NUM_ROWS);
        ////////////////////////////// Modified by Ginger ///////////////////////////
        /// apply default code editor row count setting
        $def_editor_rows = get_config('qtype_coderunnerex', 'default_code_editor_row_count');

        if (is_numeric($def_editor_rows))
            $def_editor_rows = intval($def_editor_rows);
        else
            $def_editor_rows = self::DEFAULT_NUM_ROWS;

        $mform->setDefault('answerboxlines', $def_editor_rows);
        ////////////////////////////// Modification End /////////////////////////////
        ///
        $mform->addElement(
            'group',
            'answerbox_group',
            get_string('answerbox_group', 'qtype_coderunner'),
            $answerboxelements,
            null,
            false
        );
        $mform->addHelpButton('answerbox_group', 'answerbox_group', 'qtype_coderunner');

        // Precheck control group (precheck + hide check).
        $precheckelements = [];
        $precheckvalues = [
            constants::PRECHECK_DISABLED => get_string('precheck_disabled', 'qtype_coderunner'),
            constants::PRECHECK_EMPTY    => get_string('precheck_empty', 'qtype_coderunner'),
            constants::PRECHECK_EXAMPLES => get_string('precheck_examples', 'qtype_coderunner'),
            constants::PRECHECK_SELECTED => get_string('precheck_selected', 'qtype_coderunner'),
            constants::PRECHECK_ALL      => get_string('precheck_all', 'qtype_coderunner'),
        ];
        $precheckelements[] = $mform->createElement(
            'select',
            'precheck',
            get_string('precheck', 'qtype_coderunner'),
            $precheckvalues
        );
        $precheckelements[] = $mform->createElement(
            'advcheckbox',
            'hidecheck',
            null,
            get_string('hidecheck', 'qtype_coderunner')
        );
        $mform->addElement(
            'group',
            'coderunner_precheck_group',
            get_string('submitbuttons', 'qtype_coderunner'),
            $precheckelements,
            null,
            false
        );
        $mform->addHelpButton('coderunner_precheck_group', 'precheck', 'qtype_coderunner');

        // Whether to show the 'Stop and read feedback' button.
        $giveupelements = [];
        $giveupvalues = [
            constants::GIVEUP_NEVER => get_string('giveup_never', 'qtype_coderunner'),
            constants::GIVEUP_AFTER_MAX_MARKS => get_string('giveup_aftermaxmarks', 'qtype_coderunner'),
            constants::GIVEUP_ALWAYS => get_string('giveup_always', 'qtype_coderunner'),
        ];

        $giveupelements[] = $mform->createElement('select', 'giveupallowed', null, $giveupvalues);
        $mform->addElement(
            'group',
            'coderunner_giveup_group',
            get_string('giveup', 'qtype_coderunner'),
            $giveupelements,
            null,
            false
        );
        $mform->addHelpButton('coderunner_giveup_group', 'giveup', 'qtype_coderunner');
        $mform->setDefault('giveupallowed', constants::GIVEUP_NEVER);

        // Feedback control (a group with only one element).
        $feedbackelements = [];
        $feedbackvalues = [
            constants::FEEDBACK_USE_QUIZ => get_string('feedback_quiz', 'qtype_coderunner'),
            constants::FEEDBACK_SHOW    => get_string('feedback_show', 'qtype_coderunner'),
            constants::FEEDBACK_HIDE => get_string('feedback_hide', 'qtype_coderunner'),
        ];

        $feedbackelements[] = $mform->createElement('select', 'displayfeedback', null, $feedbackvalues);
        $mform->addElement(
            'group',
            'coderunner_feedback_group',
            get_string('feedback', 'qtype_coderunner'),
            $feedbackelements,
            null,
            false
        );
        $mform->addHelpButton('coderunner_feedback_group', 'feedback', 'qtype_coderunner');
        ////////////////////////////// Original /////////////////////////////////////
        // $mform->setDefault('displayfeedback', constants::FEEDBACK_SHOW);
        ////////////////////////////// Modified by Ginger ///////////////////////////
        /// apply default feedback mode setting
        $def_feedback_mode = get_config('qtype_coderunnerex', 'default_feedback_mode');
        if (is_numeric($def_feedback_mode))
            $def_feedback_mode = intval($def_feedback_mode);
        else
            $def_feedback_mode = constants::FEEDBACK_SHOW;
        $mform->setDefault('displayfeedback', $def_feedback_mode);
        ////////////////////////////// Modification End /////////////////////////////
        $mform->setType('displayfeedback', PARAM_INT);

        // Marking controls.
        $markingelements = [];
        $markingelements[] = $mform->createElement(
            'advcheckbox',
            'allornothing',
            null,
            get_string('allornothing', 'qtype_coderunner')
        );
        $markingelements[] = $mform->CreateElement(
            'text',
            'penaltyregime',
            get_string('penaltyregimelabel', 'qtype_coderunner'),
            ['size' => 20]
        );
        $mform->addElement(
            'group',
            'markinggroup',
            get_string('markinggroup', 'qtype_coderunner'),
            $markingelements,
            null,
            false
        );

        ////////////////////////////// Original /////////////////////////////////////
        // $mform->setDefault('allornothing', true);
        ////////////////////////////// Modified by Ginger ///////////////////////////
        /// apply default feedback mode setting
        $def_allornothing = get_config('qtype_coderunnerex', 'default_allornothing');
//        var_dump($def_allornothing);
//        print_r(boolval($def_allornothing));
//        die();
        $mform->setDefault('allornothing', !!$def_allornothing);
        ////////////////////////////// Modification End /////////////////////////////
        $mform->setType('penaltyregime', PARAM_RAW);
        $mform->addHelpButton('markinggroup', 'markinggroup', 'qtype_coderunner');

        // Template params.
        $mform->addElement(
            'textarea',
            'templateparams',
            get_string('templateparams', 'qtype_coderunner'),
            ['rows' => self::TEMPLATE_PARAM_ROWS,
                'class' => 'edit_code',
                'data-lang' => '', // Don't syntax colour template params.
            ]
        );
        $mform->setType('templateparams', PARAM_RAW);
        $mform->addHelpButton('templateparams', 'templateparams', 'qtype_coderunner');

        // Twig controls.
        $twigelements = [];
        $twigelements[] = $mform->createElement(
            'advcheckbox',
            'hoisttemplateparams',
            null,
            get_string('hoisttemplateparams', 'qtype_coderunner')
        );
        $twigelements[] = $mform->createElement(
            'advcheckbox',
            'extractcodefromjson',
            null,
            get_string('extractcodefromjson', 'qtype_coderunner')
        );
        $twigelements[] = $mform->createElement(
            'advcheckbox',
            'twigall',
            null,
            get_string('twigall', 'qtype_coderunner')
        );
        $templateparamlangs = [
            'None' => 'None',
            'twig' => 'Twig',
            'python3' => 'Python3',
            'c' => 'C',
            'cpp' => 'C++',
            'java' => 'Java',
            'php' => 'php',
            'octave' => 'Octave',
            'pascal' => 'Pascal',
        ];
        $twigelements[] = $mform->createElement(
            'select',
            'templateparamslang',
            get_string('templateparamslang', 'qtype_coderunner'),
            $templateparamlangs
        );
        $twigelements[] = $mform->createElement(
            'advcheckbox',
            'templateparamsevalpertry',
            null,
            get_string('templateparamsevalpertry', 'qtype_coderunner')
        );
        $mform->addElement(
            'group',
            'twigcontrols',
            get_string('twigcontrols', 'qtype_coderunner'),
            $twigelements,
            null,
            false
        );
        $mform->setDefault('templateparamslang', 'None');
        $mform->setDefault('templateparamsevalpertry', false);
        $mform->setDefault('twigall', false);
        $mform->$hidemethod('templateparamsevalpertry', 'templateparamslang', 'eq', 'None');
        $mform->$hidemethod('templateparamsevalpertry', 'templateparamslang', 'eq', 'twig');
        $mform->setDefault('hoisttemplateparams', true);
        $mform->setDefault('extractcodefromjson', true);
        $mform->addHelpButton('twigcontrols', 'twigcontrols', 'qtype_coderunner');

        // UI parameters.
        $plugins = qtype_coderunner_ui_plugins::get_instance();
        $uielements = [];
        $uiparamedescriptionhtml = '<div class="ui_parameters_descr"></div>'; // JavaScript fills this.
        $uielements[] = $mform->createElement('html', $uiparamedescriptionhtml);
        $uielements[] = $mform->createElement(
            'textarea',
            'uiparameters',
            get_string('uiparameters', 'qtype_coderunner'),
            ['rows' => self::UI_PARAM_ROWS,
                'class' => 'edit_code',
                'data-lang' => '', // Don't syntax colour ui params.
            ]
        );
        $mform->setType('uiparameters', PARAM_RAW);

        $mform->addElement(
            'group',
            'uiparametergroup',
            get_string('uiparametergroup', 'qtype_coderunner'),
            $uielements,
            null,
            false
        );
        $mform->addHelpButton('uiparametergroup', 'uiparametergroup', 'qtype_coderunner');

        ////////////////////////////// Modified by Ginger ///////////////////////////
        /// add new controls about extra props introduced in CodeRunnerEx
        $this->add_question_extra_prop_controls($mform);
        ////////////////////////////// Modification End /////////////////////////////
    }

    ////////////////////////////// Modified by Ginger ///////////////////////////
    /// /// add new controls about extra props introduced in CodeRunnerEx
    protected function add_question_extra_prop_controls($mform) {
        $inheritable_bool_selector_items = array(
            qtype_coderunnerex_inheritable_bool_setting::INHERITED => get_string('default_setting', 'qtype_coderunnerex'),
            qtype_coderunnerex_inheritable_bool_setting::TRUE => get_string('bool_setting_true', 'qtype_coderunnerex'),
            qtype_coderunnerex_inheritable_bool_setting::FALSE => get_string('bool_setting_false', 'qtype_coderunnerex')
        );

        $mform->addElement('select', 'code_helper_enabled',
            get_string('code_helper_enabled', 'qtype_coderunnerex'),
            $inheritable_bool_selector_items
        );

        $mform->addElement('select', 'code_helper_omit_code_snippet',
            get_string('code_helper_omit_code_snippet', 'qtype_coderunnerex'),
            $inheritable_bool_selector_items
        );

        $mform->addElement('text', 'code_helper_max_usage_count_per_question_attempt',
            get_string('code_helper_max_usage_count_per_question_attempt', 'qtype_coderunnerex')
        );
        $mform->addHelpButton('code_helper_max_usage_count_per_question_attempt', 'code_helper_max_usage_count_per_question_attempt', 'qtype_coderunnerex');
    }

    public function data_preprocessing($question) {
        $result = parent::data_preprocessing($question);
        return $result;
    }

    public function set_data($question) {

        // important: must set extra fields before calling parent::set_data(), otherwise the $question->field will not be filled
        // and the form control will be empty

        $extra_cr_fields = question_bank::get_qtype($question->qtype)->get_crex_extra_option_props();
        if (is_array($extra_cr_fields) && !empty($question->options)) {
            foreach ($extra_cr_fields as $field) {
                if (property_exists($question->options, $field)) {
                    $question->$field = $question->options->$field;
                }
            }
        }
        parent::set_data($question);
    }

    public function get_data() {
        $fields = parent::get_data();
        if ($fields) {
            $fields->code_helper_enabled = qtype_coderunnerex_inheritable_bool_setting::from_str($fields->code_helper_enabled);
            $fields->code_helper_max_usage_count_per_question_attempt = qtype_coderunnerex_inheritable_int_setting::from_str($fields->code_helper_max_usage_count_per_question_attempt);
            $fields->code_helper_omit_code_snippet = qtype_coderunnerex_inheritable_bool_setting::from_str($fields->code_helper_omit_code_snippet);
        }
        return $fields;
    }

    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);

        if (!empty($data['code_helper_max_usage_count_per_question_attempt']) && !is_numeric($data['code_helper_max_usage_count_per_question_attempt'])) {
            $errors['code_helper_max_usage_count_per_question_attempt'] = get_string('err_integer_value_required', 'qtype_coderunnerex');
        }
        return $errors;
    }

    public function qtype() {
        return 'coderunnerex';
    }
    ////////////////////////////// Modification End /////////////////////////////
}
