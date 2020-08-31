<?php
require_once('../../../config.php');
require_once('../config.php');
require_once('extension_request_form.php');
 
global $DB, $OUTPUT, $PAGE;
 
// Check for all required variables.
$course_id = required_param('courseid', PARAM_INT);
$user_id = required_param('userid', PARAM_INT);
$assignment_id = required_param('assignmentid', PARAM_INT);
 
if (!$course = $DB->get_record('course', array('id' => $course_id))) {
    print_error('invalidcourse', 'block_bpm_utils', $course_id);
}

$page_parameters = array('courseid' => $course_id, 
                         'userid' => $user_id,
                         'assignmentid' => $assignment_id);

require_login($course);

$PAGE->set_url('/blocks/bpm_utils/extension/extension_request_view.php', $page_parameters);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('extensionreqtitle', 'block_bpm_utils'));

$settingsnode = $PAGE->settingsnav->add(get_string('bpm_utilssettings', 'block_bpm_utils'));
$extension_url = new moodle_url('/blocks/bpm_utils/extension/extension_request_view.php', $page_parameters);
$editnode = $settingsnode->add(get_string('extensioncrumbs', 'block_bpm_utils'), $extension_url);
$editnode->make_active(0);

$course_data = bpm_get_course_data($course_id);
$user_data = bpm_get_user_data($user_id);
$assignment_data = bpm_get_assignment_data($assignment_id);

$extension_request_form = new extension_request_form($extension_url, array(
                                                                    'coursename' => $course_data->fullname,
                                                                    'assignmentname' => $assignment_data->name,
                                                                    'assigndate' => $assignment_data->duedate));

// Cancelled forms redirect to the course main page.
if($extension_request_form->is_cancelled()) {
    $course_url = new moodle_url('/course/view.php', array('id' => $course_id));
    redirect($course_url);
    
// We need to add code to appropriately act on and store the submitted data
// but for now we will just redirect back to the course main page.    
} else if ($fromform = $extension_request_form->get_data()) {
    bpm_send_request($course_data, $user_data, $assignment_data, $fromform->requesteddate, $fromform->requestreasontext);
    $postpone_success_url = new moodle_url('/blocks/bpm_utils/extension/request_success_view.php', array('courseid' => $course_id));
    redirect($postpone_success_url);

// form didn't validate or this is the first display
} else {
    $site = get_site();
    echo $OUTPUT->header();
    $extension_request_form->display();
    echo $OUTPUT->footer();
}

// function bpm_process_request($course_id, $user_id, $assignment_id, $requested_date) {
//     global $DB;

//     if (!$DB->get_record('time_extensions', array('userid' => $user_id, 
//                                                   'courseid' => $course_id,
//                                                   'assignmentid' => $assignment_id,
//                                                   'date' => $requested_date))) {
//         $new_request = new stdClass();
//         $new_request->userid = $user_id;
//         $new_request->courseid = $course_id;
//         $new_request->assignmentid = $assignment_id;
//         $new_request->date = $requested_date;

//         return $DB->insert_record('time_extensions', $new_request, true);
//     }
// }

function bpm_send_request($course_data, $user_data, $assignment_data, $request_date, $request_reason) {
    global $BU_CFG;

    $response_params = array('courseid' => $course_data->id, 
                             'userid' => $user_data->id,
                             'assignmentid' => $assignment_data->id,
                             'requesteddate' => $request_date);
    $response_url = new moodle_url('/blocks/bpm_utils/extension/extension_response_view.php', $response_params);
    
    $request_date = date('d/m/Y', $request_date);

    // Build the message object
    $message = new \core\message\message();
    $message->courseid        = $course_data->id;
    $message->name            = 'extension_request_message';
    $message->component       = 'block_bpm_utils';
    $message->userfrom        = $BU_CFG->BPM_BOT_ID;
    $message->userto          = $BU_CFG->BPM_TE;
    $message->subject         = 'בקשה להארכת זמן - ' . $user_data->name;
    $message->fullmessagehtml = "<p dir=\"rtl\"><b>שם הקורס:</b> " . $course_data->fullname . "<br>" . 
                                "<b>שם המטלה:</b> " . $assignment_data->name . "<br><br>" . 
                                "<b>תאריך הגשה מקורי:</b> " . $assignment_data->duedate . "<br>" . 
                                "<b>תאריך הגשה רצוי:</b> " . $request_date . "<br><br>" . 
                                "<b>סיבת הבקשה:</b> " . $request_reason . "<br><br>" .
                                "<a href=" . $response_url . ">לטופס אישור/דחיית בקשה לחצו כאן</a></p>";
    $message->smallmessage    = 'בקשה להארכת זמן מ' . $user_data->name;

    $result = message_send($message);
}

function bpm_get_course_data($course_id) {
    global $DB;

    $sql = "SELECT id, fullname
            FROM mdl_course
            WHERE id = $course_id";
            
    return $DB->get_record_sql($sql);
}

function bpm_get_user_data($user_id) {
    global $DB;

    $sql = "SELECT id, firstname, lastname
            FROM mdl_user
            WHERE id = $user_id";

    $record = $DB->get_record_sql($sql);
    $record->name = $record->firstname . " " . $record->lastname;

    return $record;
}

function bpm_get_assignment_data($assignment_id) {
    global $DB;

    $sql = "SELECT id, name, duedate
            FROM mdl_assign
            WHERE id = $assignment_id";
            
    $record = $DB->get_record_sql($sql);
    $record->duedate = date('d/m/Y', $record->duedate);
    return $record;
}

function bpm_prepare_and_send_postpone_sms($course_id, $message) {
    global $DB, $BU_CFG;

    $student_numbers_sql = "SELECT usr.id, usr.phone2
                            FROM mdl_course c
                            INNER JOIN mdl_context cx ON c.id = cx.instanceid
                            INNER JOIN mdl_role_assignments ra ON cx.id = ra.contextid
                            INNER JOIN mdl_role r ON ra.roleid = r.id
                            INNER JOIN mdl_user usr ON ra.userid = usr.id
                            AND cx.contextlevel = '50' 
                            AND c.id = $course_id
                            WHERE r.id = 5";

    $number_records = $DB->get_records_sql($student_numbers_sql);
    $reciever_string = "";

    foreach ($number_records as $current_record) {
        $reciever_string .= $current_record->phone2 . ',';
    }
    $reciever_string = substr($reciever_string, 0, -1);

    bpm_send_sms($reciever_string, $message);
}

function bpm_send_sms($recievers_string, $message) {
    global $BU_CFG;

    $url = $BU_CFG->CELLACT['ENDPOINT'] . 
           $BU_CFG->CELLACT['CREDENTIALS'] .
           urlencode($message) .
           $BU_CFG->CELLACT['RECIEVER'] .
           $recievers_string;

    $curl = curl_init($url);
     curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
}