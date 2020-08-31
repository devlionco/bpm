<?php
//die(); //- Comment out this line while testing
// This is a test file for running diferent scripts in the local/salesforce folder.
require_once 'once_per_day_jobs.php';
require_once('course_start_end_forum_messages/course_start_end_messages.php');
require_once 'student_auto_certificate.php';
require_once 'config.php';

header('Content-type: text/plain');
echo 'hey';
//global $CFG, $sfsql;
//require_once 'config.php';
//require_once 'once_per_day_jobs.php';
// global $CFG, $sfsql;
// require_once 'student_auto_certificate.php';
// require_once __DIR__ . '/../../config.php';
// require_once 'webservice_test.php';
// header('Content-type: text/plain');

// try {
//     upsert_enrollments_to_moodle();
//     print_r('upsert_enrollments_to_moodle completed\n');
// } catch(Exception $e) {
//     echo 'execption' . PHP_EOL;
//     var_dump($e);
//     echo 'fail upsert_enrollments_to_moodle: ' .$e->getMessage();
// }


try {
    bpm_check_debt_status_in_sf();
    print_r('bpm_check_debt_status_in_sf completed\n');
} catch(Exception $e) {
    echo 'fail bpm_check_debt_status_in_sf: ' .$e->getMessage();
}

print_r('done\n');
