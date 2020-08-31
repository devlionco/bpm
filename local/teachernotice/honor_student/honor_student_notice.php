<?php

/**
 * Process that runs daily. Fetches and calculates honor students and sends notifications
 * To the pedagogical manager for every honor student.
 * Also sends notification for steinberg certificate prints 
 *
 * @author  Ben Laor, BPM
 * @package local/teachernotice
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../config.php';

function bpm_send_honor_student_pman_notices() { 
    $users_list = bpm_get_users();
    $cubase_users = [];

    if (count($users_list) > 0) {
	    foreach ($users_list as $current_user) {
	    	if (!bpm_is_program_type($current_user->coursename)) {
	    		if (bpm_is_honor_student($current_user->userid, $current_user->courseid)) {
    				bpm_send_pman_notice($current_user);
    			}
	    	} else {
	    		if (strpos($current_user->coursename, 'כללי') !== false) {
	    			if (bpm_is_honor_student($current_user->userid, $current_user->courseid)) {
    					bpm_send_pman_notice($current_user, 'program');
    				}
	    		}
	    	}
	    	if (strpos($current_user->coursename, 'קיובייס') !== false &&
	    	    $current_user->english_name !== '') {
	    	    if ($current_user->grade >= 80 && 
	    	    	$current_user->attendance >= 80 && 
	    	    	$current_user->completegrade == 1) {
	    			array_push($cubase_users, $current_user);
	    		}
	    	}
	    }
	}
	if (count($cubase_users) > 0) {
		bpm_send_cubase_cert_request($cubase_users);
	}
}

/**
 * Retrieve a list of users from courses that ended 2 months and 2 days ago
 *
 * @return array{stdClass} Array containing users and courses data
 */
function bpm_get_users() {
	global $DB;

	$standalone = [];
	$program = [];

	$sql = "SELECT se.id,
	               se.userid, 
				   se.courseid,
				   se.grade, 
				   se.attendance,
				   se.completegrade,
				   c.fullname as coursename, 
				   c.category,
				   CONCAT(u.firstname, ' ', u.lastname) as username,
				   sud.english_name
			FROM mdl_sf_enrollments se, 
				 mdl_course c, 
				 mdl_user u, 
				 mdl_sf_user_data sud
			WHERE c.id = se.courseid
			AND   u.id = se.userid
			AND   sud.user_id = se.userid
			AND   se.courseid IN (SELECT c2.id 
							  	  FROM mdl_course c2
							  	  WHERE FROM_UNIXTIME(c2.enddate, '%Y-%m-%d') = 
							  	  	    DATE_SUB(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), INTERVAL 2 DAY))";

	return $DB->get_records_sql($sql);
}

/**
 * Check if a student is an honor student
 *
 * @param  int  $course_name  The course fullname
 *
 * @return bool True if the course is a program general course, else false
 */
function bpm_is_honor_student($user_id, $course_id) {
    global $DB;
    
    $sql = "SELECT grade
    		FROM mdl_sf_enrollments
    		WHERE userid = $user_id
    		AND grade <> -1
    		AND courseid = $course_id
    		AND completegrade = 1";
    $grade = $DB->get_field_sql($sql);

    if (!$grade) {
        return false;
    } else {
        return ($grade >= 92);
    }
}

/**
 * Check if a course is a program general course
 *
 * @param  int  $course_name  The course fullname
 *
 * @return bool True if the course is a program general course, else false
 */
function bpm_is_program_type($course_name) {
    $program_names = array("BSP", "EMP", "GFA", "זמר יוצר", "DMP"); // Course program names;

    for ($name_index=0; $name_index < count($program_names); $name_index++) { 
        if (strpos($course_name, $program_names[$name_index]) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Send a notice to the pedagogical manager pertaining to an honor student
 *
 * @param  stdClass  $user_record  Containing user and course data for the message content
 * @param  string    $type 		   Define the type of message to send
 *
 * @global stdClass  $TN_CFG  Containing ids and other fixed values.
 *
 */
function bpm_send_pman_notice($user_record, $type = 'course') {
	global $TN_CFG;

	$type_string = ($type == 'course') ? 'קורס' : 'מסלול';
	$grade_string = ($type == 'course') ? 'ציון סופי' : 'ממוצע ציונים';

	$message_content = '<p dir="rtl">שם ה' . $type_string . ': ' . $user_record->coursename . '<br>' .
					   $grade_string . ': ' . round((float)$user_record->grade, 1, PHP_ROUND_HALF_EVEN) . '</p>';


	// Build the message object
    $message = new \core\message\message();
    $message->name            = 'honor_student_message';
    $message->component       = 'local_teachernotice';
    $message->userfrom        = $TN_CFG->bpm_bot_id;
    $message->userto          = $TN_CFG->BPM_MM_ID;
    $message->subject         = 'התראה על סטודנט מצטיין - ' . $user_record->username;
    $message->fullmessagehtml = $message_content;
    $message->smallmessage    = 'התראה על סטודנט מצטיין - ' . $user_record->username;

    $result = message_send($message);
}

/**
 * Send a list of cubase course alumni in order to print steinberg certificates
 *
 * @param  array{stdClass}  $users  Containing user and course data for the message content
 *
 * @global stdClass  $TN_CFG  Containing ids and other fixed values.
 *
 */
function bpm_send_cubase_cert_request($users) {
	global $TN_CFG, $DB;
	$message_content = '';

	foreach ($users as $current_user) {
		$course_parent_sf_id = $DB->get_record('course_details', array('courseid' => $current_user->courseid))->coursefather;
        $course_parent_id = $DB->get_record('sf_course_parent_data', array('sf_id' => $course_parent_sf_id))->course_id;
        $page_parameters = array('courseparentid' => $course_parent_id,
                         		 'courseid' => $current_user->courseid, 
                         		 'userid' => $current_user->userid,
                         		 'certtype' => 'cubase');
        $cert_download_url = new moodle_url('/blocks/bpm_utils/cert_view.php', $page_parameters);
		$message_content .= $current_user->english_name . ' - <a href="' . $cert_download_url . '">להורדת התעודה</a><br>';
	}

	// Build the message object
    $message = new \core\message\message();
    $message->name            = 'cubase_alumni_message';
    $message->component       = 'local_teachernotice';
    $message->userfrom        = $TN_CFG->bpm_bot_id;
    $message->userto          = $TN_CFG->bpm_ssd_id;
    $message->subject         = 'רשימת בוגרי קורס קיובייס';
    $message->fullmessagehtml = $message_content;
    $message->smallmessage    = 'רשימת בוגרי קורס קיובייס';


    //commenting the following out - no need for the list via email - 15.1.19
    //$result = message_send($message);
}