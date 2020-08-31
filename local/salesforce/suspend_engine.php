<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../../config.php';
require_once 'config.php';


 bpm_suspend_expired_courses();
/**
 * Retrieve all suspended enrollments and update status in salesforce
 * Suspend user if there are no active enrollments left
 *
 * @global stdClass  $DB  The moodle database API
 *
 */
function bpm_process_suspended_enrollments() {
	global $DB;

	$users_sql = "SELECT id
				  FROM mdl_user u
				  WHERE u.suspended = 0";
	$users = $DB->get_records_sql($users_sql);

	// Go over each user's enrollments and suspend if necessary
	foreach ($users as $user) {    
		$active_enrollments = false;
		$enrollments_sql = "SELECT ue.id, ue.status, c.enddate
							FROM mdl_user_enrolments ue, mdl_enrol e, mdl_course c
							WHERE e.id = ue.enrolid
							AND e.courseid = c.id
							AND   ue.userid = $user->id";
		$last_course = 0;

		$enrollments = $DB->get_records_sql($enrollments_sql);
		
		// Update each suspended enrollment in sf and turn on flag
		foreach ($enrollments as $enrollment) {
			if ($enrollment->status == 0) {
				$active_enrollments = true;
			}

			if ($enrollment->enddate > $last_course) {
				$last_course = $enrollment->enddate;
			}
			
			// } else {
			// 	$sf_enrollment_id = bpm_get_sf_enrollment_id($user->id, $enrollment->courseid);
			// 	if ($sf_enrollment_id) {
			// 		$sf_access_data = bpm_get_sf_auth_data();
			// 		bpm_suspend_sf_enrollment($sf_access_data, $sf_enrollment_id);
			// 	}
			// }
		}

		// Suspend only students from moodle if no active enrollments left
		if ($active_enrollments == false || $last_course < time()) {
			if (bpm_is_student($user->id) == true) {
				bpm_change_mail_status($user->id, true);
				//bpm_suspend_user($user->id); - Deprecated
			}
		} else {
			bpm_change_mail_status($user->id, false);
		}
	}
}

/**
 * Retrieve Salesforce access token and instance (session) url
 *
 * @global stdClass $S_CFG Object containing config data
 *
 * @return stdClass Object containing the sf connection data
 */
function bpm_get_sf_auth_data() {
	global $S_CFG;

	$post_data = array(
        'grant_type'    => 'password',
        'client_id'     => $S_CFG->BPM_SF_OAUTH_KEY,
        'client_secret' => $S_CFG->BPM_SF_USER_SECRET,
        'username'      => $S_CFG->BPM_SF_USER_NAME,
        'password'      => $S_CFG->BPM_SF_USER_PASS
	);
	
	$headers = array(
    	'Content-type' => 'application/x-www-form-urlencoded;charset=UTF-8'
	);

	$curl = curl_init($S_CFG->BPM_SF_ACCESS_URL);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

	$response = curl_exec($curl);
	curl_close($curl);

    // Retrieve and parse response body
    $sf_response_data = json_decode($response, true);

    return $sf_response_data;
}

/**
 * Update registration status in salesforce
 *
 * @param  stdClass $sf_access_data   Object contaning salesforce access data
 * @param  int 		$sf_enrollment_id Salesforce enrollment id
 *
 * @global stdClass $S_CFG Object containing config data
 *
 * @return stdClass Object containing the sf connection data
 */
function bpm_suspend_sf_enrollment($sf_access_data, $sf_enrollment_id) {
	global $S_CFG;

	$post_data = array(
		"Status__c" => 'Cancelled',
		"Entitlement__c" => 'Suspended'
	);

 	$url = $sf_access_data['instance_url'] . $S_CFG->BPM_SF_REGISTRATION_URL . $sf_enrollment_id;
 	$json_data = json_encode($post_data);
 	
 	$headers = array(
 		"Authorization: OAuth " . $sf_access_data['access_token'],
     	"Content-type: application/json"
 	);

 	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
 	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
 	curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);

 	$response = curl_exec($curl);

    echo '<br \>';
	var_dump($response);
	echo '<br \>';

 	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

 	if ( $status != 204 ) {
	    die("Error: call to URL $url failed with status $status, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
	}

	echo "Updated registration: " . $sf_enrollment_id . " status to Cancelled";

	curl_close($curl);
}

/**
 * Suspend user in the Moodle DB
 *
 * @param  stdClass $user_id User moodle id
 *
 * @global stdClass $DB The moodle database API
 */
function bpm_suspend_user($user_id) {
	global $DB;

	$sql = "UPDATE mdl_user u
			SET    u.suspended = 1
			WHERE  u.id = $user_id";

	$DB->execute($sql);

	echo "Suspended user: " . $user_id . " in the Moodle DB";
}

/**
 * Change user email status
 *
 * @param  int  $user_id User moodle id
 * @param  bool $remove  Changed email status
 *
 * @global stdClass $DB The moodle database API
 */
function bpm_change_mail_status($user_id, $remove) {
	global $DB;
	$status = ($remove == true) ? 1 : 0;
	$log = ($remove == true) ? 'not receive emails' : 'receive emails';

	$sql = "UPDATE mdl_user u
			SET    u.emailstop = $status
			WHERE  u.id = $user_id";

	$DB->execute($sql);

	//echo "Changed user: " . $user_id . " email status to " . $log;
}

/**
 * Check if a user is a student in the moodle DB
 *
 * @param  stdClass $user_id User moodle id
 *
 * @global stdClass $DB The moodle database API
 */
function bpm_is_student($user_id) {
	global $DB;

	$is_student = true;

	$roles_sql = "SELECT ra.id, r.shortname
				  FROM   mdl_role_assignments ra, mdl_role r
				  WHERE ra.userid = $user_id
				  AND   r.id = ra.roleid";

	$user_roles = $DB->get_records_sql($roles_sql);

	if (count($user_roles) == 0) {
		$is_student = false;
	} else {
		foreach ($user_roles as $role) {
			if ($role->shortname <> 'student') {
				$is_student = false;
			}
		}
	}

	return $is_student;
}

/**
 * Retrieve all courses with an "enddate" that are older than 12 months.
 * Suspend all the course enrollments and hide the course
 *
 * @global stdClass  $DB  The moodle database API
 *
 */
function bpm_suspend_expired_courses() {
	global $DB;

	$course_date_control = strtotime('-12 month', time());

	$courses_sql = "SELECT id
					FROM   mdl_course
					WHERE enddate <> 0
					AND   enddate < $course_date_control
					AND   visible = 1";

	$courses = $DB->get_records_sql($courses_sql);
    
	// Iterate over the enrollments of each course
	foreach ($courses as $course) {
		$course_enrollments = bpm_get_course_active_enrollments($course->id);

		// For each enrollment of the expired course, suspend in sf and moodle.
		if (count($course_enrollments) > 0) {
			foreach ($course_enrollments as $course_enrollment) {

				// Fetch user's other active enrollments
				$user_enrollments = bpm_get_user_active_enrollments($course_enrollment->id, $course_enrollment->userid);
				
				// Check if the user is a teacher
				$is_teacher = bpm_is_user_teacher_in_course($course->id, $course_enrollment->userid);

				// If no other active enrollments exist for user, suspend the enrollment.
				if (count($user_enrollments) == 0 || $is_teacher == true) {
					// $sf_enrollment_id = bpm_get_sf_enrollment_id($course_enrollment->userid, $course->id);
					// if ($sf_enrollment_id) {
					// 	$sf_access_data = bpm_get_sf_auth_data();
					// 	bpm_suspend_sf_enrollment($sf_access_data, $sf_enrollment_id);
					// }
					bpm_suspend_moodle_enrollment($course_enrollment->id, $course_enrollment->userid, $course->id);
				} else if (!$is_teacher) { //check if last enrollment isb4 control date
				    // echo PHP_EOL . 'userid: ' . $course_enrollment->userid  . PHP_EOL;
					$enrollments_sql = "SELECT ue.id, ue.status, c.id as courseid, c.enddate, se.access_rule_override_cutoff
                        	FROM mdl_user_enrolments ue, mdl_enrol e, mdl_course c, mdl_sf_enrollments se
                        	WHERE e.id = ue.enrolid
                        	AND e.courseid = c.id
                            AND se.userid = ue.userid
                            AND se.courseid = c.id
                        	AND   ue.userid = $course_enrollment->userid";
            		$last_course = 0;
            
            		$enrollments = $DB->get_records_sql($enrollments_sql);

            		foreach ($enrollments as $enrollment) {
            			if ($enrollment->enddate > $last_course) {
            				$last_course = $enrollment->enddate;
            			}
            			$bypass = false;
            			if ($enrollment->access_rule_override_cutoff != NULL &&
                            $enrollment->access_rule_override_cutoff >= time()) {
                                $bypass = true;
                                // echo PHP_EOL . 'bypassing, user ' . $course_enrollment->userid . ' in course ' . $enrollment->courseid . PHP_EOL . PHP_EOL;
                            }
            		}
            		if ($last_course < $course_date_control && !$bypass) {
                // 		echo PHP_EOL . PHP_EOL .'$course_date_control: '. date($course_date_control, 'Y-m-d') .PHP_EOL;
                // 		echo '$last_course: '. date($last_course, 'Y-m-d') .PHP_EOL;
            		  //  echo PHP_EOL . 'suspending user ' . $course_enrollment->userid . ' from course ' . $course->id . PHP_EOL;
				        bpm_suspend_moodle_enrollment($course_enrollment->id, $course_enrollment->userid, $course->id);
				    }
				}
			}
		}
	}
}

/**
 * Fetch all active enrollments of a specific course
 *
 * @param  int  $course_id  Course moodle id
 *
 * @global stdClass  $DB  The moodle database API
 *
 */
function bpm_get_course_active_enrollments($course_id) {
	global $DB;

	$sql = "SELECT ue.id, ue.userid
			FROM   mdl_enrol e, mdl_user_enrolments ue
			WHERE e.id = ue.enrolid
			AND   e.courseid = $course_id
			AND   ue.status = 0";

	return $DB->get_records_sql($sql);
}

/**
 * Fetch all active enrollments of a specific user, 
 * Which are different from the enrollment about to be suspended 
 *
 * @param  int  $enrollment_id  Enrollment moodle id
 * @param  int  $user_id  		User moodle id
 *
 * @global stdClass  $DB  The moodle database API
 *
 */
function bpm_get_user_active_enrollments($enrollment_id, $user_id) {
	global $DB;

	$sql = "SELECT ue.id, ue.status, e.courseid
			FROM   mdl_user_enrolments ue, mdl_enrol e
			WHERE e.id = ue.enrolid
			AND   ue.userid = $user_id
			AND   ue.status = 0
			AND   ue.id <> $enrollment_id";

	return $DB->get_records_sql($sql);
}

/**
 * Get teacher id of a specific course
 *
 * @param  int  $course_id  Course id
 *
 * @global stdClass  $DB  Moodle database api
 *
 * @return int  mdl_user.id  Teacher id
 *
  */
function bpm_is_user_teacher_in_course($course_id, $user_id) {
    global $DB;

    $sql = "SELECT COUNT(usr.id)
			FROM   mdl_course crs
			JOIN   mdl_context ctx         ON crs.id = ctx.instanceid
			JOIN   mdl_role_assignments ra ON ctx.id = ra.contextid
			JOIN   mdl_user usr            ON usr.id = ra.userid
			JOIN   mdl_role r              ON r.id   = ra.roleid
			WHERE crs.id 	 = $course_id
			AND   usr.id 	 = $user_id
			AND  (r.shortname = 'editingteacher' OR r.shortname = 'teacher')";

    $teacher_count = $DB->get_field_sql($sql);

	if ($teacher_count == 1) {
		return true;
	} else {
		return false;
	}
}

/**
 * Fetch salesforce enrollment id from the Moodle DB
 *
 * @param  int $user_id   User moodle id
 * @param  int $course_id Course moodle id
 *
 * @global stdClass $DB The moodle database API
 *
 * @return string The enrollment salesforce id
 */
function bpm_get_sf_enrollment_id($user_id, $course_id) {
	global $DB;

	$sql = "SELECT sfid
			FROM   mdl_sf_enrollments
			WHERE userid = $user_id
			AND   courseid = $course_id";

	$sf_enrollment_id = $DB->get_field_sql($sql);

	return isset($sf_enrollment_id) ? $sf_enrollment_id : false;
}

/**
 * Unsuspend users that have active enrollments
 *
 * @global stdClass  $DB  The moodle database API
 *
 */
function bpm_unsuspend_users() {
	global $DB;

	$users_sql = "SELECT id 
			FROM mdl_user u
			WHERE u.suspended = 1
 			AND   u.id IN (SELECT ue.userid
               	           FROM mdl_user_enrolments ue
               		       WHERE ue.status <> 1)";
	
	$users = $DB->get_records_sql($users_sql);

	foreach ($users as $user) {
		echo "<br> Unsuspending user: " . $user->id . "<br>";

		$update_sql = "UPDATE mdl_user
					   SET suspended = 0
					   WHERE id = $user->id";

		$DB->execute($update_sql);
	}
}

/**
 * Update enrollment status to suspended
 *
 * @param  int  $enrol_id  Enrollment moodle id
 *
 * @global stdClass  $DB  The moodle database API
 *
 */
function bpm_suspend_moodle_enrollment($enrol_id, $userid, $courseid) {
	global $DB;
    
    //check for overrides in sf_erollments table
    $sf_sql = "SELECT id, access_rule_override_cutoff FROM mdl_sf_enrollments
                    WHERE userid = $userid AND courseid = $courseid";
    $sf_enrollment = $DB->get_record_sql($sf_sql);
    if ($sf_enrollment->access_rule_override_cutoff != NULL &&
        $sf_enrollment->access_rule_override_cutoff >= time()) {
            return false;
    }
	$sql = "UPDATE mdl_user_enrolments 
			SET    status = 1
			WHERE id = $enrol_id";

	$DB->execute($sql);
}