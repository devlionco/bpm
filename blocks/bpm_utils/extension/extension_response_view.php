<?php
require_once('../../../config.php');
require_once('../config.php');
require_once('extension_response_form.php');
 
global $DB, $OUTPUT, $PAGE;
 
// Check for all required variables.
$user_id = required_param('userid', PARAM_INT);
$course_id = required_param('courseid', PARAM_INT);
$assignment_id = required_param('assignmentid', PARAM_INT);
$requested_date = optional_param('requesteddate','', PARAM_INT);
 
if (!$course = $DB->get_record('course', array('id' => $course_id))) {
    print_error('invalidcourse', 'block_bpm_utils', $course_id);
}

$page_parameters = array('userid' => $user_id,
						 'courseid' => $course_id, 
                         'assignmentid' => $assignment_id,
                         'requesteddate' => $requested_date);

require_login($course);

$PAGE->set_url('/blocks/bpm_utils/extension/extension_response_view.php', $page_parameters);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('extensionrestitle', 'block_bpm_utils'));

$settingsnode = $PAGE->settingsnav->add(get_string('bpm_utilssettings', 'block_bpm_utils'));
$extension_url = new moodle_url('/blocks/bpm_utils/extension/extension_response_view.php', $page_parameters);
$editnode = $settingsnode->add(get_string('extensioncrumbs', 'block_bpm_utils'), $extension_url);
$editnode->make_active(0);

$extension_response_form = new extension_response_form($extension_url, array('requestdate' => $requested_date));

// Cancelled forms redirect to the course main page.
if($extension_response_form->is_cancelled()) {
    print_object($fromform);
    
// We need to add code to appropriately act on and store the submitted data
// but for now we will just redirect back to the course main page.    
} else if ($fromform = $extension_response_form->get_data()) {
	$message_data = bpm_get_message_data($user_id, $course_id, $assignment_id);
	if ($fromform->reply == 'true') {
		bpm_process_extension($user_id, $assignment_id, $fromform->responsedate);
		if (date('d/m/Y', $requested_date) != date('d/m/Y', $fromform->responsedate)) {
			bpm_send_response($message_data, $fromform->responsedate, $fromform->responsereasontext, 2);
		} else {
			bpm_send_response($message_data, $fromform->responsedate, $fromform->responsereasontext, 1);
		}
	} else {
		bpm_send_response($message_data, $fromform->responsedate, $fromform->responsereasontext, 3);
	}
    $response_success_url = new moodle_url('/blocks/bpm_utils/extension/response_success_view.php', array('courseid' => $course_id));
    redirect($response_success_url);

// form didn't validate or this is the first display
} else {
    $site = get_site();
	echo $OUTPUT->header();
	$extension_response_form->display();
	echo $OUTPUT->footer();
}

function bpm_process_extension($user_id, $assignment_id, $response_date) {
	global $DB;

	if ($extension_record = $DB->get_record('assign_user_flags', array('userid' => $user_id, 'assignment' => $assignment_id))) {
		$update_record = new stdClass();
		$update_record->id = $extension_record->id;
		$update_record->extensionduedate = strtotime('+ 23 hours', $response_date);
		return $DB->update_record('assign_user_flags', $update_record);
	} else {
		$insert_record = new stdClass();
		$insert_record->userid = $user_id;
		$insert_record->assignment = $assignment_id;
		$insert_record->extensionduedate = strtotime('+ 23 hours', $response_date);
		return $DB->insert_record('assign_user_flags', $insert_record);
	}
}

function bpm_get_message_data($user_id, $course_id, $assignment_id) {
	global $DB;

    $sql = "SELECT c.id as course_id, 
    			   c.fullname as course_name,
    			   ass.id as assign_id, 
    			   ass.name as assign_name,
    			   u.id as user_id, 
    			   u.firstname,
    			   u.lastname
			FROM mdl_course c, mdl_assign ass, mdl_user u
			WHERE c.id = $course_id
			AND   u.id = $user_id
			AND   ass.id = $assignment_id";
            
    return $DB->get_record_sql($sql);
}

function bpm_send_response($message_data, $extension_date, $extension_reason, $message_type) {
	global $BU_CFG;

	// Template for BPM footer (no need for indentation)
	$footer = '<body><div dir="rtl" style="text-align:right;">
<br><span style="font-style:italic">אין להשיב לדוא"ל זה</span><br><br> בברכה,<br>מכללת BPM
<p><a href="http://www.bpm-music.com/" style="color:rgb(17,85,204);font-size:12.8px;text-align:right" target="_blank">BPM Website</a><span style="color:rgb(136,136,136);text-align:right;font-size:small">&nbsp;|&nbsp;</span>
<font style="color:rgb(136,136,136);text-align:right;font-size:small" size="2">
<a href="https://www.facebook.com/BPM.College" style="color:rgb(17,85,204);text-align:start" target="_blank">Facebook</a>&nbsp;
<span style="color:rgb(0,0,0);text-align:start">|&nbsp;</span>
<a href="https://instagram.com/bpmcollege/" style="color:rgb(17,85,204);text-align:start" target="_blank">Instagram</a>&nbsp;|&nbsp;
<a href="https://soundcloud.com/bpmsoundschool" style="color:rgb(17,85,204)" target="_blank">Soundcloud</a>&nbsp;|&nbsp;
<a href="https://www.youtube.com/user/BPMcollege" style="color:rgb(17,85,204)" target="_blank">Youtube</a></font></p>
<img src="https://my.bpm-music.com/local/BPM_pix/footer2018/mail_sign_heb.png" style="border:none" width="500">
</div></body>';

	$message_body = '';
	$message_subject = '';
	$accept_description = ($extension_reason != '') ? " פירוט האישור:<br>" . $extension_reason . "<br>" : '';
	$extension_date = date('d/m/Y', $extension_date);

	switch ($message_type) {
		case 1:
			$message_subject = 'אושרה בקשתך להארכת זמן';
			$message_body = "<p dir=\"rtl\">היי " . $message_data->firstname . ",<br>" .
				"בקשתך להארכת זמן ב<b>" . $message_data->assign_name . "</b>" . 
				" בקורס <b>" . $message_data->course_name . "</b>" .
				" אושרה עד לתאריך <b>" . $extension_date . "</b>.<br>" .
				$accept_description . 
				"<br><br>" . $footer . "</p>";
			break;
		case 2:
			$message_subject = 'אושרה בקשתך להארכת זמן';
			$message_body = "<p dir=\"rtl\">היי " . $message_data->firstname . ",<br>" .
				"ניתנה לך הארכת זמן ב<b>" . $message_data->assign_name . "</b>" . 
				" בקורס <b>" . $message_data->course_name . "</b>" .
				" עד לתאריך <b>" . $extension_date . "</b>.<br>" . 
				$accept_description .
				"<br><br>" . $footer . "</p>";
			break;
		case 3:
			$message_subject = 'נדחתה בקשתך להארכת זמן';
			$message_body = "<p dir=\"rtl\">היי " . $message_data->firstname . ",<br>" .
				"בקשתך להארכת זמן ב<b>" . $message_data->assign_name . "</b>" . 
				" בקורס <b>" . $message_data->course_name . "</b>" .
				" נדחתה.<br>" . 
				" פירוט הדחייה:<br>" . $extension_reason . "<br>" .
				"אם ברצונך לערער על החלטה זו, פנה למדור לימודים בטלפון 035604781 או במייל:limudim@bpm-music.com<br>" .
				"<br><br>" . $footer . "</p>";
			break;
		default:
			break;
	}

	// Build the message object
    $message = new \core\message\message();
    $message->courseid        = $message_data->course_id;
    $message->name            = 'extension_response_message';
    $message->component       = 'block_bpm_utils';
    $message->userfrom        = $BU_CFG->BPM_BOT_ID;
    $message->userto          = $message_data->user_id;
    $message->subject         = $message_subject;
    $message->fullmessagehtml = $message_body;
    $message->smallmessage    = $message_subject;

    $result = message_send($message);
    
    //send additional message to time-extensions bot
    $message->userto = $BU_CFG->BPM_TE;
    $message->subject         = 'בקשה להארכת זמן - ' . $message_data->firstname . ' ' . $message_data->lastname;
    
    $result = message_send($message);
}