<?php

require_once('../../config.php');

if ($_POST['function'] == 'get_course_average') {
	get_course_average($_POST['courseid']);
}

function get_course_average($course_id) {
	global $DB;

	$sql = "SELECT AVG(grade)
			FROM mdl_sf_enrollments
			WHERE courseid = $course_id
			AND grade <> -1";

	$grade = $DB->get_field_sql($sql);

	echo round($grade, 1);
}