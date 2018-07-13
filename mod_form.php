<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The main workshop configuration form
 *
 * The UI mockup has been proposed in MDL-18688
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/dev/lib/formslib.php
 *
 * @package    mod_udmworkshop
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Module settings form for Workshop instances
 */
class mod_udmworkshop_mod_form extends moodleform_mod {

    /** @var object the course this instance is part of */
    protected $course = null;

    /**
     * Constructor
     */
    public function __construct($current, $section, $cm, $course) {
        $this->course = $course;
        parent::__construct($current, $section, $cm, $course);
    }

    /**
     * Defines the workshop instance configuration form
     *
     * @return void
     */
    public function definition() {
        global $CFG, $PAGE, $USER;

        $workshopconfig = get_config('workshop');
        $mform = $this->_form;

        // General --------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $sameuser = (isset($USER->realuser)) ? false : true;
        if ($sameuser) {
            user_preference_allow_ajax_update("workshop_form_showadvanced", PARAM_BOOL);
        }
        $displayadvancedsettings = get_user_preferences('workshop_form_showadvanced', null);
        $displayadvanced = optional_param('displayadvanced', null, PARAM_ALPHA);

        // Workshop name
        $label = get_string('workshopname', 'workshop');
        $mform->addElement('text', 'name', $label, array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Save advanced setting state.
        $mform->addElement('hidden', 'advancedsettingdisplayed', $displayadvancedsettings ? 1 : 0);
        $mform->setType('advancedsettingdisplayed', PARAM_INT);
        // Introduction
        $this->standard_intro_elements(get_string('introduction', 'workshop'));

        // Grading settings -----------------------------------------------------------
        $mform->addElement('header', 'gradingsettings', get_string('gradingsettings', 'workshop'));
        $mform->setExpanded('gradingsettings');

        $options = array();
        if ($this->current->instance && workshop::is_assessmenttype_disabled($this->current->id)) {
            $options = array('disabled' => 'true');
        }
        $radio = array();
        $radio[] = $mform->createElement('radio', 'assessmenttype', null, get_string('peerassessment', 'workshop'),
                \workshop::PEER_ASSESSMENT, $options);
        $radio[] = $mform->createElement('radio', 'assessmenttype', null, get_string('selfassessment', 'workshop'),
                \workshop::SELF_ASSESSMENT, $options);
        $radio[] = $mform->createElement('radio', 'assessmenttype', null, get_string('selfandpeerassessment', 'workshop'),
                \workshop::SELF_AND_PEER_ASSESSMENT, $options);
        $mform->addGroup($radio, 'assessmenttype', get_string('assessmenttype', 'workshop'), array('<br />'), false);
        $mform->addHelpButton('assessmenttype', 'assessmenttype', 'workshop');
        $mform->setType('assessmenttype', PARAM_INT);
        $mform->setDefault('assessmenttype', \workshop::PEER_ASSESSMENT);

        $label = get_string('strategy', 'workshop');
        $mform->addElement('select', 'strategy', $label, workshop::available_strategies_list());
        $mform->setDefault('strategy', $workshopconfig->strategy);
        $mform->addHelpButton('strategy', 'strategy', 'workshop');

        $grades = workshop::available_maxgrades_list();
        $gradecategories = grade_get_categories_menu($this->course->id);

        $label = get_string('submissiongrade', 'workshop');
        $mform->addGroup(array(
            $mform->createElement('select', 'grade', '', $grades),
            $mform->createElement('select', 'gradecategory', '', $gradecategories),
            ), 'submissiongradegroup', $label, ' ', false);
        $mform->setDefault('grade', $workshopconfig->grade);
        $mform->addHelpButton('submissiongradegroup', 'submissiongrade', 'workshop');

        $mform->addElement('text', 'submissiongradepass', get_string('gradetopasssubmission', 'workshop'));
        $mform->addHelpButton('submissiongradepass', 'gradepass', 'grades');
        $mform->setDefault('submissiongradepass', '');
        $mform->setType('submissiongradepass', PARAM_RAW);

        $label = get_string('gradinggrade', 'workshop');
        $mform->addGroup(array(
            $mform->createElement('select', 'gradinggrade', '', $grades),
            $mform->createElement('select', 'gradinggradecategory', '', $gradecategories),
            ), 'gradinggradegroup', $label, ' ', false);
        $mform->setDefault('gradinggrade', $workshopconfig->gradinggrade);
        $mform->addHelpButton('gradinggradegroup', 'gradinggrade', 'workshop');

        $mform->addElement('text', 'gradinggradepass', get_string('gradetopassgrading', 'workshop'));
        $mform->addHelpButton('gradinggradepass', 'gradepass', 'grades');
        $mform->setDefault('gradinggradepass', '');
        $mform->setType('gradinggradepass', PARAM_RAW);

        $options = array();
        for ($i = 5; $i >= 0; $i--) {
            $options[$i] = $i;
        }
        $label = get_string('gradedecimals', 'workshop');
        $mform->addElement('select', 'gradedecimals', $label, $options);
        $mform->setDefault('gradedecimals', $workshopconfig->gradedecimals);

        // Submission settings --------------------------------------------------------
        $mform->addElement('header', 'submissionsettings', get_string('submissionsettings', 'workshop'));

        $options = array();
        if ($this->current->instance && workshop::is_allowsubmission_disabled($this->current)) {
            $options = array('disabled' => 'true');
        }
        $label = get_string('allowsubmission', 'workshop');
        $mform->addElement('checkbox', 'allowsubmission', $label, ' ', $options);
        $mform->addHelpButton('allowsubmission', 'allowsubmission', 'workshop');

        $divsubmissioninfo = \html_writer::start_div('fitem submissioninfo');
        $label = get_string('instructauthors', 'workshop');
        $mform->addElement('html', $divsubmissioninfo);
        $mform->addElement('editor', 'instructauthorseditor', $label, null,
                            workshop::instruction_editors_options($this->context));
        $mform->addElement('html', \html_writer::end_div());

        $label = get_string('assessassoonsubmitted', 'workshop');
        $mform->addElement('html', $divsubmissioninfo);
        $mform->addElement('checkbox', 'assessassoonsubmitted', $label, ' ');
        $mform->addHelpButton('assessassoonsubmitted', 'assessassoonsubmitted', 'workshop');
        $mform->addElement('html', \html_writer::end_div());

        $options = array();
        for ($i = 7; $i >= 0; $i--) {
            $options[$i] = $i;
        }
        $label = get_string('nattachments', 'workshop');
        $mform->addElement('html', $divsubmissioninfo);
        $mform->addElement('select', 'nattachments', $label, $options);
        $mform->setDefault('nattachments', 1);
        $mform->addElement('html', \html_writer::end_div());

        $label = get_string('allowedfiletypesforsubmission', 'workshop');
        $mform->addElement('html', $divsubmissioninfo);
        $mform->addElement('text', 'submissionfiletypes', $label, array('maxlength' => 255, 'size' => 64));
        $mform->addHelpButton('submissionfiletypes', 'allowedfiletypesforsubmission', 'workshop');
        $mform->setType('submissionfiletypes', PARAM_TEXT);
        $mform->addRule('submissionfiletypes', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->disabledIf('submissionfiletypes', 'nattachments', 'eq', 0);
        $mform->addElement('html', \html_writer::end_div());

        $options = get_max_upload_sizes($CFG->maxbytes, $this->course->maxbytes, 0, $workshopconfig->maxbytes);
        $mform->addElement('html', $divsubmissioninfo);
        $mform->addElement('select', 'maxbytes', get_string('maxbytes', 'workshop'), $options);
        $mform->setDefault('maxbytes', $workshopconfig->maxbytes);
        $mform->disabledIf('maxbytes', 'nattachments', 'eq', 0);
        $mform->addElement('html', \html_writer::end_div());

        $label = get_string('latesubmissions', 'workshop');
        $mform->addElement('html', $divsubmissioninfo);
        $text = get_string('latesubmissions_desc', 'workshop');
        $mform->addElement('checkbox', 'latesubmissions', $label, $text);
        $mform->addHelpButton('latesubmissions', 'latesubmissions', 'workshop');
        $mform->addElement('html', \html_writer::end_div());

        // Assessment settings --------------------------------------------------------
        $mform->addElement('header', 'assessmentsettings', get_string('assessmentsettings', 'workshop'));
        $anonymitysettings = new \mod_udmworkshop\anonymity_settings($this->context);
        // Display appraisees name.
        $label = get_string('displayappraiseesname', 'workshop');
        $mform->addElement('checkbox', 'displayappraiseesname', $label);
        $mform->addHelpButton('displayappraiseesname', 'displayappraiseesname', 'workshop');
        $mform->setDefault('displayappraiseesname', $anonymitysettings->display_appraisees_name());
        // Display appraisers name.
        $label = get_string('displayappraisersname', 'workshop');
        $mform->addElement('checkbox', 'displayappraisersname', $label);
        $mform->addHelpButton('displayappraisersname', 'displayappraisersname', 'workshop');
        $mform->setDefault('displayappraisersname', $anonymitysettings->display_appraisers_name());

         // Do not display assess without submission if allow submission is false.
        $mform->addElement('html', $divsubmissioninfo);
        $label = get_string('assesswithoutsubmission', 'workshop');
        $mform->addElement('checkbox', 'assesswithoutsubmission', $label);
        $mform->addHelpButton('assesswithoutsubmission', 'assesswithoutsubmission', 'workshop');
        $mform->addElement('html', \html_writer::end_div());

        $label = get_string('instructreviewers', 'workshop');
        $mform->addElement('editor', 'instructreviewerseditor', $label, null,
                            workshop::instruction_editors_options($this->context));

        // Feedback -------------------------------------------------------------------
        $mform->addElement('header', 'feedbacksettings', get_string('feedbacksettings', 'workshop'));

        $mform->addElement('select', 'overallfeedbackmode', get_string('overallfeedbackmode', 'mod_udmworkshop'), array(
            0 => get_string('overallfeedbackmode_0', 'mod_udmworkshop'),
            1 => get_string('overallfeedbackmode_1', 'mod_udmworkshop'),
            2 => get_string('overallfeedbackmode_2', 'mod_udmworkshop')));
        $mform->addHelpButton('overallfeedbackmode', 'overallfeedbackmode', 'mod_udmworkshop');
        $mform->setDefault('overallfeedbackmode', 1);

        $options = array();
        for ($i = 7; $i >= 0; $i--) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'overallfeedbackfiles', get_string('overallfeedbackfiles', 'workshop'), $options);
        $mform->setDefault('overallfeedbackfiles', 0);
        $mform->disabledIf('overallfeedbackfiles', 'overallfeedbackmode', 'eq', 0);

        $label = get_string('allowedfiletypesforoverallfeedback', 'workshop');
        $mform->addElement('text', 'overallfeedbackfiletypes', $label, array('maxlength' => 255, 'size' => 64));
        $mform->addHelpButton('overallfeedbackfiletypes', 'allowedfiletypesforoverallfeedback', 'workshop');
        $mform->setType('overallfeedbackfiletypes', PARAM_TEXT);
        $mform->addRule('overallfeedbackfiletypes', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->disabledIf('overallfeedbackfiletypes', 'overallfeedbackfiles', 'eq', 0);

        $options = get_max_upload_sizes($CFG->maxbytes, $this->course->maxbytes);
        $mform->addElement('select', 'overallfeedbackmaxbytes', get_string('overallfeedbackmaxbytes', 'workshop'), $options);
        $mform->setDefault('overallfeedbackmaxbytes', $workshopconfig->maxbytes);
        $mform->disabledIf('overallfeedbackmaxbytes', 'overallfeedbackmode', 'eq', 0);
        $mform->disabledIf('overallfeedbackmaxbytes', 'overallfeedbackfiles', 'eq', 0);

        $label = get_string('conclusion', 'workshop');
        $mform->addElement('editor', 'conclusioneditor', $label, null,
                            workshop::instruction_editors_options($this->context));
        $mform->addHelpButton('conclusioneditor', 'conclusion', 'workshop');

        // Example submissions --------------------------------------------------------
        $mform->addElement('header', 'examplesubmissionssettings', get_string('examplesubmissions', 'workshop'));

        $label = get_string('useexamples', 'workshop');
        $text = get_string('useexamples_desc', 'workshop');
        $mform->addElement('checkbox', 'useexamples', $label, $text);
        $mform->addHelpButton('useexamples', 'useexamples', 'workshop');

        $label = get_string('examplesmode', 'workshop');
        $options = workshop::available_example_modes_list();
        $mform->addElement('select', 'examplesmode', $label, $options);
        $mform->setDefault('examplesmode', $workshopconfig->examplesmode);
        $mform->disabledIf('examplesmode', 'useexamples');

        // Availability ---------------------------------------------------------------
        $mform->addElement('header', 'accesscontrol', get_string('availability', 'core'));

        $label = get_string('submissionstart', 'workshop');
        $mform->addElement('html', $divsubmissioninfo);
        $mform->addElement('date_time_selector', 'submissionstart', $label, array('optional' => true));
        $mform->addElement('html', \html_writer::end_div());

        $label = get_string('submissionend', 'workshop');
        $mform->addElement('html', $divsubmissioninfo);
        $mform->addElement('date_time_selector', 'submissionend', $label, array('optional' => true));
        $mform->addElement('html', \html_writer::end_div());

        $label = get_string('submissionendswitch', 'mod_udmworkshop');
        $mform->addElement('html', $divsubmissioninfo);
        $mform->addElement('html',  \html_writer::start_div('phaseswitchassessmentinfo'));
        $mform->addElement('checkbox', 'phaseswitchassessment', $label);
        $mform->addHelpButton('phaseswitchassessment', 'submissionendswitch', 'mod_udmworkshop');
        $mform->addElement('html', \html_writer::end_div());

        $mform->addElement('html', \html_writer::end_div());

        $label = get_string('assessmentstart', 'workshop');
        $mform->addElement('date_time_selector', 'assessmentstart', $label, array('optional' => true));

        $label = get_string('assessmentend', 'workshop');
        $mform->addElement('date_time_selector', 'assessmentend', $label, array('optional' => true));

        $coursecontext = context_course::instance($this->course->id);
        plagiarism_get_form_elements_module($mform, $coursecontext, 'mod_udmworkshop');

        // Common module settings, Restrict availability, Activity completion etc. ----
        $features = array('groups' => true, 'groupings' => true,
                'outcomes' => true, 'gradecat' => false, 'idnumber' => false);

        $this->standard_coursemodule_elements();

        // Standard buttons, common to all modules ------------------------------------
        $this->add_action_buttons();
        $fieldsetarray = array('#id_gradingsettings', '#id_submissionsettings', '#id_assessmentsettings', '#id_feedbacksettings',
            '#id_examplesubmissionssettings', '#id_accesscontrol');
        $scrollto = null;
        if ($displayadvanced) {
            $scrollto = "#id_" . $displayadvanced;
            $mform->setExpanded($displayadvanced);
            $key = array_search($scrollto, $fieldsetarray);
            if ($key !== false) {
                unset($fieldsetarray[$key]);
            }
        }
        $fieldsets = implode(',', $fieldsetarray);
        $inputadvancedsettingselector = "input[name='advancedsettingdisplayed']";
        $PAGE->requires->js_call_amd('mod_udmworkshop/workshopform', 'init', array($inputadvancedsettingselector,
            $fieldsets, \workshop::SELF_ASSESSMENT, $scrollto));

        $inputallowsubmissionselector = "input[name='allowsubmission']";
        $submissionendselector = "input[name='submissionend[enabled]']";
        $PAGE->requires->js_call_amd('mod_udmworkshop/wizardsubmissionsettings', 'init',
                array($inputallowsubmissionselector, $submissionendselector));
    }

    /**
     * Prepares the form before data are set
     *
     * Additional wysiwyg editor are prepared here, the introeditor is prepared automatically by core.
     * Grade items are set here because the core modedit supports single grade item only.
     *
     * @param array $data to be set
     * @return void
     */
    public function data_preprocessing(&$data) {
        if ($this->current->instance) {
            // editing an existing workshop - let us prepare the added editor elements (intro done automatically)
            $draftitemid = file_get_submitted_draft_itemid('instructauthors');
            $data['instructauthorseditor']['text'] = file_prepare_draft_area($draftitemid, $this->context->id,
                                'mod_udmworkshop', 'instructauthors', 0,
                                workshop::instruction_editors_options($this->context),
                                $data['instructauthors']);
            $data['instructauthorseditor']['format'] = $data['instructauthorsformat'];
            $data['instructauthorseditor']['itemid'] = $draftitemid;

            $draftitemid = file_get_submitted_draft_itemid('instructreviewers');
            $data['instructreviewerseditor']['text'] = file_prepare_draft_area($draftitemid, $this->context->id,
                                'mod_udmworkshop', 'instructreviewers', 0,
                                workshop::instruction_editors_options($this->context),
                                $data['instructreviewers']);
            $data['instructreviewerseditor']['format'] = $data['instructreviewersformat'];
            $data['instructreviewerseditor']['itemid'] = $draftitemid;

            $draftitemid = file_get_submitted_draft_itemid('conclusion');
            $data['conclusioneditor']['text'] = file_prepare_draft_area($draftitemid, $this->context->id,
                                'mod_udmworkshop', 'conclusion', 0,
                                workshop::instruction_editors_options($this->context),
                                $data['conclusion']);
            $data['conclusioneditor']['format'] = $data['conclusionformat'];
            $data['conclusioneditor']['itemid'] = $draftitemid;
        } else {
            // adding a new workshop instance
            $draftitemid = file_get_submitted_draft_itemid('instructauthors');
            file_prepare_draft_area($draftitemid, null, 'mod_udmworkshop', 'instructauthors', 0);    // no context yet, itemid not used
            $data['instructauthorseditor'] = array('text' => '', 'format' => editors_get_preferred_format(), 'itemid' => $draftitemid);

            $draftitemid = file_get_submitted_draft_itemid('instructreviewers');
            file_prepare_draft_area($draftitemid, null, 'mod_udmworkshop', 'instructreviewers', 0);    // no context yet, itemid not used
            $data['instructreviewerseditor'] = array('text' => '', 'format' => editors_get_preferred_format(), 'itemid' => $draftitemid);

            $draftitemid = file_get_submitted_draft_itemid('conclusion');
            file_prepare_draft_area($draftitemid, null, 'mod_udmworkshop', 'conclusion', 0);    // no context yet, itemid not used
            $data['conclusioneditor'] = array('text' => '', 'format' => editors_get_preferred_format(), 'itemid' => $draftitemid);
            $data['allowsubmission'] = 1;
        }
    }

    /**
     * Set the grade item categories when editing an instance
     */
    public function definition_after_data() {

        $mform =& $this->_form;

        if ($id = $mform->getElementValue('update')) {
            $instance   = $mform->getElementValue('instance');

            $gradeitems = grade_item::fetch_all(array(
                'itemtype'      => 'mod',
                'itemmodule'    => 'workshop',
                'iteminstance'  => $instance,
                'courseid'      => $this->course->id));

            if (!empty($gradeitems)) {
                foreach ($gradeitems as $gradeitem) {
                    // here comes really crappy way how to set the value of the fields
                    // gradecategory and gradinggradecategory - grrr QuickForms
                    $decimalpoints = $gradeitem->get_decimals();
                    if ($gradeitem->itemnumber == 0) {
                        $submissiongradepass = $mform->getElement('submissiongradepass');
                        $submissiongradepass->setValue(format_float($gradeitem->gradepass, $decimalpoints));
                        $group = $mform->getElement('submissiongradegroup');
                        $elements = $group->getElements();
                        foreach ($elements as $element) {
                            if ($element->getName() == 'gradecategory') {
                                $element->setValue($gradeitem->categoryid);
                            }
                        }
                    } else if ($gradeitem->itemnumber == 1) {
                        $gradinggradepass = $mform->getElement('gradinggradepass');
                        $gradinggradepass->setValue(format_float($gradeitem->gradepass, $decimalpoints));
                        $group = $mform->getElement('gradinggradegroup');
                        $elements = $group->getElements();
                        foreach ($elements as $element) {
                            if ($element->getName() == 'gradinggradecategory') {
                                $element->setValue($gradeitem->categoryid);
                            }
                        }
                    }
                }
            }
        }

        parent::definition_after_data();
    }

    /**
     * Validates the form input
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array eventual errors indexed by the field name
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate lists of allowed extensions.
        foreach (array('submissionfiletypes', 'overallfeedbackfiletypes') as $fieldname) {
            if (isset($data[$fieldname])) {
                $invalidextensions = workshop::invalid_file_extensions($data[$fieldname], array_keys(core_filetypes::get_types()));
                if ($invalidextensions) {
                    $errors[$fieldname] = get_string('err_unknownfileextension', 'mod_udmworkshop',
                        workshop::clean_file_extensions($invalidextensions));
                }
            }
        }

        // check the phases borders are valid
        if ($data['submissionstart'] > 0 and $data['submissionend'] > 0 and $data['submissionstart'] >= $data['submissionend']) {
            $errors['submissionend'] = get_string('submissionendbeforestart', 'mod_udmworkshop');
        }
        if ($data['assessmentstart'] > 0 and $data['assessmentend'] > 0 and $data['assessmentstart'] >= $data['assessmentend']) {
            $errors['assessmentend'] = get_string('assessmentendbeforestart', 'mod_udmworkshop');
        }

        // check the phases do not overlap
        if (max($data['submissionstart'], $data['submissionend']) > 0 and max($data['assessmentstart'], $data['assessmentend']) > 0) {
            $phasesubmissionend = max($data['submissionstart'], $data['submissionend']);
            $phaseassessmentstart = min($data['assessmentstart'], $data['assessmentend']);
            if ($phaseassessmentstart == 0) {
                $phaseassessmentstart = max($data['assessmentstart'], $data['assessmentend']);
            }
            if ($phasesubmissionend > 0 and $phaseassessmentstart > 0 and $phaseassessmentstart < $phasesubmissionend) {
                foreach (array('submissionend', 'submissionstart', 'assessmentstart', 'assessmentend') as $f) {
                    if ($data[$f] > 0) {
                        $errors[$f] = get_string('phasesoverlap', 'mod_udmworkshop');
                        break;
                    }
                }
            }
        }

        // Check that the submission grade pass is a valid number.
        if (!empty($data['submissiongradepass'])) {
            $submissiongradefloat = unformat_float($data['submissiongradepass'], true);
            if ($submissiongradefloat === false) {
                $errors['submissiongradepass'] = get_string('err_numeric', 'form');
            } else {
                if ($submissiongradefloat > $data['grade']) {
                    $errors['submissiongradepass'] = get_string('gradepassgreaterthangrade', 'grades', $data['grade']);
                }
            }
        }

        // Check that the grade pass is a valid number.
        if (!empty($data['gradinggradepass'])) {
            $gradepassfloat = unformat_float($data['gradinggradepass'], true);
            if ($gradepassfloat === false) {
                $errors['gradinggradepass'] = get_string('err_numeric', 'form');
            } else {
                if ($gradepassfloat > $data['gradinggrade']) {
                    $errors['gradinggradepass'] = get_string('gradepassgreaterthangrade', 'grades', $data['gradinggrade']);
                }
            }
        }

        return $errors;
    }
}
