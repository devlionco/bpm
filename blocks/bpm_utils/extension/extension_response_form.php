<?php

require_once("{$CFG->libdir}/formslib.php");
 
class extension_response_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

		$radio_html = '<div class="form-group row fitem">
    					<div class="col-md-3">
    					 <span class="pull-xs-right text-nowrap">
						 </span>
    					 <label class="col-form-label d-inline " for="fgroup_id_radioar">
    					 </label>
    					</div>
    					<div class="col-md-9 form-inline felement" style="display: grid;" data-fieldtype="group">
						 <label class="form-check-inline form-check-label  fitem  ">
						  <input name="responseradio" id="id_responseradio_0" value="0" type="radio" required>
						  דחייה
						 </label>
						 <span class="form-control-feedback" id="id_error_responseradio" style="display: none;">
    					 </span>
            			 <label class="form-check-inline form-check-label  fitem  ">
						  <input name="responseradio" id="id_responseradio_1" value="0" type="radio">
						  אישור
						 </label>
						 <span class="form-control-feedback" id="id_error_responseradio" style="display: none;">
    					 </span>
        				 <div class="form-control-feedback" id="id_error_" style="display: none;">
            			 </div>
    					</div>
					   </div>';

		$mform->addElement('html', $radio_html);
        $mform->addElement('date_selector', 'responsedate', get_string('extensionresdate', 'block_bpm_utils'), '', array('disabled' => 'true'));
        $mform->setDefault('responsedate',  $this->_customdata['requestdate']);
		$mform->addElement('textarea', 'responsereasontext', get_string('extensionresreason', 'block_bpm_utils'), 'rows="4"');
		$mform->setType('requestreasontext', PARAM_RAW);
		$mform->addElement('hidden', 'reply', '', array('id' => 'extensionreply'));
		$mform->setType('reply', PARAM_RAW);

		$this->add_action_buttons(true, 'שליחת תשובה');

		$form_js = "<script type='text/javascript'>
                	var radioAccept = document.getElementById(\"id_responseradio_1\");
                	var radioDeny = document.getElementById(\"id_responseradio_0\");
                	var dateSelector = document.getElementById(\"id_responsedate_calendar\");
                	var dateDay = document.getElementById(\"id_responsedate_day\");
                	var dateMonth = document.getElementById(\"id_responsedate_month\");
                	var dateYear = document.getElementById(\"id_responsedate_year\");
                	var reasonText = document.getElementById(\"id_responsereasontext\");
                	var reply = document.getElementById(\"extensionreply\");
                	dateSelector.style.display = \"none\";
                	radioDeny.addEventListener('click', function() {
                	    reply.value = false;
                		reasonText.setAttribute(\"required\", true);
                		dateSelector.style.display = \"none\";
                		dateDay.disabled = true;
                		dateMonth.disabled = true;
                		dateYear.disabled = true;
                	});
                	radioAccept.addEventListener('click', function() {
                	    reply.value = true;
                		reasonText.removeAttribute(\"required\", false);
                		dateSelector.style.display = \"inline\";
                		dateDay.disabled = false;
                		dateMonth.disabled = false;
                		dateYear.disabled = false;
                	});
               		</script>";
		$mform->addElement('html', $form_js);
    }
}