<?php
//die(); //comment out when testing
//define('CLI_SCRIPT', true);

//require_once('teacher_notice_engine.php');
//require_once('attendance_notice_engine.php');
//require_once('honor_student/honor_student_notice.php');

// $test_data = new stdclass();

// $test_data->course_id = 460;
// $test_data->teacher_data->id = 3;
// $test_data->teacher_data->firstname = 'ארז';
// $test_data->teacher_data->lastname = 'גורן';
// $test_data->type = 3;
// $test_data->assignment_id = 398;
// $test_data->count = 1;
// $test_data->course_name = "קורס בן לאב לבדיקה";
require_once('teacher_notice_engine.php');
require_once('attendance_notice_engine.php');
require_once('honor_student/honor_student_notice.php');

/*try {
    echo 'Run bpm_prepare_attendance_report<br>';
    bpm_prepare_attendance_report();
    print_r('bpm_prepare_attendance_report completed\n');
} catch(Exception $e) {
    echo 'fail bpm_prepare_attendance_report: ' .$e->getMessage();
}*/
// try {
//     echo 'Run bpm_send_ssd_attendance_report_mail<br>';
//     bpm_send_ssd_attendance_report_mail();
//     print_r('bpm_send_ssd_attendance_report_mail completed\n');
// } catch(Exception $e) {
//     echo 'fail bpm_send_ssd_attendance_report_mail: ' .$e->getMessage();
// }

// try {
//     echo 'Run bpm_process_teacher_notices<br>';
//     bpm_process_teacher_notices();
//     print_r('bpm_process_teacher_notices completed\n');
// } catch(Exception $e) {
//     echo 'fail bpm_process_teacher_notices: ' .$e->getMessage();
// }

try {
    echo 'Run bpm_process_attendance_notices<br>';
    bpm_process_attendance_notices();
    print_r('bpm_process_attendance_notices completed\n');
} catch(Exception $e) {
    echo 'fail bpm_process_attendance_notices: ' .$e->getMessage();
}

// try {
//     echo 'Run bpm_send_honor_student_pman_notices<br>';
//     bpm_send_honor_student_pman_notices();
//     print_r('bpm_send_honor_student_pman_notices completed\n');
// } catch(Exception $e) {
//     echo 'fail bpm_send_honor_student_pman_notices: ' .$e->getMessage();
// }
