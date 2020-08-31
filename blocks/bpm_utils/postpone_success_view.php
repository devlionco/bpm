<?php
require_once('../../config.php');

global $PAGE;

// Check for all required variables.
$course_id = required_param('courseid', PARAM_RAW);

require_login();

$context = context_course::instance($course_id);
$PAGE->set_context($context);

$course_url = new moodle_url('/course/view.php', array('id' => $course_id));
$PAGE->set_url('/blocks/bpm_utils/cert_view.php', array('id' => $course_id));
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading('BPM Moodle');

echo $OUTPUT->header();
echo "<div style=\"text-align:center\"><h3>ביטול המפגש התבצע בהצלחה</h3> <br><br> <a href=\"$course_url\">לחץ כאן כדי לחזור לעמוד הקורס</a></div>";
echo $OUTPUT->footer();