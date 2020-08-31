<?php

//define('CLI_SCRIPT', true);

require_once __DIR__ . '/../../config.php';
require_once($CFG->dirroot . '/lib/classes/user.php');
require_once(__DIR__ . '/classes/utils.php');
if (!isset($PHP_EOL)) {
    $PHP_EOL = "\n";
}
bpm_process_attendance_missed();

/**
 * Process absence notice for students for all sessions of the previous day
 *
 */
function bpm_process_attendance_missed() {
    global $DB;

    $yesterday = strtotime('6 days ago midnight');
    $today = strtotime('today midnight');
    $sessions_sql = "SELECT ass.id, a.course
                     FROM mdl_attendance_sessions ass,
                          mdl_attendance a,
                          mdl_attendance_log al
                     WHERE a.id = ass.attendanceid
                     AND   ass.id = al.sessionid
                     AND  ((ass.sessdate >= $yesterday AND  ass.sessdate < $today) 
                        OR (al.timetaken >= $yesterday AND al.timetaken < $today))";
                        
     $session_ids = $DB->get_records_sql($sessions_sql);

    echo "<pre>";
    foreach ($session_ids as $current_session_row) {
        
        
        $user_records = \utils::bpm_get_session_user_ids($current_session_row->id);
        var_dump($user_records);
        $course_id = $current_session_row->course;
        $course_parent_id = bpm_get_course_parent($course_id);

        foreach ($user_records as $record) {
            // echo $PHP_EOL . 'record: ' . $PHP_EOL;
            // var_dump($record);
            
            $user = core_user::get_user($record->studentid);
            echo 'user:::' . $PHP_EOL;
            var_dump($user);
            echo $PHP_EOL;
            echo 'user is:';
            var_dump($user);
            if ($course_parent_id == '01t24000003i55BAAQ') {
                echo 'current session row -> id: ' . $current_session_row->id;
              /*  if (bpm_check_electric_absence($record->studentid, $course_id, $current_session_row->id)) {
                    bpm_send_first_electric_absent($user, $course_id, $absence_count_result);
                }*/
            }
            $absence_count_result = \utils::bpm_count_consecutive_absence($record->studentid, $course_id);


            // If result is bigger than 6 it's the attendance session date instead
            if ($absence_count_result > 6) {
                echo 'bpm_send_first_class_absent(' . $user . ', ' . $course_id . ', '. $absence_count_result . ');';
                
            } else if ($absence_count_result == 2) {
                //ignore electric safety courses
                if ($course_parent_id != '01t24000003i55BAAQ') { //electric safety parent product
                    echo 'bpm_send_repeated_absent - user = ' . $user->id . ', course_id = ' . $course_id;
                    //bpm_send_repeated_absent($user, $course_id);
                }
            } else if ($absence_count_result == 6) {
                echo 'bpm_suspend_in_moodle('.$record->studentid . ',' . $course_id . ');';
                
            }
        }
    }
}

function bpm_check_electric_absence($user_id, $course_id, $session_id) {
    global $DB;

    $sql = "SELECT ases.id
            FROM mdl_attendance_log al, 
                 mdl_attendance_sessions ases,
                 mdl_attendance att,
                 mdl_attendance_statuses asts
            WHERE ases.id = al.sessionid
            AND   ases.attendanceid = att.id
            AND   asts.id = al.statusid
            AND   att.course = $course_id
            AND   al.studentid = $user_id
            AND   asts.acronym LIKE 'נע'";

    $absence_records = $DB->get_records_sql($sql);
    if (count($absence_records) == 1) {
        if ($absence_records[$session_id]->id == $session_id) {
            return true;
        }
    }

    return false;
}

function bpm_send_first_electric_absent($user, $course_id, $absence_count_result) {
    global $AN_CFG;

    // Get course name and convert timestamp to readable date
    $course_name = \utils::bpm_get_course_name($course_id);
    $attendance_date = date('d/m/y', $absence_count_result);

    // Combine student first and last name
    $student_name = $user->firstname . " " . $user->lastname;

    // Build the message object
    $message = new \core\message\message();
    $message->name            = 'notice_message';
    $message->component       = 'local_absencenotice';
    $message->userfrom        = $AN_CFG->BPM_BOT_ID;
    $message->userto          = $user->id;
    $message->courseid        = $course_id;
    $message->subject         = 'חיסור מקורס בטיחות בחשמל';
    $message->fullmessagehtml = "<p dir=\"rtl\">שלום " . $student_name . ",<br>" .
                                "במערכת שלנו נרשם כי החסרת מפגש בקורס בטיחות בחשמל.<br>" . 
                                "נבקש להזכיר שכדי לעבור את הקורס יש להשתתף בלפחות ארבעה מתוך חמשת המפגשים בקורס.<br>" . 
                                "בעת חיסור נוסף תיאבד זכאותך לתעודה, ותידרש הרשמה והשתתפות מהתחלה בקורס במועד חדש.<br>" .
                                "(יש באפשרותך להירשם עד ל-2 מועדי קורס שונים)</p>" . $AN_CFG->BPM_EMAIL_FOOTER;
    $message->smallmessage    = 'חיסור מקורס בטיחות בחשמל';

    $message_result = message_send($message);
    if (!$message_result) {
        \utils::bpm_log_error("failed to send absence message for $user->id in course $course_id");
    }

    $sms_message = "שלום " . $student_name . " שמנו לב שהחסרת מפגש בקורס בטיחות בחשמל." . 
        " לידיעתך על מנת לעבור את הקורס יש להשתתף בלפחות 4 מפגשים מתוך 5. אם ירשם חיסור נוסף תיאבד זכאותך לתעודה ותידרש השתתפות בקורס מהתחלה." . 
        " יש באפשרותך להירשם עד ל-2 מועדי קורס שונים";

    bpm_send_sms($user->phone2, $sms_message);
}

function bpm_send_first_class_absent($user, $course_id, $absence_count_result) {
    global $AN_CFG;

    // Get course name and convert timestamp to readable date
    $course_name = \utils::bpm_get_course_name($course_id);
    $attendance_date = date('d/m/y', $absence_count_result);

    // Combine student first and last name
    $student_name = $user->firstname . " " . $user->lastname;

    // Build the message object
    $message = new \core\message\message();
    $message->name            = 'notice_message';
    $message->component       = 'local_absencenotice';
    $message->userfrom        = $AN_CFG->BPM_BOT_ID;
    $message->userto          = $user->id;
    $message->courseid        = $course_id;
    $message->subject         = 'העדרותך משיעור ראשון בקורס';
    $message->fullmessagehtml = "<p dir=\"rtl\">שלום " . $student_name . ",<br>" .
                                "שמנו לב שלא הגעת לשיעור הראשון בקורס " . $course_name . " שהתקיים בתאריך " . $attendance_date .".<br>" .
                                "לבירורים ופרטים נוספים צור קשר עם מזכירות הלימודים בהקדם בטלפון 035604781.<br></p>" . $AN_CFG->BPM_EMAIL_FOOTER;
    $message->smallmessage    = 'העדרות משיעור ראשון בקורס ' . $course_name;

    $message_result = message_send($message);
    if (!$message_result) {
        \utils::bpm_log_error("failed to send absence message for $user->id in course $course_id");
    }
}

function bpm_send_repeated_absent($user, $course_id) {
    global $AN_CFG;
    
    // Check if case allready opened
    $case_opened = \utils::bpm_is_case_opened($user->id, $course_id);
    
    if (!$case_opened) {
    
        // Get course name
        $course_name = \utils::bpm_get_course_name($course_id);
        
        // Combine student first and last name
        $student_name = $user->firstname . " " . $user->lastname;

        // Build the message object
        $message = new \core\message\message();
        $message->name            = 'trice_notice_message';
        $message->component       = 'local_absencenotice';
        $message->userfrom        = $AN_CFG->BPM_BOT_ID;
        $message->userto          = $user->id;
        $message->courseid        = $course_id;
        $message->subject         = 'העדרות ממושכת מקורס';
        $message->fullmessagehtml = "<p dir=\"rtl\">שלום " . $student_name . ",<br>" .
                                    "שמנו לב שלא הגעת ל2 שיעורים ברצף בקורס " . $course_name . ".<br>" . 
                                    "במידה ותמשיך להחסיר שיעורים לא תוכל לעבור את הקורס.<br>" .
                                    "לבירורים ופרטים נוספים צור קשר עם מזכירות הלימודים בהקדם בטלפון 035604781.<br></p>";
        $message->smallmessage    = 'העדרות משלושה שיעורים ברצף בקורס ' . $course_name;
        
        //$message_result = message_send($message);
        if (!$message_result) {
          //  \utils::bpm_log_error("failed to send absence message for $user->id in course $course_id");
        }

        // Send a copy to the students services dept - Asked by ssd to remove because a case is opened in sf
        // $message->subject = 'העדרות ממושכת של ' . $student_name;
        // $message->fullmessagehtml = "<p dir=\"rtl\">" . $student_name . " נעדר במשך 2 מפגשים רצופים מקורס " . $course_name . ".</p>";
        // $message->userto = $AN_CFG->BPM_SSD_ID;
        // message_send($message);

        echo '$case_response = bpm_open_sf_case($user->idnumber, $course_id)';
        if (!isset($case_response['id'])) {
            \utils::bpm_log_error("failed to create absence case for $user->id in course $course_id");
        }
        echo 'bpm_mark_case_opened($user->id, $course_id)';
    } else {
        echo 'case exists for user ' . $user->id . ' in course ' . $course_id . $PHP_EOL;
    }
}

/**
 * Retrieve salesforce user id from the salesforce database via a the users 'idnumber'
 *
 * @param int $user_idnumber User's 'idnumber'
 * @param int $course_id      Course moodle id
 *
 * @global stdClass $TA_CFG Object containing config data
 *
 * @return int The sfid of the user
 */
function bpm_open_sf_case($user_idnumber, $course_id) {
    global $AN_CFG;
    
    echo 'creating case for user ' . $user_idnumber . ' in course ' . $course_id . $PHP_EOL;
    
    $sf_access_data = bpm_get_sf_auth_data();
    $account_sfid = bpm_get_user_sfid($sf_access_data, $user_idnumber);
    $course_sfid = bpm_get_course_sfid($sf_access_data, $course_id);
    $contact_sfid = bpm_get_contact_sfid($sf_access_data, $account_sfid);
    $course_name = \utils::bpm_get_course_name($course_id);

    $post_data = array(
        "AccountId" => $account_sfid,
        "Course__c" => $course_sfid,
        "ContactId" => $contact_sfid,
        "Subject" => '2 חיסורים מקורס ' . $course_name,
        "Status" => 'New',
        "Type" => 'חיסורים',
        "Origin" => 'Moodle',
        "OwnerId" => '0051p000009KQve'
    );
     
    $url = $sf_access_data['instance_url'] . $AN_CFG->BPM_SF_CASE_URL;
     
    $json_data = json_encode($post_data);

    $headers = array(
        "Authorization: OAuth " . $sf_access_data['access_token'],
        "Content-type: application/json"
    );

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);

    $json_response = curl_exec($curl);

    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ( $status != 201 ) {
        \utils::bpm_log_error("Error: call to URL $url failed with status $status, response *$json_response*, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
    }
     
    curl_close($curl);

    return json_decode($json_response, true);
}

/**
 * Retrieve Salesforce access token and instance (session) url
 *
 * @global stdClass $TA_CFG Object containing config data
 *
 * @return stdClass Object containing the sf connection data
 */
function bpm_get_sf_auth_data() {
    global $AN_CFG;

    $post_data = array(
        'grant_type'    => 'password',
        'client_id'     => $AN_CFG->BPM_SF_CLIENT_ID,
        'client_secret' => $AN_CFG->BPM_SF_USER_SECRET,
        'username'      => $AN_CFG->BPM_SF_USER_NAME,
        'password'      => $AN_CFG->BPM_SF_USER_PASS
    );
    
    $headers = array(
        'Content-type' => 'application/x-www-form-urlencoded;charset=UTF-8'
    );

    $curl = curl_init($AN_CFG->BPM_SF_ACCESS_URL);
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
 * Retrieve salesforce user id from the salesforce database via a the users 'idnumber'
 *
 * @param stdClass  $sf_access_data Object contaning salesforce access data
 *
 * @param int         $user_id_number The users 'Idnumber'
 *
 * @global stdClass $TA_CFG         Object containing config data
 *
 * @return int      The sfid of the user
 */
function bpm_get_user_sfid($sf_access_data, $user_id_number) {
    global $AN_CFG;

    $sql = "SELECT Id, Name 
            FROM Account 
            WHERE ID__c = '$user_id_number'";

    $url = $sf_access_data['instance_url'] . $AN_CFG->BPM_SF_QUERY_URL . urlencode($sql);

    $headers = array(
        "Authorization: OAuth " . $sf_access_data['access_token']
    );

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $json_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($json_response);

    return $response->records[0]->Id;
}


/**
 * Retrieve salesforce course id from the salesforce database via a moodle course id
 *
 * @param  stdClass  $sf_access_data    Object contaning salesforce access data
 * @param  int          $moodle_course_id  The moodle course id
 *
 * @global stdClass  $TA_CFG  Object containing config data
 *
 * @return int  The sfid of the course
 */
function bpm_get_course_sfid($sf_access_data, $moodle_course_id) {
    global $AN_CFG;

    $sql = "SELECT Id 
            FROM Course__c 
            WHERE Moodle_Course_Id__c = '$moodle_course_id'";

    $url = $sf_access_data['instance_url'] . $AN_CFG->BPM_SF_QUERY_URL . urlencode($sql);

    $headers = array(
        "Authorization: OAuth " . $sf_access_data['access_token']
    );

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $json_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($json_response);

    return $response->records[0]->Id;
}

/**
 * Retrieve salesforce contact id from the salesforce database via a sf account id
 *
 * @param  stdClass  $sf_access_data  Object contaning salesforce access data
 * @param  int          $sf_account_id   The sf account id
 *
 * @global stdClass  $TA_CFG  Object containing config data
 *
 * @return int  The sfid of the contact
 */
function bpm_get_contact_sfid($sf_access_data, $sf_account_id) {
    global $AN_CFG;

    $sql = "SELECT Id 
            FROM Contact 
            WHERE AccountId = '$sf_account_id'";

    $url = $sf_access_data['instance_url'] . $AN_CFG->BPM_SF_QUERY_URL . urlencode($sql);

    $headers = array(
        "Authorization: OAuth " . $sf_access_data['access_token']
    );

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $json_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($json_response);

    return $response->records[0]->Id;
}

/**
 * Update sf_case_opened to true in the mdl_sf_enrollments for a user in a course
 *
 * @param  int  $course_id  The course moodle id
 * @param  int  $user_id    The user moodle id
 *
 * @global stdClass  $DB  The moodle database API
 */
function bpm_mark_case_opened($user_id, $course_id) {
    global $DB;

    $sql = "UPDATE mdl_sf_enrollments se
            SET se.absent_case_open = 1
            WHERE se.userid = $user_id
            AND   se.courseid = $course_id";

    $DB->execute($sql);
}

/**
 * Update status of user in mdl_user_enrolments to 1
 *
 * @param  int  $course_id  The course moodle id
 * @param  int  $user_id    The user moodle id
 *
 * @global stdClass  $DB  The moodle database API
 */
function bpm_suspend_in_moodle($user_id, $course_id) {
    global $DB;

    $sql = "UPDATE mdl_user_enrolments ue, mdl_enrol e
            SET ue.status = 1
            WHERE ue.enrolid = e.id
            AND   e.courseid = $course_id
            AND   ue.userid = $user_id";

    $DB->execute($sql);
    echo 'user: ' . $user_id . ' suspended from course: ' . $course_id;
}

/**
 * Update registration status in salesforce
 *
 * @param  int      $user_id        The moodle user id
 * @param  int      $course_id      The moodle course id
 *
 * @global stdClass $AN_CFG Object containing config data
 * @global stdClass  $DB  The moodle database API
 */
function bpm_suspend_sf_enrollment($user_id, $course_id) {
    global $AN_CFG, $DB;

    $sf_access_data = bpm_get_sf_auth_data();
    $sf_enrollment_id = $DB->get_field('sf_enrollments', 'sfid', array('userid' => $user_id, 'courseid' => $course_id));

    $post_data = array(
        "Status__c" => 'Cancelled',
        "Entitlement__c" => 'Suspended'
    );

    $url = $sf_access_data['instance_url'] . $AN_CFG->BPM_SF_REGISTRATION_URL . $sf_enrollment_id;
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
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ( $status != 204 ) {
        \utils::bpm_log_error("Error: call to URL $url failed with status $status, " . 
                              "curl_error " . curl_error($curl) . 
                              ", curl_errno " . curl_errno($curl));
    }
    curl_close($curl);
}


/**
 * returns sfid of product
 *
 * @param  int  $course_id  The course moodle id
 *
 * @global stdClass  $DB  The moodle database API
 */
function bpm_get_course_parent($course_id) {
    global $DB;

    $sql = "SELECT coursefather
            FROM mdl_course_details
            WHERE courseid = '$course_id'";
            
    return $DB->get_record_sql($sql)->coursefather;
}

/**
 * Send SMS message using cellact
 *
 * @param  string  $recievers_string  A list of the sms recievers
 * @param  string  $message           The message content
 *
 * @global stdClass $BU_CFG Object containing config data
 */
function bpm_send_sms($recievers_string, $message) {
    global $AN_CFG;

    $url = $AN_CFG->CELLACT['ENDPOINT'] . 
           $AN_CFG->CELLACT['CREDENTIALS'] .
           urlencode($message) .
           $AN_CFG->CELLACT['RECIEVER'] .
           $recievers_string;

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
}