<?php
define('CLI_SCRIPT', true);

// Safety first!
parse_str($argv[1], $params);

if ($params['passcode'] != 'BPM_init' ) {
	die();
}

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

try {
    bpm_send_flow_request($S_CFG->BPM_SF_END_NOTIFICATION_FLOW_URL);
    print_r('BPM_SF_END_NOTIFICATION_FLOW_URL completed\n');
} catch(Exception $e) {
    echo 'fail BPM_SF_END_NOTIFICATION_FLOW_URL: ' .$e->getMessage();
}
/* request to remove by Aviv on 7/10/19
try {
    bpm_send_flow_request($S_CFG->BPM_SF_COURSE_START_MARKETING_SMS);
    print_r('BPM_SF_COURSE_START_MARKETING_SMS completed\n');
} catch(Exception $e) {
    echo 'fail BPM_SF_COURSE_START_MARKETING_SMS: ' .$e->getMessage();
}
 
try {
    bpm_send_flow_request($S_CFG->BPM_SF_COURSE_END_MARKETING_SMS);
    print_r('BPM_SF_COURSE_END_MARKETING_SMS completed\n');
} catch(Exception $e) {
    echo 'fail BPM_SF_COURSE_END_MARKETING_SMS: ' .$e->getMessage();
}

try {
    bpm_send_flow_request($S_CFG->BPM_SF_SEMESTER_START_MARKETING_SMS);
    print_r('BPM_SF_SEMESTER_START_MARKETING_SMS completed\n');
} catch(Exception $e) {
    echo 'fail BPM_SF_SEMESTER_START_MARKETING_SMS: ' .$e->getMessage();
}
*/

try {
    bpm_send_flow_request($S_CFG->BPM_SF_STUDENT_ACCOUNT_STATUS_FLOW_URL);
    print_r('BPM_SF_STUDENT_ACCOUNT_STATUS_FLOW_URL completed\n');
} catch(Exception $e) {
    echo 'fail BPM_SF_STUDENT_ACCOUNT_STATUS_FLOW_URL: ' .$e->getMessage();
}

try {
    bpm_send_flow_request($S_CFG->BPM_SF_END_FEEDBACK_INSTRUCTOR_URL);
    print_r('BPM_SF_END_FEEDBACK_INSTRUCTOR_URL completed\n');
} catch(Exception $e) {
    echo 'fail BPM_SF_END_FEEDBACK_INSTRUCTOR_URL: ' .$e->getMessage();
}

try {
    bpm_send_flow_request($S_CFG->BPM_SF_COURSE_START_TEAM_EMAIL);
    print_r('BPM_SF_COURSE_START_TEAM_EMAIL completed\n');
} catch(Exception $e) {
    echo 'fail BPM_SF_COURSE_START_TEAM_EMAIL: ' .$e->getMessage();
}

try {
    bpm_send_flow_request($S_CFG->BPM_SF_COURSE_START_INSTRUCTOR_EMAIL);
    print_r('BPM_SF_COURSE_START_INSTRUCTOR_EMAIL completed\n');
} catch(Exception $e) {
    echo 'fail BPM_SF_COURSE_START_INSTRUCTOR_EMAIL: ' .$e->getMessage();
}
try {
    bpm_send_flow_request($S_CFG->BPM_SF_COURSE_END_SALES_EMAIL);
    print_r('BPM_SF_COURSE_END_SALES_EMAIL completed\n');
} catch(Exception $e) {
    echo 'fail BPM_SF_COURSE_END_SALES_EMAIL: ' .$e->getMessage();
}
try {
    bpm_send_flow_request($S_CFG->BPM_CREATE_CONTINUING_STUDIES_TASKS_FOR_ADVISORS);
    print_r('BPM_CREATE_CONTINUING_STUDIES_TASKS_FOR_ADVISORS completed\n');
} catch(Exception $e) {
    echo 'fail BPM_CREATE_CONTINUING_STUDIES_TASKS_FOR_ADVISORS: ' .$e->getMessage();
}

try {
    bpm_update_program_course_grade();
    print_r('bpm_update_program_course_grade completed\n');
} catch(Exception $e) {
    echo 'fail bpm_update_program_course_grade: ' .$e->getMessage();
}

try {
    bpm_remove_teacher_forum_subscriptions();
    print_r('bpm_remove_teacher_forum_subscriptions completed\n');
} catch(Exception $e) {
    echo 'fail bpm_remove_teacher_forum_subscriptions: ' .$e->getMessage();
}

try {
    course_start_end_messages_exec();
    print_r('bpm_course_start_end_messages: completed\n');
} catch(Exception $e) {
    echo 'fail bpm_course_start_end_messages_exec(): ' .$e->getMessage();
}
try {
    bpm_create_acuity_certificates_for_new_students();
    print_r('bpm_create_acuity_certificates_for_new_students: completed\n');
} catch(Exception $e) {
    echo 'fail bpm_create_acuity_certificates_for_new_students: ' . $e->getMessage();
}
try {
    auto_certificates();
    print_r('auto_certificates completed\n');
} catch(Exception $e) {
    echo 'fail auto_certificates: ' .$e->getMessage();
}

try {
    bpm_handle_student_forum_subscriptions();
    print_r('bpm_handle_student_forum_subscriptions completed\n');
} catch(Exception $e) {
    echo 'fail bpm_handle_student_forum_subscriptions: ' .$e->getMessage();
}

try {
    bpm_check_debt_status_in_sf();
    print_r('bpm_check_debt_status_in_sf completed\n');
} catch(Exception $e) {
    echo 'fail bpm_check_debt_status_in_sf: ' .$e->getMessage();
}
print_r('done\n');
