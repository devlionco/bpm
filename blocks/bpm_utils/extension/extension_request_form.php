<?php

require_once("{$CFG->libdir}/formslib.php");
 
class extension_request_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('html', get_string('extensiontemplate', 
        									  'block_bpm_utils', [
        									      'coursename' => $this->_customdata['coursename'],
        									      'assignmentname' => $this->_customdata['assignmentname'],
        									      'assigndate' => $this->_customdata['assigndate']
        									  ]));
        $mform->addElement('date_selector', 'requesteddate', get_string('extensionreqdate', 'block_bpm_utils'));
		$mform->addElement('textarea', 'requestreasontext', get_string('extensionreqreason', 'block_bpm_utils'));
		$mform->setType('requestreasontext', PARAM_RAW);
		$mform->addRule('requestreasontext', 'יש להזין סיבת בקשה', 'required', null, 'client');

		$this->add_action_buttons(true, 'שליחת הבקשה');
    }
}