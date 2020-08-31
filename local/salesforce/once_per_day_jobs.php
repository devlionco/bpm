<?php

require_once 'config.php';
require_once __DIR__ . '/../../config.php';

/**
 * Send a request to run a flow in salesforce
 *
 * @param string $flow_url String containing the flow url
 */
function bpm_send_flow_request($flow_url) {
	$sf_access_data = bpm_get_sf_auth_data();
	$url = $sf_access_data['instance_url'] . $flow_url;

	$headers = array(
 		"Authorization: OAuth " . $sf_access_data['access_token'],
     	"Content-type: application/json"
 	);
 	
 	$body = '{"inputs":[{}]}';

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
	$response = curl_exec($curl);

	curl_close($curl);
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
 * Update calculated average grade for program general courses that ended 2 months ago
 *
 * @global stdClass $DB Moodle DataBase API.
 *
 */
function bpm_update_program_course_grade() {
	global $DB;
	
	$sql = "SELECT c.id, c.category, c.fullname
			FROM mdl_course c
			WHERE FROM_UNIXTIME(c.enddate, '%Y-%m-%d') = DATE_SUB(DATE_SUB(CURDATE(), INTERVAL 2 MONTH), INTERVAL 1 DAY)";

	$course_records = $DB->get_records_sql($sql);

	foreach ($course_records as $current_course) {
		if (bpm_is_program_type($current_course->fullname)) {
			bpm_update_program_students_grade($current_course->category, $current_course->id);
		}
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
        if (strpos($course_name, $program_names[$name_index]) !== false && 
        	strpos($course_name, 'כללי') !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Update a general program course grade in mdl_sf_enrollments per student in that program
 * The grade is calculated from the students final grades in course with the same category
 *
 * @global stdClass  $DB  Moodle DataBase API.
 *
 * @param  int  $category  	  The course category
 * @param  int  $course_name  The course fullname
 *
 */
function bpm_update_program_students_grade($category, $course_id) {
	global $DB;

	$sql = "SELECT se.userid, avg(grade) as grade
			FROM mdl_sf_enrollments se
			WHERE grade <> -1 
			AND se.courseid IN (SELECT c.id
                    			FROM mdl_course c
                    			WHERE c.category = $category)
			AND se.userid IN (SELECT se2.userid
                  			  FROM mdl_sf_enrollments se2
                  			  WHERE se2.courseid = $course_id)
			GROUP BY se.userid
			ORDER BY se.userid ASC";

	$students_records = $DB->get_records_sql($sql);

	foreach ($students_records as $current_student) {
		$update_sql = "UPDATE mdl_sf_enrollments
					   SET grade = $current_student->grade
					   WHERE userid = $current_student->userid
					   AND   courseid = $course_id";
		$DB->execute($update_sql);
	}
}

/**
 * Select all non editing teachers forum ids (for the courses where they have that role)
 * and remove their forum subscriptions
 *
 * @global stdClass  $DB  Moodle DataBase API.
 *
 */
function bpm_remove_teacher_forum_subscriptions() {
	global $DB;
	$delete_string = '';

	$sql = "SELECT fs.id
			FROM mdl_user u,
     			 mdl_context c,
     			 mdl_role r,
	 			 mdl_role_assignments ra,
     			 mdl_forum f,
     			 mdl_forum_subscriptions fs
			WHERE u.id = ra.userid
			AND r.id = ra.roleid 
			AND c.id = ra.contextid
			AND f.id = fs.forum
			AND u.id = fs.userid
			AND c.instanceid = f.course 
			AND r.shortname = 'teacher'";

	$forum_subs = $DB->get_records_sql($sql);

	if (count($forum_subs) > 0) {
		foreach ($forum_subs as $curr_sub) {
			$delete_string = $delete_string . $curr_sub->id . ',';
		}

		$delete_string = rtrim($delete_string, ',');

		$delete_sql = "DELETE FROM mdl_forum_subscriptions
					   WHERE id IN (" . $delete_string . ")";

		$DB->execute($delete_sql);
	}
}

function bpm_create_acuity_certificates_for_new_students() {
    global $DB;

    //get unique emails for students of DJ courses that start today
    $sql = "SELECT DISTINCT usr.email, CONCAT(usr.firstname, ' ', usr.lastname, ' - ', usr.username) as usernamestring, c.shortname
            FROM mdl_course c
            INNER JOIN mdl_context cx ON c.id = cx.instanceid
            AND cx.contextlevel = '50' " .
            
            //temp, debug for specific course - comment out the next line and uncomment the one after it
            "AND c.id IN (SELECT id FROM mdl_course WHERE  from_unixtime(startdate, '%d/%m/%Y') = from_unixtime(UNIX_TIMESTAMP(), '%d/%m/%Y'))" .
            //"AND c.id = 983 " .
            
            "INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid
            INNER JOIN mdl_role r ON ra.roleid = r.id
            INNER JOIN mdl_user usr ON ra.userid = usr.id
            INNER JOIN mdl_enrol e ON e.courseid = c.id
            INNER JOIN mdl_user_enrolments ue ON e.id = ue.enrolid AND ue.userid = usr.id
            WHERE r.id = 5 AND ue.status != 1
            AND c.shortname LIKE '%DJ%'
            AND c.shortname NOT LIKE '%אונליין%'";
    $emails = $DB->get_records_sql($sql);
    
    if (count($emails) <= 0) {
        echo 'no records';
        
        return false;
    }
    $dj_product_code = 592240;
    $log_list = '';
    foreach($emails as $email) {
        for ($i = 0; $i < 3; $i++) {
           bpm_create_acuity_certificate($dj_product_code, $email->email);
        }
        $log_list .= 'קורס ' . $email->shortname . ' - ' . $email->usernamestring . ' - ' . $email->email . "<br>"; 
    }
    pass_log_to_front_desk($log_list);
    
}

function bpm_create_acuity_certificate($product_id, $user_email) {
    global $S_CFG;
    
    
    $url = 'https://acuityscheduling.com/api/v1/certificates';

    $headers = array("Content-type: application/json");
    
    $body = "{\"productID\":\"" . $product_id . "\"," .
             "\"email\":\"" . $user_email . "\"}";

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_USERPWD, $S_CFG->ACUITY_PASS . ":" . $S_CFG->ACUITY_USER);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    $response = curl_exec($curl);

    //$response_array = json_decode($response);

    curl_close($curl);
    $output = json_encode($response);
    return $output;
}

function pass_log_to_front_desk($html_string) {
    
    $message = '<div dir="rtl">';
    $message .= $html_string . "</div>";
    
    $message .= BPM_EMAIL_FOOTER;
    $heb_date = date("d/m/Y");
    $to = 'mazkirut@bpm-music.com';
	$subject = "פירוט פתיחת קופונים אוטומטית לתאריך: " . $heb_date;
	$headers  = "To: " . $to . " <" . $to . ">"     . "\r\n";
    $headers = "From: מכללת BPM <noreply@bpm-music.com>"     . "\r\n";
    $headers .= "MIME-Version: 1.0"                           . "\r\n";
    $headers .= "Content-Type: text/html; charset=iso-8859-1" . "\r\n";

    $response = mail($to, $subject, $message, $headers);
	
	if($response) {
        return 'all good';
    } else {
        return 'error';
    };
}

function bpm_handle_student_forum_subscriptions() {
	global $DB;

    //get recently edited enrolments
    $sql = "SELECT ue.id ,ue.status, ue.userid, ue.enrolid, e.courseid
            FROM mdl_user_enrolments ue,
                mdl_enrol e
            WHERE e.id = ue.enrolid
                AND ue.timemodified > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY))";
    //test
    // $testing_sql = "SELECT ue.id ,ue.status, ue.userid, ue.enrolid, e.courseid
    //         FROM mdl_user_enrolments ue,
    //             mdl_enrol e
    //         WHERE e.id = ue.enrolid AND e.courseid = 608 AND ue.userid IN (363, 222)";
    $ue_records = $DB->get_records_sql($sql);
    
    foreach ($ue_records as $ue_record) {
       handle_course_subscriptions($ue_record->userid, $ue_record->courseid, $ue_record->status);
       echo PHP_EOL;
    }
}

function handle_course_subscriptions($userid, $courseid, $enrollment_status) {
    global $DB;
    
    // echo 'courseid: ' . $courseid  .PHP_EOL;
    // echo 'userid: ' . $userid . PHP_EOL;
    // echo 'status: ' . $enrollment_status . PHP_EOL;
    
    //get forums ids from course;
    $sql = "SELECT id, name FROM mdl_forum WHERE course = $courseid";
    $forums = $DB->get_records_sql($sql);
    foreach($forums as $forum) {
        $record_array = array('userid' => $userid, 'forum' => $forum->id);
        $existing_sub = $DB->get_record('forum_subscriptions', $record_array);
        
        if ($existing_sub && $enrollment_status == '1') {//user is suspended, need to delete sub
            echo 'removing ' . $userid . ' from  forum in course ' . $courseid . PHP_EOL;
            var_dump($DB->delete_records('forum_subscriptions', (array) $existing_sub));
        } else if (!$existing_sub && $enrollment_status == '0') {//enrolment is active, need to add sub
            $DB->insert_record('forum_subscriptions', $record_array);
            return 'added ' . $userid . ' to forum in course ' . $courseid . PHP_EOL;
        } else {
            //echo 'nothing to do';
        }
    }
}

function bpm_check_debt_status_in_sf() {
	global $BPM_CFG;
    
    // echo "<pre>";
	//check existing debt records
    $existing_debtors = get_existing_debtors();
	$debtor_ids_array = array();
    foreach($existing_debtors as $account) {
		array_push($debtor_ids_array, $account->sf_id);
    }
	$debtor_ids_string = implode("', '", $debtor_ids_array);
	$current_debt_sql = "SELECT Id, Balance__c FROM Account WHERE Id IN ('$debtor_ids_string')";
	$current_debt = sf_curl_sql($current_debt_sql);
	
	foreach($current_debt->records as $debt_row) {
	   // var_dump($debt_row);
		if ($debt_row->Balance__c <= 0) { //debt is settled
// 			echo 'updating debt ' . $debt_row->Balance__c . ' for account ' . $debt_row->Id . PHP_EOL;
			update_debt_value($debt_row->Id, NULL);
		} else {
// 			echo 'updating debt ' . $debt_row->Balance__c . ' for account ' . $debt_row->Id . PHP_EOL;
			update_debt_value($debt_row->Id, $debt_row->Balance__c);
		}
	}
	
	update_new_debts_from_sf($debtor_ids_string);
}

function get_existing_debtors() {
    global $DB;
    
    $sql = "SELECT id, sf_id, debt FROM mdl_sf_user_data WHERE debt > 0";
    return $DB->get_records_sql($sql);
}

function update_debt_value($sf_id, $value) {
	global $DB;
    
    $sql = "UPDATE mdl_sf_user_data SET debt = $value WHERE sf_id = '$sf_id'";
	return $DB->execute($sql);
}

function update_new_debts_from_sf($existing_records) {
    global $sfsql, $DB;
	
	$new_debtors_sql ="SELECT Id, Balance__c FROM Account WHERE Id NOT IN ('$existing_records') AND Moodle_User_Id__c  != '' AND Balance__c > 0";
	
	$new_debtors = sf_curl_sql($new_debtors_sql);
	foreach ($new_debtors->records as $sf_new_debt_row) {
// 		echo 'updating debt ' . $sf_new_debt_row->Balance__c . ' for account ' . $sf_new_debt_row->Id . PHP_EOL;
		update_debt_value($sf_new_debt_row->Id, $sf_new_debt_row->Balance__c);
	}
}

function sf_curl_sql($query) {
    global $BPM_CFG;
    // require_once __DIR__ . '/../../config.php';
    
	$sf_access_data = get_sf_auth_data();  
	$url = $sf_access_data['instance_url'] . $BPM_CFG->SF_QUERY_URL . urlencode($query);
    $headers = array(
        "Authorization: OAuth " . $sf_access_data['access_token']
    );
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $json_response = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($json_response);
    if ($sf_response_data->errorCode == 'REQUEST_LIMIT_EXCEEDED') {
        echo 'error';
    } else {
        echo $response->records[0]->Name . ',' . $response->records[0]->Id;
    }
    
	return $response;
}

function get_sf_auth_data() {
    global $BPM_CFG;
   
  $post_data = array(
        'grant_type'    => 'password',
        'client_id'     => $BPM_CFG->SF_OAUTH_KEY,
        'client_secret' => $BPM_CFG->SF_CLIENT_SECRET,
        'username'      => $BPM_CFG->SF_USER_NAME,
        'password'      => $BPM_CFG->SF_PASS
  );
  $headers = array(
      'Content-type' => 'application/x-www-form-urlencoded;charset=UTF-8'
  );
    
  $curl = curl_init($BPM_CFG->SF_ACCESS_URL);
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