<?php
// This file is part of the Teacher notice plugin for Moodle - http://moodle.org/
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
 * Declare and define the methods responsible for notices being sent to teachers 
 * and management of the "local_teacher_notice" table 
 * (to be called via an external file that is run by a cron job)
 *
 * @author  Ben Laor, BPM
 * @package local/teachernotice
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once($CFG->dirroot . '/lib/classes/user.php');
require_once('config.php');
require_once('lib.php');

function bpm_process_attendance_notices() { 
	global $DB;
	
	$sessions_to_delete = array();
	$sessions_sql = "SELECT atts.id as sessid, att.id as attendance_id, att.course as course_id,  c.startdate as startdate, atts.sessdate, atts.lasttaken, atts.description
					 FROM mdl_attendance_sessions atts, mdl_attendance att, mdl_course c
				     WHERE att.id = atts.attendanceid
					 AND att.course = c.id
                     AND FROM_UNIXTIME(atts.sessdate, '%Y-%m-%d') = DATE_SUB(CURDATE(), INTERVAL 2 DAY)
					 AND (atts.lasttaken IS NULL OR (atts.description = '' OR atts.description = 'מפגש כיתתי'))
                     AND c.fullname NOT LIKE '%בדיקה%'";
    
	$session_records = $DB->get_records_sql($sessions_sql);

	foreach ($session_records as $current_session) {
		if ($current_session->sessdate < $current_session->startdate) {
			array_push($sessions_to_delete, $current_session->sessid);
		} else {
		    //count logs vs students
		    $student_count = bpm_count_students_in_course($current_session->course_id);
		    
		    
		    $logs_count = bpm_count_attendance_logs_for_session($current_session->sessid);
		    
		    if ($logs_count <= 0 ||
		        $student_count <= $logs_count ||
		        $current_session->lasttaken == NULL ||
		        ($current_session->description == '' || $current_session->description == 'מפגש כיתתי'
	        )) {
		    
    			$teacher_id = \utils::bpm_get_teacher_id($current_session->course_id);
    			if ($teacher_id !== false) {
    				$message_type = 0;
    
    				if ($missed_row = $DB->get_record('bpm_attendance_missed', 
    												  array('courseid' => $current_session->course_id, 'teacherid' => $teacher_id))) {
    					$missed_row->missedcount += 1;
    					$DB->update_record('bpm_attendance_missed', $missed_row);
    					$message_type = ($missed_row->missedcount > 2) ? 3 : 2;
    				} else {
    					$new_missed = new stdClass();
    					$new_missed->courseid = $current_session->course_id;
    					$new_missed->teacherid = $teacher_id;
    					$new_missed->missedcount = 1;
    					$DB->insert_record('bpm_attendance_missed', $new_missed);
    					$message_type = 1;
    				}
    
    				$teacher = core_user::get_user($teacher_id);
    				$teacher->name = $teacher->firstname . ' ' . $teacher->lastname;
    				$course = $DB->get_record('course', array('id' => $current_session->course_id));
    				bpm_send_attendance_missed_notice($teacher, $course, $current_session->sessdate, $message_type);
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


function bpm_prepare_attendance_report() {
    global $DB, $TN_CFG;

    $email_recipients = array(
        "haifa"    => 'mazkirut-haifa@bpm-music.com',
        "tel_aviv" => 'mazkirut@bpm-music.com'
        );
     
     
    $result = false;

    $sql = "SELECT cm.id as modid,
                   sess.id as sessid,
                   (DATE(FROM_UNIXTIME(sess.sessdate))) as sessdate, 
                    c.startdate as startdate,
                   sess.sessdate as sessunixtime, 
                   c.fullname 
            FROM mdl_course c, 
                 mdl_attendance_sessions sess, 
                 mdl_attendance att,
                 mdl_course_modules cm
            WHERE sess.attendanceid = att.id 
            AND   att.course = c.id 
            AND   cm.course = c.id
            AND   cm.module = 23
            AND   (DATE(FROM_UNIXTIME(sess.sessdate)) BETWEEN (CURDATE() - INTERVAL 1 DAY) AND CURDATE()) 
            AND   (DATE(FROM_UNIXTIME(sess.sessdate)) != CURDATE())";

    $att_sessions = $DB->get_records_sql($sql);
    
    
    
    if (count($att_sessions) > 0) {
        
        $first_cell = reset($att_sessions);
        $heb_date = date("d-m-Y", strtotime($first_cell->sessdate));
        $mail_content = "<p dir=\"rtl\"><b>להלן הקורסים בהם התקיים מפגש בתאריך " . $heb_date . ":</b><br><br>";
        $count_tel_aviv = 0;
        $count_haifa = 0;
        
        $tel_aviv_attendance = $mail_content;
        $haifa_attendance = $mail_content;
        $sessions_to_delete = array();
        foreach ($att_sessions as $session) {
        	if ($session->sessdate < $session->startdate) {
        			array_push($sessions_to_delete, $session->sessid);
        	} else {
                if (strpos($session->fullname, "חיפה") == true) {
                    $attendance_url = $attendance_url = "https://my.bpm-music.com/mod/attendance/take.php?id=" . $session->modid . 
                                  "&sessionid=" . $session->sessid . "&grouptype=0";
                    $haifa_attendance .= $session->fullname . "<br>" .
                                "לינק למפגש: " . $attendance_url . "<br><br>";
                    $count_haifa++;
                } else {
                    $attendance_url = $attendance_url = "https://my.bpm-music.com/mod/attendance/take.php?id=" . $session->modid . 
                                  "&sessionid=" . $session->sessid . "&grouptype=0";
                    $tel_aviv_attendance .= $session->fullname . "<br>" .     
                                "לינק למפגש: " . $attendance_url . "<br><br>";
                    
                    $count_tel_aviv++;
                    //$tel_aviv_attendance .= "ספירת חיפה: " . $count_haifa . "<br>ספירת תל אביב: " . $count_tel_aviv;
                }
        	}
            
        }

        if (count($sessions_to_delete) > 0) {
    		$sessions_string = implode(", ", $sessions_to_delete);
    		$delete_sql = "DELETE FROM mdl_attendance_sessions WHERE id IN (" . $sessions_string . ")";
    		$DB->execute($delete_sql);
    	}

        foreach($email_recipients as $branch => $address) {
            
            if ($branch == 'haifa') {
                if ($count_haifa > 0) {
                    echo bpm_email_attendance_report($address, $haifa_attendance, $heb_date);
                }
            } else {
                if ($count_tel_aviv > 0) {
                    echo bpm_email_attendance_report($address, $tel_aviv_attendance, $heb_date);
                }
            }
        }
    }

    return $result;
	
}

function bpm_email_attendance_report($address, $message, $date) {
	$subject = "דוח נוכחות לתאריך " . $date;
	$headers  = "To: " . $address . " <" . $address . ">"     . "\r\n";
    $headers = "From: מכללת BPM <noreply@bpm-music.com>"     . "\r\n";
    $headers .= "MIME-Version: 1.0"                           . "\r\n";
    $headers .= "Content-Type: text/html; charset=iso-8859-1" . "\r\n";

    $response = mail($address, $subject, $message, $headers);
	
	if($response) {
        return 'all good';
    } else {
        return 'error';
    };
}
