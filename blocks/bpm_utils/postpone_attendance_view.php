<?php
require_once('../../config.php');
require_once('config.php');
require_once('postpone_form.php');
 
global $DB, $OUTPUT, $PAGE;
 
// Check for all required variables.
$session_id = required_param('sessionid', PARAM_INT);
$course_id = required_param('courseid', PARAM_INT);
$course_end_date = required_param('courseenddate', PARAM_INT);
 
if (!$course = $DB->get_record('course', array('id' => $course_id))) {
    print_error('invalidcourse', 'block_bpm_utils', $course_id);
}

$form_parameters = array('sessionid' => $session_id, 
                         'courseid' => $course_id,
                         'courseenddate' => $course_end_date);

require_login($course);

$PAGE->set_url('/blocks/bpm_utils/postpone_attendance_view.php', $form_parameters);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('postponetitle', 'block_bpm_utils'));

$settingsnode = $PAGE->settingsnav->add(get_string('bpm_utilssettings', 'block_bpm_utils'));
$postpone_url = new moodle_url('/blocks/bpm_utils/postpone_attendance_view.php', $form_parameters);
$editnode = $settingsnode->add(get_string('postponecrumbs', 'block_bpm_utils'), $postpone_url);
$editnode->make_active(0);

$course_name = bpm_get_course_name($course_id);
$postpone_form = new postpone_form($postpone_url, array('coursename' => $course_name));

// Cancelled forms redirect to the course main page.
if($postpone_form->is_cancelled()) {
    $course_url = new moodle_url('/course/view.php', array('id' => $course_id));
    redirect($course_url);
    
// We need to add code to appropriately act on and store the submitted data
// but for now we will just redirect back to the course main page.    
} else if ($fromform = $postpone_form->get_data()) {
    $smscb = isset($fromform->smscb) ? true : false;
    $forumcb = isset($fromform->forumcb) ? true : false;
	bpm_send_postpone_messages($course_id, $course_name, $fromform->custommessagetext, $smscb, $forumcb);
	bpm_process_postpone($course_id, $session_id, $course_end_date);
	$postpone_success_url = new moodle_url('/blocks/bpm_utils/postpone_success_view.php', array('courseid' => $course_id));
	redirect($postpone_success_url);

// form didn't validate or this is the first display
} else {
    $site = get_site();
	echo $OUTPUT->header();
	$postpone_form->display();
	echo $OUTPUT->footer();
}

function bpm_send_postpone_messages($course_id, $course_name, $custom_message, $sms, $forum) {
	if ($sms) {
		$sms_message = "ביטול מפגש ב" . $course_name . ". סטודנטים יקרים, " . $custom_message . " מתנצלים על אי הנעימות. יום טוב, מדור לימודים.";
		bpm_prepare_and_send_postpone_sms($course_id, $sms_message);
	}

	if ($forum) {
		bpm_create_forum_post($course_id, $course_name, $custom_message);
	}
}

function bpm_process_postpone($course_id, $session_id, $course_end_date) {
	global $DB;

    $timemodified = time();
	$update_session_sql = "UPDATE mdl_attendance_sessions
						   SET sessdate = $course_end_date + 604800, timemodified = $timemodified
						   WHERE id = $session_id";

	$DB->execute($update_session_sql);

	$update_course_sql = "UPDATE mdl_course
						  SET enddate = enddate + 604800, timemodified = $timemodified
						  WHERE id = $course_id";

	$DB->execute($update_course_sql);
}

function bpm_get_course_name($course_id) {
	global $DB;

    $sql = "SELECT fullname
            FROM mdl_course
            WHERE id = $course_id";
            
    return $DB->get_field_sql($sql);
}

function bpm_prepare_and_send_postpone_sms($course_id, $message) {
	global $DB, $BU_CFG;

	$student_numbers_sql = "SELECT usr.id, usr.phone2
                            	FROM mdl_course c
                                INNER JOIN mdl_context cx ON c.id = cx.instanceid
                                INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid
                                INNER JOIN mdl_role r ON ra.roleid = r.id
                                INNER JOIN mdl_user usr ON ra.userid = usr.id
                                	AND cx.contextlevel = '50' 
                                	AND c.id = $course_id
                              	INNER JOIN mdl_enrol e ON c.id = e.courseid
                                INNER JOIN mdl_user_enrolments ue ON ue.enrolid = e.id
                                	AND ue.userid = usr.id
                                WHERE r.id = 5
                                	AND ue.status = 0";

	$number_records = $DB->get_records_sql($student_numbers_sql);
	$reciever_string = "";

	foreach ($number_records as $current_record) {
		$reciever_string .= $current_record->phone2 . ',';
	}
	$reciever_string = substr($reciever_string, 0, -1);

    bpm_send_sms($reciever_string, $message);
}

function bpm_send_sms($recievers_string, $message) {
	global $BU_CFG;

	$url = $BU_CFG->CELLACT['ENDPOINT'] . 
		   $BU_CFG->CELLACT['CREDENTIALS'] .
		   urlencode($message) .
		   $BU_CFG->CELLACT['RECIEVER'] .
		   $recievers_string;

	$curl = curl_init($url);
 	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
}

function bpm_create_forum_post($course_id, $course_name, $custom_message) {
	global $DB;

	$subject = 'ביטול מפגש בקורס ' . $course_name;
	$message = "<p>ביטול מפגש ב<b>" . $course_name . "</b></p><br>" .
			   "סטודנטים יקרים,<br>" . $custom_message . ".<br>" .
			   "מתנצלים על אי הנעימות.<br>יום טוב,<br>מדור לימודים.";
	$forum_id = $DB->get_field('forum', 'id', array('course' => $course_id, 'name' => 'הודעות מערכת'));

	$discussion_record = new stdClass();
	$discussion_record->course = $course_id;
	$discussion_record->forum = $forum_id;
	$discussion_record->name = $subject;
	$discussion_record->userid = 121;
	$discussion_record->timemodified = time();
	$discussion_record->usermodified = 121;

	$discussion_id = $DB->insert_record('forum_discussions', $discussion_record, true);

	if (isset($discussion_id)) {
		$post_record = new stdClass();
		$post_record->discussion = $discussion_id;
		$post_record->userid = 121;
		$post_record->created = time();
		$post_record->modified = time();
		$post_record->subject = $subject;
		$post_record->message = $message;
		$post_record->messageformat = 1;
		$post_record->messagetrust = 1;
		$post_record->mailnow = 1;

		$post_id = $DB->insert_record('forum_posts', $post_record, true);

		if (isset($post_id)) {
			$discussion_update = new stdClass();
			$discussion_update->id = $discussion_id;
			$discussion_update->firstpost = $post_id;
			$DB->update_record('forum_discussions' , $discussion_update);
		}
	}
}