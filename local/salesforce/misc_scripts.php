<?php
die(); // Comment out this line while testing
require_once __DIR__ . '/../../config.php';

function bpm_populate_sf_account_link_in_users() {
    global $DB;

    $sf_account_sql = "SELECT id, user_id, sf_id
				       FROM mdl_sf_user_data
				       WHERE sf_id <> ''";

    $sf_account_records = $DB->get_records_sql($sf_account_sql);

    foreach ($sf_account_records as $current_record) {
        
        $sql = "SELECT COUNT(id)
                FROM   mdl_user_info_data
                WHERE  userid = ?
                AND    fieldid = 7";
        $count_result = $DB->get_field_sql($sql, array($current_record->user_id));    

        if ($count_result == 0) {
    	    $url = "<p><a target=\"_blank\" href=\"https://eu5.salesforce.com/" . $current_record->sf_id . "\"><img src=\"https://eu5.salesforce.com/favicon.ico\"></a></p>";
            $user_info_data_record = array(
                'fieldid' => 7,
                'userid'  => $current_record->user_id,
                'data'    => $url);
    
            $insert_result = $DB->insert_record('user_info_data', $user_info_data_record, false);
        }
    }
}

function temp_bpm_update_courses_dates_in_sf() {
    global $DB;

    $sql = "SELECT id, startdate, enddate
            FROM mdl_course
            WHERE enddate > UNIX_TIMESTAMP()";
    $courses = $DB->get_records_sql($sql);
    $course_sObjects = array();

    foreach ($courses as $course) {
        $startdate = date('c', $course->startdate);
        $enddate = date('c', $course->enddate);
        $course_sObject = new sObject();
        $course_sObject->type = 'Course__c';
        $course_sObject->fields = array(
            'Moodle_Course_Id__c' => $course->id,
            'Starts_On__c' => $startdate,
            'End_Date__c' => $enddate
        );
        array_push($course_sObjects, $course_sObject);
    }
    bpm_update_array_to_sf($course_sObjects, true);
}

function temp_bpm_update_sf_account_with_moodle_ids() {
    global $DB, $sfsql;

    $sql = "SELECT user_id, sf_id
            FROM mdl_sf_user_data
            WHERE sf_id <> ''
            LIMIT 800, 200";
    $user_ids_records = $DB->get_records_sql($sql);

    echo count($user_ids_records);

    $sObjects = array();

    foreach ($user_ids_records as $current_record) {
        $sObject = new sObject();
        $sObject->type = 'Account';
        $sObject->fields = array('Id' => $current_record->sf_id,
                                 'Moodle_User_Id__c' => $current_record->user_id
                                ); 
        array_push($sObjects, $sObject);
    }

    $result = $sfsql->update($sObjects);
}