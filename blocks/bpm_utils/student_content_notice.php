<?php

require_once __DIR__ . '/../../config.php';
require_once('config.php');

if (!empty($_POST)) {
    $course_id = $_POST["courseId"];
    $section_id = $_POST["sectionId"];

    $course_data = bpm_get_course_data($course_id);
    bpm_send_student_content_notice($course_data, $section_id);
}

function bpm_get_course_data($course_id) {
    global $DB;

    $sql = "SELECT usr.id as userid, c.fullname
            FROM mdl_course c
            JOIN mdl_context cx ON c.id = cx.instanceid
            JOIN mdl_role_assignments ra ON cx.id = ra.contextid
            JOIN mdl_role r ON ra.roleid = r.id
            JOIN mdl_user usr ON ra.userid = usr.id
            JOIN mdl_enrol e ON c.id = e.courseid
            JOIN mdl_user_enrolments ue ON e.id = ue.enrolid AND ue.userid = usr.id
            WHERE r.id = 5
            AND c.id = $course_id
            AND cx.contextlevel = '50' 
            AND ue.status = 0";

    $user_records = $DB->get_records_sql($sql);
    $course_data = new stdClass();
    $course_data->fullname = current($user_records)->fullname;
    $course_data->user_ids = [];
    $course_data->id = $course_id;
    foreach ($user_records as $current_record) {
        array_push($course_data->user_ids, $current_record->userid);
    }

    return $course_data;
}

function bpm_send_student_content_notice($course_data, $section_id) {
    global $BU_CFG;
    
    $response_url = new moodle_url('/course/view.php');
    $response_url = $response_url . '?id=' . $course_data->id . '#section-' . $section_id;

    // Template for BPM footer (no need for indentation)
    $footer = '<body><div dir="rtl" style="text-align:right;">
               <br><span style="font-style:italic">אין להשיב לדוא"ל זה</span><br><br> בברכה,<br>מכללת BPM
               <p><a href="http://www.bpm-music.com/" style="color:rgb(17,85,204);font-size:12.8px;text-align:right" target="_blank">BPM Website</a><span style="color:rgb(136,136,136);text-align:right;font-size:small">&nbsp;|&nbsp;</span>
               <font style="color:rgb(136,136,136);text-align:right;font-size:small" size="2">
               <a href="https://www.facebook.com/BPM.College" style="color:rgb(17,85,204);text-align:start" target="_blank">Facebook</a>&nbsp;
               <span style="color:rgb(0,0,0);text-align:start">|&nbsp;</span>
               <a href="https://instagram.com/bpmcollege/" style="color:rgb(17,85,204);text-align:start" target="_blank">Instagram</a>&nbsp;|&nbsp;
               <a href="https://soundcloud.com/bpmsoundschool" style="color:rgb(17,85,204)" target="_blank">Soundcloud</a>&nbsp;|&nbsp;
               <a href="https://www.youtube.com/user/BPMcollege" style="color:rgb(17,85,204)" target="_blank">Youtube</a></font></p>
               <img src="https://my.bpm-music.com/local/BPM_pix/footer2018/mail_sign_heb.png" style="border:none" width="500">
               </div></body>';

    // Build the message object
    $message = new \core\message\message();
    $message->courseid        = $course_data->id;
    $message->name            = 'student_notice_content_message';
    $message->component       = 'block_bpm_utils';
    $message->userfrom        = $BU_CFG->BPM_BOT_ID;
    $message->subject         = $course_data->fullname . ' - יחידת הוראה חדשה זמינה';
    $message->fullmessagehtml = '<p dir="rtl">למעבר אל היחידה החדשה בעמוד הקורס <a href=' . 
                                $response_url . '>לחץ כאן</a><br>' . $footer;
    $message->smallmessage    = 'חשיפת יחידה בקורס - ' . $course_data->fullname;

    foreach ($course_data->user_ids as $current_id) {
        $message->userto = $current_id;
        message_send($message);
    }
}