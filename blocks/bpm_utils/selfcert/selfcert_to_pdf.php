<?php

require_once('../../../config.php');
require('../vendor/autoload.php');

use mikehaertl\wkhtmlto\Pdf;
global $DB;

if (isset($_GET['userId'])) {
	$cert_data = (object) array('user_id' => $_GET['userId'],
		'user_english' => $_GET['userEng'],
		'user_hebrew' => $_GET['userHeb'],
		'course_english' => $_GET['courseEng'],
		'course_hebrew' => $_GET['courseHeb']);
	if (!isset($_GET['date']) || strlen($_GET['date']) < 1) {
	   $date = date('d.m.y');
	} else { $date = $_GET['date']; }

	$html_template = file_get_contents("template2.html");
	$cert_html = fill_template($cert_data, $html_template, $date);

	// Create a new Pdf object with some global PDF options
	$pdf = new Pdf(array(
	    'no-outline',   // Make Chrome not complain
	    'margin-top'    => 0,
	    'margin-right'  => 0,
	    'margin-bottom' => 0,
	    'margin-left'   => 0,

	    // Default page options
	    'disable-smart-shrinking',
	    'binary' => __DIR__ . '/wkhtmltopdf'
	));

	header('Content-Type: application/pdf; charset=utf-8');
	header('Content-Disposition: attachment; filename="bpm_cert.pdf"');
	$cert_html = mb_convert_encoding($cert_html, "UTF-8");

	file_put_contents('tempTemplate.html', $cert_html);

	// Add a page. To override above page defaults, you could add
	// another $options array as second argument.
	$pdf->addPage('tempTemplate.html');

	if (!$pdf->send($cert_data->course_hebrew . ' - ' . 
					$cert_data->user_hebrew . '.pdf')) {
	    throw new Exception('Could not create PDF: '.$pdf->getError());
	}

	unlink('tempTemplate.html');
}

function fill_template($cert_data, $html_template, $date=NULL) {
    if (!$date) { $date = date('d.m.y');}
	$english_name_length = strlen($cert_data->course_english);
	$hebrew_name_length = strlen($cert_data->course_hebrew);
	$fixed_class_en = '';
	$fixed_class_he = '';
	$fixed_class_student_en = '';
	$fixed_class_student_he = '';

	if ($english_name_length > 34) {
		$fixed_class_en = ' smallerLongText';
	    $html_template = bpm_fix_course_english_name($html_template);
	}
	if ($hebrew_name_length > 34) {
		$fixed_class_he = ' smallerLongText';
	    $html_template = bpm_fix_course_hebrew_name($html_template);
	}
	
	$str = ' | ת"ז ';
	mb_convert_encoding($str, "UTF-8", "UTF-8");
	
	$user_name_field_english = $cert_data->user_english . ' | ID ' . $cert_data->user_id;
	$user_name_field_hebrew = $cert_data->user_hebrew . $str . $cert_data->user_id;

	if (strlen($user_name_field_english) > 34) {
		$fixed_class_student_en = ' smallerLongText';
	    $html_template = bpm_fix_student_english_name($html_template);
	}
	if (strlen($user_name_field_hebrew) > 34) {
		$fixed_class_student_he = ' smallerLongText';
	    $html_template = bpm_fix_student_hebrew_name($html_template);
	}

	$template_indexes = array('<p id="date">', 
					  		  '<p id="studentEnglishName" class="studentName eng2' . $fixed_class_student_en . '">',
					  		  '<p id="courseEnglishName" class="courseName eng' . $fixed_class_en . '">',
					  		  '<p id="studentHebrewName" class="studentName' . $fixed_class_student_he . '">',
					  		  '<p id="courseHebrewName" class="courseName' . $fixed_class_he . '">');
   
	$template_values = array($date,
					 		 $user_name_field_english,
					 		 $cert_data->course_english,
					 		 $user_name_field_hebrew,
					 		 $cert_data->course_hebrew);

	for ($i=0; $i < count($template_indexes); $i++) { 
		$insert_position = strpos($html_template, $template_indexes[$i]);
		$insert_position_length = strlen($template_indexes[$i]);
		$html_template = substr_replace($html_template, $template_values[$i], $insert_position + $insert_position_length, 0);
	}

	return $html_template;
}

function bpm_fix_course_english_name($template) {
	$old_class = 'class="courseName eng">';
	$fixed_class = 'class="courseName eng smallerLongText">';
	$class_position = strpos($template, $old_class);
	$template = substr_replace($template, $fixed_class, $class_position, strlen($old_class));
	return $template;
}

function bpm_fix_course_hebrew_name($template) {
	$old_class = 'class="courseName">';
	$fixed_class = 'class="courseName smallerLongText">';
	$class_position = strpos($template, $old_class);
	$template = substr_replace($template, $fixed_class, $class_position, strlen($old_class));
	return $template;
}

function bpm_fix_student_english_name($template) {
	$old_class = 'class="studentName eng2">';
	$fixed_class = 'class="studentName eng2 smallerLongText">';
	$class_position = strpos($template, $old_class);
	$template = substr_replace($template, $fixed_class, $class_position, strlen($old_class));
	return $template;
}

function bpm_fix_student_hebrew_name($template) {
	$old_class = 'class="studentName">';
	$fixed_class = 'class="studentName smallerLongText">';
	$class_position = strpos($template, $old_class);
	$template = substr_replace($template, $fixed_class, $class_position, strlen($old_class));
	return $template;
}