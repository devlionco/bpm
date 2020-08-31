<?php
require_once('../../config.php');
require_once('bpm_utils_form.php');
 
global $DB, $OUTPUT, $PAGE;
 
// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);
$blockid = required_param('blockid', PARAM_INT);
 
// Next look for optional variables.
$id = optional_param('id', 0, PARAM_INT);
 
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_bpm_utils', $courseid);
}
 
require_login($course);

$PAGE->set_url('/blocks/bpm_utils/view.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('edithtml', 'block_bpm_utils'));

$settingsnode = $PAGE->settingsnav->add(get_string('bpm_utilssettings', 'block_bpm_utils'));
$editurl = new moodle_url('/blocks/bpm_utils/view.php', array('id' => $id, 'courseid' => $courseid, 'blockid' => $blockid));
$editnode = $settingsnode->add(get_string('editpage', 'block_bpm_utils'), $editurl);
$editnode->make_active();

$bpm_utils = new bpm_utils_form();

$toform['blockid'] = $blockid;
$toform['courseid'] = $courseid;
$bpm_utils->set_data($toform);

// Cancelled forms redirect to the course main page.
if($bpm_utils->is_cancelled()) {
    $courseurl = new moodle_url('/course/view.php', array('id' => $id));
    redirect($courseurl);
    
// We need to add code to appropriately act on and store the submitted data
// but for now we will just redirect back to the course main page.    
} else if ($fromform = $bpm_utils->get_data()) {
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    print_object($fromform);

// form didn't validate or this is the first display
} else {
    $site = get_site();
	echo $OUTPUT->header();
	$bpm_utils->display();
	echo $OUTPUT->footer();
	$bpm_utils->display();
}