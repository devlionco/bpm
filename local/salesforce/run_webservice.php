<?php
// Check how the job was run
if ( isset($_GET['exec_mode']) && $_GET['exec_mode'] == 'user' ) {
	$exec_mode = 'user';
} else { 
    define('CLI_SCRIPT', true);
}

global $CFG, $sfsql;
require_once 'webservice.php';
require_once 'suspend_engine.php';
header('Content-type: text/plain');

     try {
            upsert_classes_to_moodle();
            print_r('upsert_classes_to_moodle completed\n');
        } catch(Exception $e) {
            echo 'fail upsert_classes_to_moodle: ' .$e->getMessage();
        }
        try {
            insert_objects_to_sf();
            print_r('insert_objects_to_sf completed\n');
        } catch(Exception $e) {
            echo 'fail insert_objects_to_sf: ' .$e->getMessage();
        }
        try {
            upsert_enrollments_to_moodle();
            print_r('upsert_enrollments_to_moodle completed\n');
        } catch(Exception $e) {
            echo 'fail upsert_enrollments_to_moodle: ' .$e->getMessage();
        }
        try {
            show_all_modules_course();
            print_r('show_all_modules_course completed\n');
        } catch(Exception $e) {
            echo 'fail show_all_modules_course: ' .$e->getMessage();
        }
        try {
            check_student_status_endcourse();
            print_r('check_student_status_endcourse completed\n');
        } catch(Exception $e) {
            echo 'fail check_student_status_endcourse: ' .$e->getMessage();
        }
        try {
            check_changes_in_student_status();
            print_r('check_changes_in_student_status completed\n');
        } catch(Exception $e) {
            echo 'fail check_changes_in_student_status: ' .$e->getMessage();
        }
        try{
            bpm_process_suspended_enrollments();
            print_r('bpm_process_suspended_enrollments completed\n');
        } catch(Exception $e) {
            print_r('fail bpm_process_suspended_enrollments: ' . $e->getMessage() . '\n');
        }
        try{
            bpm_suspend_expired_courses();
            print_r('bpm_suspend_expired_courses completed\n');
        } catch(Exception $e) {
            print_r('fail bpm_suspend_expired_courses: ' . $e->getMessage() . '\n');
        }
        try{
            bpm_unsuspend_users();
            print_r('bpm_unsuspend_users completed\n');
        } catch(Exception $e) {
            print_r('fail bpm_unsuspend_users: ' . $e->getMessage() . '\n');
        }
        try{
            bpm_sync_courses_from_sf();
            print_r('bpm_sync_courses_from_sf completed\n');
        } catch(Exception $e) {
            print_r('fail bpm_sync_courses_from_sf: ' . $e->getMessage() . '\n');
        }
        
        print_r('done\n');

// upsert_classes_to_moodle();
// echo '<pre dir=ltr style=text-align:left>' . print_r( 'upsert_classes_to_moodle completed' , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
// insert_objects_to_sf();
// echo '<pre dir=ltr style=text-align:left>' . print_r( 'insert_objects_to_sf completed' , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
// upsert_enrollments_to_moodle();
// echo '<pre dir=ltr style=text-align:left>' . print_r( 'upsert_enrollments_to_moodle completed' , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
// show_all_modules_course();
// echo '<pre dir=ltr style=text-align:left>' . print_r( 'show_all_modules_course completed' , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
// check_student_status_endcourse();
// echo '<pre dir=ltr style=text-align:left>' . print_r( 'check_student_status_endcourse' , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
// check_changes_in_student_status();
// echo '<pre dir=ltr style=text-align:left>' . print_r( 'check_changes_in_student_status completed' , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
// echo '<pre dir=ltr style=text-align:left>' . print_r( 'done' , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
