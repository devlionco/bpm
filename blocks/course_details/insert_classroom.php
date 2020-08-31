<?php

require_once('../../config.php');
require_once(dirname(__FILE__) . '/classroom_form.php');
require_once(dirname(__FILE__) . '/../../lib/formslib.php');
require_once(dirname(__FILE__) . '/classroom_form.php');
global $USER, $DB, $OUTPUT, $CFG, $COURSE;

$courseid = $_GET['courseid'];
require_login();
$context = context_course::instance($courseid);

if(!has_capability('block/course_details:view', $context)){
	return null;
}

if($name = $DB->get_field('course', 'fullname', array('id' => $courseid)))
{
	$coursename = $name;
} else {
	$courseid = $COURSE->id;
	$coursename = $COURSE->fullname;
}

$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/course_details/js/course_details.js');
$PAGE->requires->strings_for_js(array('create_classroom_succsed',
										'remove_classroom_succsed',
										'update_classroom_succsed',
										'need_insert_all',
										'error'), 'block_course_details');
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_heading(get_string('course_details','block_course_details'));
$PAGE->set_title(get_string('course_details','block_course_details'));
$PAGE->set_url(new moodle_url('/blocks/course_details/insert_classroom.php'), $_GET);
$PAGE->navbar->add($coursename, new moodle_url('/course/view.php?id='.$courseid));
$PAGE->navbar->add(get_string('edit_classroom', 'block_course_details'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();
$mform = new classroom_form();
$mform->display();
echo $OUTPUT->footer();   


 