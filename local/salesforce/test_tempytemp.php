<?php

// global $CFG, $sfsql;
// require_once 'webservice.php';
// header('Content-type: text/plain');

//      try {
//             upsert_enrollments_to_moodle();
//             print_r('upsert_enrollments_to_moodle completed\n');
//         } catch(Exception $e) {
//             echo 'fail upsert_enrollments_to_moodle: ' .$e->getMessage();
//         }
        
require_once 'once_per_day_jobs.php';
require_once('course_start_end_forum_messages/course_start_end_messages.php');
require_once 'student_auto_certificate.php';
require_once 'config.php';

header('Content-type: text/plain');

try {
    bpm_send_flow_request($S_CFG->BPM_SF_END_FEEDBACK_FLOW_URL);
    print_r('BPM_SF_END_FEEDBACK_FLOW_URL completed\n');
} catch(Exception $e) {
    echo 'fail BPM_SF_END_FEEDBACK_FLOW_URL: ' .$e->getMessage();
}