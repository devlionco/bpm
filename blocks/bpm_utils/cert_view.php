<?php

require(__DIR__ . '/vendor/autoload.php');
require_once('../../config.php');
require_once('config.php');

use mikehaertl\wkhtmlto\Pdf;

// Check for all required variables.
$course_parent_id = required_param('courseparentid', PARAM_RAW);
$course_id = required_param('courseid', PARAM_RAW);
$user_id = required_param('userid', PARAM_RAW);
$cert_type = optional_param('certtype', null, PARAM_TEXT);
$ssd_request = optional_param('ssdrequest', false, PARAM_BOOL);
$dev = optional_param('dev', false, PARAM_BOOL);
$branch = optional_param('branch', false, PARAM_TEXT);

if (!$user_id) {
    $user_id = $USER->id;
}

require_login();
$context = context_course::instance($course_id);
$PAGE->set_context($context);
	/*echo "<script>console.log('strpos($branch, \"חיפה\") >0 : '" 	.
	(strpos($branch, "חיפה") >0) .
	")</script>";*/
if ($ssd_request) {
	$url = new moodle_url('/blocks/bpm_utils/cert_view.php', array('courseparentid' => $course_parent_id,
                                                                   'courseid' => $COURSE->id,
                                                                   'userid' => $user_id,
                                                                   'branch' => $branch));
	$url_string = $url;
	if($cert_type != 'BPM') {
		$surl = new moodle_url('/blocks/bpm_utils/cert_view.php', array('courseparentid' => $course_parent_id,
                                                                   	    'courseid' => $COURSE->id,
                                                                   	    'userid' => $user_id,
                                                                   	    'certtype' => $cert_type,
                                                                   	    'branch' => $branch));
		$url_string = "תעודת הסמכה חיצונית " . $surl;

		if ($cert_type == 'cubase') {
			if (!$DB->record_exists('bpm_cubase_cert', array('userid' => $user_id))) {
				$new_record = new stdClass();
				$new_record->duration = $DB->get_field('course_details', 'several_days', array('courseid' => $course_id));
				$new_record->userid = $USER->id;
				$new_record->issuedate = time();
				$DB->insert_record('bpm_cubase_cert', $new_record);
			}
		}
	}

	$cert_data_record = bpm_get_certification_data($user_id, $course_parent_id, $dev);

	// Build the message object
    $message = new \core\message\message();
    $message->courseid        = $course_parent_id;
    $message->name            = 'ssd_cert_request_message';
    $message->component       = 'block_bpm_utils';
    $message->userfrom        = $BU_CFG->BPM_BOT_ID;
    $message->userto          = $BU_CFG->BPM_SSD_ID;
    $message->subject         = 'בקשה להדפסת תעודות';

    if (substr_count($branch, "haifa") > 0) {
        $message->subject = "למזכירות חיפה - " . $message->subject;
    }
    $message->fullmessagehtml = "<p dir=\"rtl\"> הסטודנט: <b>" . $cert_data_record->user_hebrew_name . 
    							'</b> מבקש הדפסת תעודות לקורס: <b>' . $cert_data_record->course_hebrew_name . '</b><br>' .
    							'<br><br>' . $url_string . '<br></p>';
    $message->smallmessage    = 'בקשה להדפסת תעודה לסטודנט: ' . $cert_data_record->user_hebrew_name . 
    						    ' בקורס: ' . $cert_data_record->course_hebrew_name;
    $result = message_send($message);

    $course_url = new moodle_url('/course/view.php', array('id' => $course_id));
	$PAGE->set_url('/blocks/bpm_utils/cert_view.php', array('id' => $course_id));
	$PAGE->set_pagelayout('incourse');
	$PAGE->set_heading('BPM Moodle');

	echo $OUTPUT->header();
	echo "<div style=\"text-align:center\"><h3>בקשתך נשלחה בהצלחה!</h3> <br><br> <a href=\"$course_url\">לחץ כאן כדי לחזור לעמוד הקורס</a></div>";
	echo $OUTPUT->footer();
} else {
	$cert_data_record = bpm_get_certification_data($user_id, $course_parent_id, $dev);
	$html_template = bpm_fill_html_template($cert_type, $cert_data_record, $dev);

    	// Create a new Pdf object with some global PDF options
    	$pdf = new Pdf(array(
    	    'no-outline',         // Make Chrome not complain
    	    'margin-top'    => 0,
    	    'margin-right'  => 0,
    	    'margin-bottom' => 0,
    	    'margin-left'   => 0,
    
    	    // Default page options
    	    'disable-smart-shrinking',
    	    'binary' => __DIR__ . '/wkhtmltopdf'
    	));
    	
    // For testing purposes 
    if (!$dev) {
        $external_cert = ($cert_type != 'BPM') ? ' - הסמכה' : '';
    	header('Content-Type: application/pdf; charset=utf-8');
    	header('Content-Disposition: attachment; filename="bpm_cert.pdf"');
    	$html_template = mb_convert_encoding($html_template, "UTF-8");
    
    	file_put_contents('tempTemplate.html', $html_template);
    
    	// Add a page. To override above page defaults, you could add
    	// another $options array as second argument.
    	$pdf->addPage('tempTemplate.html');
    
        $pdf_name = $cert_data_record->course_hebrew_name . ' - ' . 
    					$cert_data_record->user_hebrew_name . 
    					$external_cert . '.pdf';
    
    	if (!$pdf->send($pdf_name)) {
    	    throw new Exception('Could not create PDF: '.$pdf->getError());
    	}
    
    	unlink('tempTemplate.html');
    } else {
    	$external_cert = ($cert_type != 'BPM') ? ' - הסמכה' : '';
        $html_template = mb_convert_encoding($html_template, "UTF-8");
        file_put_contents('tempTemplate.html', $html_template);
    
    	// Add a page. To override above page defaults, you could add
    	// another $options array as second argument.
    	$pdf->addPage('tempTemplate.html');
    
        $pdf_name = $cert_data_record->course_hebrew_name . ' - ' . 
    				$cert_data_record->user_hebrew_name .
    				$external_cert . '.pdf';
    	
    	unlink('tempTemplate.html');
    }
}

function bpm_get_certification_data($user_id, $course_id, $dev) {
	global $DB;

	$sql = "SELECT u.id,
				   u.idnumber,
				   ud.english_name AS user_english_name, 
	   			   ud.hebrew_name AS user_hebrew_name,
       			   cpd.english_name AS course_english_name,
       			   cpd.hebrew_name AS course_hebrew_name,
       			   cd.several_days
			FROM   mdl_sf_user_data ud, 
				   mdl_sf_course_parent_data cpd, 
				   mdl_user u,
				   mdl_course_details cd
			WHERE u.id    	   = $user_id
			AND   ud.user_id   = $user_id
			AND   cd.courseid = $course_id
			AND   cpd.course_id = $course_id";

	$cert_data_record = $DB->get_record_sql($sql);

	// If english names exist, create a certification data object and populate
	if(isset($cert_data_record->user_english_name) && isset($cert_data_record->course_english_name)) {
		$cert_data_record->date = date("d.m.Y");
		return $cert_data_record;
	}
	return false;
}

function bpm_fill_html_template($type, $cert_data_record, $dev) {
	$html_template;
	$template_indexes;
	$template_values;

	switch ($type) {
		case 'cubase':
			$html_template = file_get_contents("assets/cubase.html");
			$template_indexes = array('<p class="dynamicText" id="studentEnglishName">',
	    					  		  '<p class="dynamicText" id="courseName">',
	    					  		  '<p class="dynamicText" id="courseDuration">',
	    					  		  '<p id="date">');

			$template_values = array($cert_data_record->user_english_name,
							 		 $cert_data_record->course_english_name,
							 		 $cert_data_record->several_days . ' Hrs',
							 		 $cert_data_record->date);
			break;
		case 'ableton':
			$html_template = file_get_contents("assets/ableton.html");
			$template_indexes = array('<p id="studentEnglishName" class="inputs">',
	    					  		  '<span id="studentID">',
	    					  		  '<p id="date" class="inputs">');

			$template_values = array($cert_data_record->user_english_name,
							 		 $cert_data_record->idnumber,
							 		 $cert_data_record->date);
			break;
		default:
			$html_template = file_get_contents("assets/template2.html");
			$english_name_length = strlen($cert_data_record->course_english_name);
			$hebrew_name_length = strlen($cert_data_record->course_hebrew_name);
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
			
			$user_name_field_english = $cert_data_record->user_english_name . ' | ID ' . $cert_data_record->idnumber;
			$user_name_field_hebrew = $cert_data_record->user_hebrew_name . ' | ת.ז. ' . $cert_data_record->idnumber;

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

			$template_values = array($cert_data_record->date,
							 		 $user_name_field_english,
							 		 $cert_data_record->course_english_name,
							 		 $user_name_field_hebrew,
							 		 $cert_data_record->course_hebrew_name);
			break;
	}
    if ($cert_data_record->user_english_name != 'dev_bpm') {
        	for ($i=0; $i < count($template_indexes); $i++) { 
		    $insert_position = strpos($html_template, $template_indexes[$i]);
		    $insert_position_length = strlen($template_indexes[$i]);
		    $html_template = substr_replace($html_template, $template_values[$i], $insert_position + $insert_position_length, 0);
	    }
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