<?php
require_once('../../config.php');
require_once('config.php');
require_once('cert_download_form.php');
 
global $DB, $OUTPUT, $PAGE;

// Check for all required variables.
$course_parent_id = required_param('courseparentid', PARAM_INT);
$course_id = required_param('courseid', PARAM_INT);
$user_id = required_param('userid', PARAM_INT);
$course_type = required_param('coursetype', PARAM_INT);

$branch = $_GET['branch'];


echo "<script>console.log('branch is  " . $branch . "');</script>";
if (!$course = $DB->get_record('course', array('id' => $course_id))) {
    print_error('invalidcourse', 'block_bpm_utils', $course_id);
}

$page_parameters = array('courseparentid' => $course_parent_id,
                         'courseid' => $course_id, 
                         'userid' => $user_id,
                         'branch' => $branch);

require_login($course);

$PAGE->set_url('/blocks/bpm_utils/cert_download_view.php', $page_parameters);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('certdownloadtitle', 'block_bpm_utils'));
$PAGE->set_title(get_string('certdownloadtitle', 'block_bpm_utils'));

$settingsnode = $PAGE->settingsnav->add(get_string('bpm_utilssettings', 'block_bpm_utils'));
$cert_download_url = new moodle_url('/blocks/bpm_utils/cert_download_view.php', $page_parameters);
$editnode = $settingsnode->add(get_string('certdownloadtitle', 'block_bpm_utils'), $cert_download_url);
$editnode->make_active(0);

$cert_type;
if (substr_count($course->fullname, 'אבלטון') !== 0) {
    $cert_type = 'ableton';
} else if (substr_count($course->fullname, 'קיובייס') !== 0) {
    $cert_type = 'cubase';
}

$cert_url = new moodle_url('/blocks/bpm_utils/cert_view.php', array('courseparentid' => $course_parent_id,
                                                                    'courseid' => $course_id,
                                                                    'userid' => $user_id));
$ssd_url = new moodle_url('/blocks/bpm_utils/cert_view.php', array('courseparentid' => $course_parent_id,
                                                                   'courseid' => $course_id,
                                                                   'userid' => $user_id,
                                                                   'ssdrequest' => true,
                                                                   'branch' => $branch));

$page_parameters['cert_url'] = $cert_url;
$page_parameters['ssd_url'] = $ssd_url;
$page_parameters['cert_type'] = $cert_type;
$page_parameters['course_type'] = $course_type;


$cert_download_form = new cert_download_form($cert_download_url, $page_parameters);

// Cancelled forms redirect to the course main page.
if($cert_download_form->is_cancelled()) {
    print_object($cert_download_form);
    
// We need to add code to appropriately act on and store the submitted data
// but for now we will just redirect back to the course main page.    
} else if ($fromform = $cert_download_form->get_data()) {
    redirect($cert_download_url);

// form didn't validate or this is the first display
} else {
    $site = get_site();
    echo $OUTPUT->header();
    $cert_download_form->display();
    echo $OUTPUT->footer();
}