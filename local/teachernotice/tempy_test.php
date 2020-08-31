<?php

require_once __DIR__ . '/../../config.php';
require_once($CFG->dirroot . '/lib/classes/user.php');
require_once('config.php');
require_once('lib.php');

bpm_process_attendance_notices();

function bpm_process_attendance_notices() { 
	global $DB;
	$eol = "\r\n<br>";
	$sessions_to_delete = array();
	$sessions_sql = "SELECT atts.id as sessid, att.id as attendance_id, att.course as course_id,  c.startdate as startdate, atts.sessdate, atts.lasttaken, atts.description
					 FROM mdl_attendance_sessions atts, mdl_attendance att, mdl_course c
				     WHERE att.id = atts.attendanceid
					 AND att.course = c.id
                     AND FROM_UNIXTIME(atts.sessdate, '%Y-%m-%d') = DATE_SUB(CURDATE(), INTERVAL 5 DAY)
					 AND (atts.lasttaken IS NULL OR (atts.description = '' OR atts.description = 'מפגש כיתתי'))
                     AND c.fullname NOT LIKE '%בדיקה%'";
    
	$session_records = $DB->get_records_sql($sessions_sql);

	foreach ($session_records as $current_session) {
	    echo 'course id: ' . $current_session->course_id . $eol;
	    echo 'session id: ' . $current_session->sessid . $eol;
	    echo 'session description: ' . $current_session->description . $eol;
		if ($current_session->sessdate < $current_session->startdate) {
			array_push($sessions_to_delete, $current_session->sessid);
		} else {
		    //count logs vs students
		    
		    $student_count = bpm_count_students_in_course($current_session->course_id);
		    echo 'student count: ' . $student_count . $eol;
		    
		    $logs_count = bpm_count_attendance_logs_for_session($current_session->sessid);
		    echo 'logs count: ' . $logs_count . $eol;
		    if ($logs_count <= 0 ||
		        $student_count <= $logs_count ||
		        $current_session->lasttaken == NULL ||
		        ($current_session->description == '' || $current_session->description == 'מפגש כיתתי'
	        )) {
	            echo 'ay';
    		    
    			$teacher_id = \utils::bpm_get_teacher_id($current_session->course_id);
    			if ($teacher_id !== false) {
    				$message_type = 0;
    
    				if ($missed_row = $DB->get_record('bpm_attendance_missed', 
    												  array('courseid' => $current_session->course_id, 'teacherid' => $teacher_id))) {
    					$missed_row->missedcount += 1;
    			//		$DB->update_record('bpm_attendance_missed', $missed_row);
    					$message_type = ($missed_row->missedcount > 2) ? 3 : 2;
    				} else {
    					$new_missed = new stdClass();
    					$new_missed->courseid = $current_session->course_id;
    					$new_missed->teacherid = $teacher_id;
    					$new_missed->missedcount = 1;
    			//		$DB->insert_record('bpm_attendance_missed', $new_missed);
    					$message_type = 1;
    				}
    
    				$teacher = core_user::get_user($teacher_id);
    				$teacher->name = $teacher->firstname . ' ' . $teacher->lastname;
    				$course = $DB->get_record('course', array('id' => $current_session->course_id));
    				//bpm_send_attendance_missed_notice($teacher, $course, $current_session->sessdate, $message_type);
    				echo 'sending notice to ';
    				var_dump($teacher);
    				var_dump($course);
    				var_dump($message_type);
    			}
	        }
		}
	}
	if (count($sessions_to_delete) > 0) {
		$sessions_string = implode(", ", $sessions_to_delete);
		$delete_sql = "DELETE FROM mdl_attendance_sessions WHERE id IN (" . $sessions_string . ")";
		$DB->execute($delete_sql);
	}
}

function bpm_count_students_in_course($course_id) {
    global $DB;
    
    $sql = "SELECT count(usr.id)
            FROM mdl_role_assignments ra, mdl_role r, mdl_user usr, mdl_enrol e, mdl_user_enrolments ue, mdl_course c, mdl_context cx 
            WHERE c.id = cx.instanceid
			AND cx.contextlevel = '50' AND c.id= $course_id
            AND	cx.id = ra.contextid
            AND ra.roleid = r.id
            AND	ra.userid = usr.id
			AND e.courseid = c.id
            AND e.id = ue.enrolid AND ue.userid = usr.id
            AND r.id = 5 
			AND ue.status != 1";
	$result = $DB->execute($sql);
	return $result;
	
}


function bpm_count_attendance_logs_for_session($sess_id) {
    global $DB;
    
    $sql = "SELECT count(id) FROM `mdl_attendance_log` where sessionid = $sess_id";
    
    $result = $DB->execute($sql);
    return $result;
}

function bpm_send_attendance_missed_notice($teacher, $course, $session_date, $message_type) {
	global $TN_CFG;

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

	$session_date = date('d/m/Y', $session_date);

	// Build the message object
    $message = new \core\message\message();
    $message->name            = 'attendance_missing_message';
    $message->component       = 'local_teachernotice';
    $message->userfrom        = $TN_CFG->bpm_bot_id;
    $message->userto 		  = $teacher->id;

	switch ($message_type) {
		case 1:
			$message->subject = 'אי הזנת יומן נוכחות';
			$message->smallmessage = 'אי הזנת יומן נוכחות';
			$message->fullmessagehtml = "<p dir=\"rtl\">היי " . $teacher->firstname . ",<br>" .
				"בתאריך <b>" . $session_date . "</b>" .
				" התקיים מפגש בקורס <b>" . $course->fullname . "</b>" .
				" ולא הזנת נוכחות ו/או את תוכן השיעור ליומן הכיתה במודל. במידה ומקרה זה יחזור - יירדו לך באופן אוטומטי נקודות בחישוב ציון המשובים.<br><br>" . 
				"יש להקפיד על הנהלים.<br>" . $footer . "</p>";
			message_send($message);
			break;
		case 2:
			$message->subject = 'אי הזנת יומן נוכחות';
			$message->smallmessage = 'אי הזנת יומן נוכחות';
			$message->fullmessagehtml = "<p dir=\"rtl\">היי " . $teacher->firstname . ",<br>" .
				"בתאריך <b>" . $session_date . "</b>" .
				" התקיים מפגש בקורס <b>" . $course->fullname . "</b>" .
				" ולא הזנת נוכחות ו/או את תוכן השיעור ליומן הכיתה במודל.<br>זוהי הפעם השניה שזה קורה בקורס זה, ולכן יירדו לך באופן אוטומטי נקודות בחישוב ציון המשובים.<br><br>" . 
				"יש להקפיד על הנהלים.<br>" . $footer . "</p>";
			message_send($message);
			$message->userto = $TN_CFG->bpm_ssd_id;
			message_send($message);
			utils::bpm_update_notfilled_in_sf($course->id);
			break;
		case 3:
			$message->subject = $teacher->firstname . " - אי הזנת יומן נוכחות";
			$message->smallmessage = $teacher->firstname . " - אי הזנת יומן נוכחות";
			$message->fullmessagehtml = "<p dir=\"rtl\">שם המרצה: <b>" . $teacher->firstname . ' ' . $teacher->lastname . "</b><br>" .
				"שם הקורס: <b>" . $course->fullname . "</b><br>" .
				"זו הפעם השלישית בה הנ\"ל לא הזין נוכחות ו/או מילא את תוכן השיעור במפגש.<br><br>" .
				"לטיפולך.<br>" . $footer . "</p>";
			$message->userto = $TN_CFG->BPM_PMAN_ID;
			message_send($message);
			break;
		default:
			break;
	}
}