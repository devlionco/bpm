<?php

require_once("{$CFG->libdir}/formslib.php");
 
class postpone_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('checkbox', 'smscb', get_string('smscheckbox', 'block_bpm_utils'), '', array('class' => 'messageCheckbox'));
        $mform->setDefault('smscb', true);
        $mform->addElement('checkbox', 'forumcb', get_string('forumcheckbox', 'block_bpm_utils'), '', 
            array('checked' => true, 'class' => 'messageCheckbox'));
        $mform->setDefault('forumcb', true);
        $mform->addElement('html', get_string('postponecoursetext', 'block_bpm_utils', $this->_customdata['coursename']));
		$mform->addElement('textarea', 'custommessagetext', get_string('postponetemplate', 'block_bpm_utils'));
		$mform->setType('custommessagetext', PARAM_RAW);
		$mform->addRule('custommessagetext', 'יש להזין תוכן הודעה', 'required', null, 'client');
		$mform->addElement('html', get_string('postponetemplate2', 'block_bpm_utils'));

        $form_js = "<script type='text/javascript'>
                        $('.messageCheckbox').change(function(){
                            var checkboxes = $('.messageCheckbox');
                            if (checkboxes.is(':checked')) {
                                $('#id_submitbutton').val('ביטול מפגש ושליחת הודעה לסטודנטים');
                            } else {
                                $('#id_submitbutton').val('ביטול מפגש');
                            }
                        });
                   </script>";
        $mform->addElement('html', $form_js);

		$this->add_action_buttons(true, 'ביטול מפגש ושליחת הודעה לסטודנטים');
    }
}