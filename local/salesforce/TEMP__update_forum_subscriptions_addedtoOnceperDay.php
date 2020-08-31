<?php
require_once 'config.php';
require_once __DIR__ . '/../../config.php';

// bpm_handle_student_forum_subscriptions();

/**
 * Select all non editing teachers forum ids (for the courses where they have that role)
 * and remove their forum subscriptions
 *
 * @global stdClass  $DB  Moodle DataBase API.
 *
 */
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