<?php
//gets called by local/salesforce/run_once_per_day.php
require_once 'config.php';
require_once __DIR__ . '/../../config.php';

set_include_path("/public_html/moodle31");
require_once(realpath( dirname(dirname(dirname( __FILE__ )))) . '/blocks/bpm_utils/certificate_eligibility.php');
require_once(realpath( dirname(dirname(dirname( __FILE__ )))) . '/blocks/bpm_utils/assets/messages/simple_html_dom/simple_html_dom.php');
require_once(realpath( dirname(dirname(dirname( __FILE__ )))) . '/blocks/bpm_utils/vendor/autoload.php');

use mikehaertl\wkhtmlto\Pdf;

$_GLOBALS['emails_sent'] = false; //used to see whether or not we need to send out the daily log
echo "<pre>";
//  auto_certificates();
function auto_certificates() {
	global $_GLOBALS;
	$courses = bpm_get_courses_to_check_eligibility();
// 	var_dump($courses);
	if (count($courses) > 0) {
       log_certificate(NULL, NULL);
	} else if (count($courses) == 0) {
	    echo 'no courses';
	}
	foreach ($courses as $course) {
	    echo PHP_EOL . 'course: ' . PHP_EOL;
	    var_dump($course);
	    echo PHP_EOL;
	    if ($course->course_type == '2' || $course->course_type == '0') { //clali && standalone
	        $program_name;
	        log_certificate(NULL, $course);
		    get_students_and_determine_eligibility($course, $program_name);
	    } else if ($course->course_type == '1') {//from program
	        log_certificate(NULL, $course);
	        check_for_standalones($course);
	    }
	}
	echo PHP_EOL;
	if ($_GLOBALS['emails_sent']) {
	    export_log('mazkirut@bpm-music.com');    
	   
	   //todo uncomment: 
	   export_log('erez@bpm-music.com');    
	} else {
	    //todo uncomment: 
	    log_certificate('no courses (+offset) to certify today', NULL);
	    
	}

}

function bpm_get_courses_to_check_eligibility() {
    global $DB;
	
	$testing_sql = "SELECT c.id, c.enddate, c.shortname, sfd.course_type, sfd.sf_id as parent_sf_id, sfd.course_id as parent_m_id, sfd.certificate_eligibility_offset, c.category
			FROM mdl_course c, mdl_course_details cd, mdl_sf_course_parent_data sfd
			WHERE c.id = cd.courseid
			AND cd.coursefather = sfd.sf_id
			AND sfd.course_type <> 3 
			AND certificate_eligibility_offset IS NOT NULL
			AND cd.coursefather <> '01t240000001vQOAAY'  " .
			"AND c.id IN (1058)" . //1056) " . 
			//"AND	DATE_ADD(from_unixtime(c.enddate, '%Y-%m-%d'), INTERVAL sfd.certificate_eligibility_offset DAY) = CURDATE() " .
			"ORDER BY c.id DESC";
	
	$sql = "SELECT c.id, c.enddate, c.shortname, sfd.course_type, sfd.sf_id as parent_sf_id, sfd.course_id as parent_m_id, sfd.certificate_eligibility_offset, c.category, cd.sf_id
			FROM mdl_course c, mdl_course_details cd, mdl_sf_course_parent_data sfd
			WHERE c.id = cd.courseid
			AND cd.coursefather = sfd.sf_id
			AND sfd.course_type <> 3 
			AND certificate_eligibility_offset IS NOT NULL
			AND cd.coursefather <> '01t240000001vQOAAY' 
			AND	DATE_ADD(from_unixtime(c.enddate, '%Y-%m-%d'), INTERVAL sfd.certificate_eligibility_offset DAY) = CURDATE()
			ORDER BY c.id DESC";
    
    $tomorrow_sql = "SELECT c.id, c.enddate, c.shortname, sfd.course_type, sfd.sf_id as parent_sf_id, sfd.course_id as parent_m_id, sfd.certificate_eligibility_offset, c.category, cd.sf_id
			FROM mdl_course c, mdl_course_details cd, mdl_sf_course_parent_data sfd
			WHERE c.id = cd.courseid
			AND cd.coursefather = sfd.sf_id
			AND sfd.course_type <> 3 
			AND certificate_eligibility_offset != \"NULL\"
			AND cd.coursefather <> '01t240000001vQOAAY' 
			AND	DATE_ADD(from_unixtime(c.enddate, '%Y-%m-%d'), INTERVAL sfd.certificate_eligibility_offset DAY) = DATE_ADD(CURDATE(), INTERVAL 4 DAY)
			ORDER BY c.id DESC";
			    
	return $DB->get_records_sql($sql);
}


function get_students_and_determine_eligibility($course, $program_name) {
	global $DB, $BU_CFG;

	$mysqli = get_db_connection();
	
	$courseid = $course->id;
	$students_sql = "SELECT usr.id, c.id as courseid, usr.firstname, usr.lastname, usr.email,usr.phone2, c.fullname, c.category
                        FROM mdl_course c, mdl_context cx, mdl_role_assignments ra, mdl_user usr, mdl_role r, mdl_enrol e, mdl_user_enrolments ue, mdl_sf_enrollments sfe
                        WHERE c.id = cx.instanceid
                        AND cx.contextlevel = '50'
                        AND r.id = '5'
                        AND usr.suspended = 0
                        AND cx.id = ra.contextid
                        AND ra.roleid = r.id
                        AND ra.userid = usr.id
                        AND c.id= $courseid
                        AND e.courseid = $courseid
                        AND ue.enrolid = e.id
                        AND ue.userid = usr.id
                        AND sfe.userid = usr.id
                        AND sfe.courseid = $courseid
                        AND sfe.exempt <> 1
                        #AND ue.status <> 1
                        ";
	$result = $mysqli->query($students_sql);
	while($row = $result->fetch_assoc()) {
		$current_student = [];
        foreach ($row as $key => $value) {
            if ($value === NULL) {
				$value = "";
            }
			$current_student[$key] = $value;
		}
		
		$current_student['is_eligible'] = (determine_eligibility($current_student, $course));
		$course->es_required = check_es_requirement_for_course($course->shortname, $course->course_type);
		if ($course->es_required) {
		    $current_student['passed_es'] = student_passed_es($current_student['id']);
		}
		echo PHP_EOL . 'student: ';
		var_dump($current_student);
        bpm_prepare_email_and_certificate_templates($current_student, $course);
	}
	
}

function get_program_failure_details($student, $course) {
    global $DB;
    $allowed_failures = 1;
    $category = $course->category;
    $course_id = $course->id;
    $output;
    $student_id = $student['id'];
    $sql = "SELECT sfe.courseid, c.shortname, gg.finalgrade, gi.gradepass, sfe.attendance, sfe.completegrade, cc.shortname as programname
            FROM  mdl_sf_enrollments sfe, mdl_grade_items gi, mdl_grade_grades gg, mdl_course c, mdl_course cc
            WHERE sfe.userid = $student_id 
            AND c.id IN (SELECT id FROM mdl_course WHERE id <> $course_id AND category = $category)
            AND c.id = sfe.courseid
            AND gi.courseid = sfe.courseid
            AND gg.userid = sfe.userid
            AND gg.itemid = gi.id
            AND gi.itemtype = 'course'
            AND ((gg.finalgrade < gi.gradepass) OR (sfe.attendance < 80) OR (sfe.completegrade = 0)
            OR gg.finalgrade IS NULL
            )
            #AND (sfe.grade <> -1 AND sfe.attendance <> -1)
            AND cc.id = $course_id
            ";
    // echo 'sql: ' . $sql . "<br>";
    $courses_from_program = $DB->get_records_sql($sql);
    $any_course =  array_keys($courses_from_program);
    $program_name = get_category_name($course->category);
    $num_of_fails = count($courses_from_program);
    if ($num_of_fails > $allowed_failures) {
        foreach($courses_from_program as $failed_course) {
            // echo PHP_EOL . 'checking for other course instead of ' . $failed_course->courseid . '...' . PHP_EOL;
            $failed_course_id = $failed_course->courseid;
            $sql = "SELECT c.id, c.shortname, gg.finalgrade, gi.gradepass, sfe.attendance, sfe.completegrade
                    FROM mdl_course c, mdl_grade_grades gg, mdl_grade_items gi, mdl_sf_enrollments sfe
                    WHERE c.id IN (
                        SELECT cd2.courseid FROM mdl_course_details cd2 
                        WHERE cd2.courseid <> $failed_course_id 
                        AND cd2.coursefather = (
                            SELECT cd3.coursefather FROM mdl_course_details cd3 
                            WHERE cd3.courseid = $failed_course_id
                        )
                    )
                    AND sfe.courseid = c.id
                    AND gg.itemid = gi.id
                    AND gi.itemtype = 'course'
                    AND gi.courseid = c.id
                    AND gg.userid = $student_id
                    AND sfe.userid = $student_id
                    
                    #check if actually passed:
                    AND (gg.finalgrade > gi.gradepass AND attendance >= 80 AND completegrade = 1)
                    
                    ";
                    
            $previous_passing_enrollment_in_course_type = $DB->get_records_sql($sql);
            if ($previous_passing_enrollment_in_course_type && count($previous_passing_enrollment_in_course_type) > 0) {
                $num_of_fails--;
                $courseid_toremove = $failed_course->courseid;
                unset($courses_from_program[$courseid_toremove]);
                $new_courseid = $previous_passing_enrollment_in_course_type[array_keys($previous_passing_enrollment_in_course_type)[0]];
                // echo 'found a replacement, course ' . $new_courseid->id;
                
            } else {
                // echo 'did not find a replacement, failure stays.';
            }
            
        }
    }
    
    
    // echo 'program name is ' . $program_name . ', ' . 'student name is ' . $student['firstname'] . ' ' . $student['lastname'];
    $hide_fails = false; //if its a program and theres only one failure it doesnt matter
    
    $output .= "<div dir='rtl' style='direction:rtl'><h4 style='color:#fdaf17;display:inline;'>$program_name</h4><span style='font-style:italic;color:white;'> - אינך זכאי/ת לתעודה בשלב זה</span></div>";
    $output .= "<span style='font-style:italic;text-decoration:underline;color:white;'>סיבה:</span> ";
    
        if (($num_of_fails-$allowed_failures) > 1) {
            $output .= "<li style='color:white;'> זכאות לתעודה מצריכה מעבר של כלל הקורסים במסלול כאשר ניתן לעבור עם נכשל אחד לכל היותר. במסלול זה חסרים לך " . ($num_of_fails-1) . " ציונים עוברים כדי לקיים את זכאותך לתעודה. להלן אותם לא עברת והישיגך הנוכחיים בכל אחד:</li>";
        } else if (($num_of_fails-$allowed_failures) > 0) {
            $output .= "<li style='color:white;'> זכאות לתעודה מצריכה מעבר של כלל הקורסים במסלול כאשר ניתן לעבור עם נכשל אחד לכל היותר. במסלול זה חסר לך ציון עובר אחד כדי לקיים את זכאותך לתעודה. להלן פירוט הקורסים אותם לא עברת והישיגך הנוכחיים בכל אחד:</li>";
        } else if ($num_of_fails-$allowed_failures == 0) {
            //$output .= "<li style='color:white;'>*זכאות לתעודה עבור מסלול לימודים מאפשרת עד נכשל אחד - כעת יש לך ציון אחד העונה להגדרה הזו והוא מפורט בהמשך.<li>";
            $hide_fails = true;
        }
        
        if (isset($student['passed_es'])) {
            if (!$student['passed_es']) {
                $output .= "<li style='color:white'>לא עברת קורס בטיחות בחשמל ההכרחי לקבלת תעודה בקורס זה</li>";
                $output .= "<div id='es_container' style='border: 1px solid red;border-radius: 5px;padding: 0 2em;margin-top: 10px;margin-bottom:10px;line-height: 1.3em;'>";
                $output .= "<p style='color:#f03e62;font-weight:bold' id='es_fail'>נדרשת השלמה של בטיחות בחשמל</p>";
                //$output .= "<p style='color:white' id='es_fail'>לצפייה בתאריכים הקרובים והרשמה יש לגשת למערכת המודל. לאחר שלושה חודשים מסיום הלימודים השלמת בטיחות בחשמל תתאפשר בתשלום, שאותו ניתן לתאם מול מזכירות המכללה בטלפון <a style='color:#00aab4 !important' href='tel:035604781'>03-5604781</a>.</p>";
                $output .= "</div>";
            }
        }
    if (!$hide_fails) {
        foreach ($courses_from_program as $course_from_program) {
            $output .= get_failure_details($student, $course_from_program->courseid, true, $program_name);
        }
    }
    
   // print_r($output);
    return $output;
}

function get_failure_details($student, $course_id, $program = false, $program_name = NULL ) {
    global $DB;

    $student_id = $student['id'];
    $sql = "SELECT sfe.id, sfe.grade, sfe.attendance, sfe.completegrade, c.shortname, gi.gradepass
            FROM mdl_sf_enrollments sfe, mdl_course c, mdl_grade_items gi
            WHERE sfe.userid = $student_id
            AND sfe.courseid = c.id
            AND gi.courseid = c.id
            AND c.id = $course_id
            AND gi.itemtype = 'course'";
    $enrollment_data = $DB->get_records_sql($sql);
    $enrollment_data = $enrollment_data[array_keys($enrollment_data)[0]];
    
    $html = "<ul class='singleCourseDetails' style='border:1px solid grey;border-radius:6px;padding: 20px 30px;'>";
    
    if (!$program) {
        $html .= "<span id='courseName' style='color:#00aab4;font-size:1.2em;'>" . $enrollment_data->shortname . "</span>";
        $html .= "<span style='font-style:italic;color:white;'> - אינך זכאי/ת בשלב זה</span>";
        if (isset($student['passed_es'])) {
            if (!$student['passed_es']) {
                $html .= "<p style='color:#f03e62;font-weight:bold' id='es_fail'>נדרשת השלמה של בטיחות בחשמל</p>";
                
            }
        }
    } else {
        //echo str_replace(" - " . $program_name, "", $enrollment_data->shortname) . 'ok? not program' . PHP_EOL;
        $html .= "<span id='courseName' style='color:#00aab4;font-size:1.2em;'>" .
        str_replace(" - " . $program_name, "", $enrollment_data->shortname) .
        "</span>";
    }
    if (isset($student['passed_es'])) {
        if (!$student['passed_es']) {
            
        }
    }
    
    
    //check for attendance, const, needs to be >= 80
    $html .= "<li style=color:white;><span style='font-weight:bold'>נוכחות</span> - ";
    if ((int)$enrollment_data->attendance < 80) {
        if ((int)$enrollment_data->attendance == -1) {
            $html .= "נדרש: 80% - ציונך: <span style='font-style:italic'>-חסר ערך-</span>";
        } else {
            $html .= "נדרש: 80% - ציונך: " . round((int)$enrollment_data->attendance, 1) . "%";
        }
    } else {
        $html .= "✓";
    }
        $html .= "</li>";
    //check for complete & passing grade 
    $html .= "<li style=color:white;><span style='font-weight:bold'>ציון</span> - ";
    
    $grades_page_url = "https://my.bpm-music.com/grade/report/user/index.php?id=";
    if ((int)$enrollment_data->completegrade < 1) {
        $html .= "אין לך ציון שלם בקורס זה. יש עבור ל<a href='" . $grades_page_url . $course_id . "' style='color:#00aab4 !important'>פירוט הציונים במודל למידע נוסף</a>.";
    } else if ((int)$enrollment_data->grade < (int)$enrollment_data->gradepass) {
        if ((int)$enrollment_data->grade == -1) {
             $html .= "הציון העובר בקורס זה הינו " . round($enrollment_data->gradepass) . " ולא הוזן עבורך ציון בקורס זה.";
        } else {
            $html .= "הציון העובר בקורס זה הינו " . round($enrollment_data->gradepass) . " וציונך הוא " . round((int)$enrollment_data->grade, 1) . ".";
        }
    } else {
        $html .= "✓";
    }
    $html .= "</li>";
    
    $html .= "</ul>";
    
    return $html;
}

function determine_eligibility($student, $course) {
    if ($course->course_type == 1) {
        $eligible = bpm_cert_check_eligibility($student['id'], $course->shortname, '1', $course->parent_sf_id, $course->id, true);
    } else {
        $eligible = bpm_cert_check_eligibility($student['id'], $course->shortname, '1', $course->parent_sf_id, $course->id);
    }
    return $eligible;
}

function get_db_connection() {
    $mysqli = new mysqli('localhost','superuserben', 'Aa123456', 'mybpmmus_moodle_bpm');
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }
    mysqli_set_charset($mysqli,"utf8");
    return $mysqli;
}

function get_course_type($course_name, &$program_name = NULL) {
        global $S_CFG;
        
        $program_names = $S_CFG->PROGRAM_NAMES;
        for ($name_index=0; $name_index < count($program_names); $name_index++) { 
            if (strpos($course_name, $program_names[$name_index]) !== false) {
                $program_name = $program_names[$name_index];
                return $S_CFG->COURSE_TYPES['program'];
            }
        }
        return $S_CFG->COURSE_TYPES['standalone'];
}

function get_certification_data($user_id, $course_id) {
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
    
function fill_html_template($type, $cert_data_record) {
    
    $assets_dir = new moodle_url('/blocks/bpm_utils/assets');
	$html_template;
	$template_indexes;
	$template_values;

	switch ($type) {
		case 'cubase':
			$html_template = file_get_contents($assets_dir . "/cubase.html");
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
			$html_template = file_get_contents($assets_dir . "/ableton.html");
			$template_indexes = array('<p id="studentEnglishName" class="inputs">',
	    					  		  '<span id="studentID">',
	    					  		  '<p id="date" class="inputs">');

			$template_values = array($cert_data_record->user_english_name,
							 		 $cert_data_record->idnumber,
							 		 $cert_data_record->date);
			break;
		default:
		    
			$html_template = file_get_contents($assets_dir . "/template2.html");
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
	}

	for ($i=0; $i < count($template_indexes); $i++) { 
		$insert_position = strpos($html_template, $template_indexes[$i]);
		$insert_position_length = strlen($template_indexes[$i]);
		$html_template = substr_replace($html_template, $template_values[$i], $insert_position + $insert_position_length, 0);
	}

	return $html_template;
}


function send_certificate_by_email($recipient, $files=NULL, $subject, $message, $eligible) {
	//a random hash will be necessary to send mixed content    
	$separator = md5(time());
    //carriage return type (RFC)
    $eol = "\r\n";

    // main header (multipart mandatory)
    $headers = "From: מכללת BPM <noreply@bpm-music.com>" . $eol;
    $headers .= "MIME-Version: 1.0" . $eol;
    if ($files) {
        $headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol;
    } else { 
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    }
    $headers .= "Bcc: erez@bpm-music.com\r\n";

    // message
    if ($files) {
        $body = "--" . $separator . $eol;
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64" . $eol;
		$body .= "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" . "Content-Type: text/plain; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . chunk_split(base64_encode($message)) . "\n\n"; 
        // $body .=  chunk_split(base64_encode($message)) . $eol; 
    } else {
        $body = $message . $eol;
    }

    // attachment/s
    if ($files) {
		foreach($files as $file) {
			$file_content = fopen($file, "r");
			$file_data = fread($file, filesize($file));
			fclose($file_content);
			$file_data = chunk_split(base64_encode($file_data));
			$content = file_get_contents($file);
			$content = chunk_split(base64_encode($content));
			$body .= "--" . $separator . $eol;
			$body .= "Content-Type: application/octet-stream; name=\"" . $file . "\"" . $eol;
			$body .= "Content-Transfer-Encoding: base64" . $eol;
			$body .= "Content-Disposition: attachment" . $eol;
			$body .= $content . $eol;
        //$body .= "--" . $separator . "--";
        unlink($file);
		}
    }
//  	echo PHP_EOL . 'body: ' . PHP_EOL;
    //echo $eol . "headers: " . $eol;
    //var_dump($headers);
    
    //todo uncomment:
    if (mail($recipient, $subject, $body, $headers)) { 
        echo 'sent mail';
    } else {
        echo "mail send ... ERROR!";
        print_r( error_get_last() );
    }
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
function get_message_template($eligible, $override=NULL) {
    $dir = new moodle_url('/blocks/bpm_utils/assets/messages');
	if ($override) { //ableton mid program, special template
		$html = file_get_contents($dir . '/' .  $override . '.html');
	} else {
		if ($eligible) {
			$html = file_get_contents($dir . '/pass.html');
			
		}   else {
			$html = file_get_contents($dir . '/fail.html');   
			
		}
	}
    return $html;
}

function strip_program_name(&$program_name) {
    global $BU_CFG;
    
    $program_names = $BU_CFG->PROGRAM_NAMES;
    for ($name_index=0; $name_index < count($program_names); $name_index++) { 
        if (strpos($course_name, $program_names[$name_index]) !== false) {
            $program_name = $program_names[$name_index];
            return false;
        }
    }
    return $program_name;
}

function course_requires_es($course_name, $program_name) {
    global $BU_CFG;
    
        if (bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY_STRING, $program_name) ||
        bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY_STRING, $course_name) || bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY__ATTENDANCE_SESSION_STRING, $course_name)) {
        
            return true;
        } else {
            return false;
        }
}
function student_passed_es($user_id) {
    global $DB;
    //not using $course_id in the following sql because it doens't matter which course they had electric safety in, as long as they had it
    $electric_sql = "SELECT COUNT(al.id)
                    FROM mdl_attendance_log al, mdl_attendance_sessions asess, mdl_attendance_statuses astats, mdl_course c, mdl_attendance a, mdl_user u
                    WHERE al.sessionid = asess.id
                    AND asess.attendanceid = a.id
                    AND a.course = c.id
                    AND al.statusid = astats.id
                    AND astats.description = 'נוכח'
                    AND asess.description LIKE '%בטיחות בחשמל%'
                    AND studentid = u.id
                    AND u.id = $user_id";
    //echo 'es sql: ' . $electric_sql;
    $electric_session_count = $DB->get_field_sql($electric_sql, NULL);
    
    if ($electric_session_count >= 1) {
        return true;
    } else {
        if (!bpm_check_for_es_course_reg_and_completion($user_id)) {
            return false;
        }
    }
   
}
function check_for_standalones($course) {
    global $DB;
    
    $course_from_program = $course->id;
    $clali_sql = "SELECT c1.id FROM mdl_course c1 WHERE shortname LIKE '%כללי%' AND c1.category IN (SELECT c2.category FROM mdl_course c2 WHERE c2.id = $course_from_program)";
    if ($clali_id = $DB->get_field_sql($clali_sql)) {
        //not enrolled in clali course
        $sa_sql = "SELECT sfa.userid as id, u.firstname, u.lastname, u.email
                    FROM mdl_sf_enrollments sfa, mdl_user u, mdl_user_enrolments ue, mdl_enrol e
                    WHERE u.id = sfa.userid
                    AND sfa.courseid = $course_from_program
                    AND sfa.userid NOT IN (SELECT sfb.userid FROM mdl_sf_enrollments sfb WHERE sfb.courseid = $clali_id)
                    AND sfa.userid = ue.userid
                    AND ue.enrolid = e.id
                    AND e.courseid = $course_from_program
                    AND ue.status <> 1
                    ";
        $sa_regs = $DB->get_records_sql($sa_sql);
        foreach($sa_regs as $sa_reg) {
            $sa_reg = (array) $sa_reg;
            $sa_reg['is_eligible'] = determine_eligibility($sa_reg, $course);
            bpm_prepare_email_and_certificate_templates($sa_reg, $course, ' - סטנדאלון');
        }
    } else { //error 
		echo 'error, could not find clali course';
		var_dump($course);
		return false;
	}
	if (strpos($course->shortname, "אבלטון") !== false) { //need to distribute ABLETON certificates/fail messages to program students regardless of the program
		$program_students_sql = "SELECT DISTINCT sfa.userid as id, u.firstname, u.lastname, u.email, sfa.attendance, sfa.grade, sfa.completegrade
                    FROM mdl_sf_enrollments sfa, mdl_user u
                    WHERE u.id = sfa.userid
                    AND sfa.courseid = $course_from_program
                    AND sfa.userid IN (SELECT sfb.userid FROM mdl_sf_enrollments sfb WHERE sfb.courseid IN($clali_id)) " .
					/*AND sfa.attendance >= 80
					AND sfa.grade >= 80
					AND sfa.completegrade = 1*/
					" AND sfa.exempt <> 1";
		$program_students = $DB->get_records_sql($program_students_sql);
		$html_dom = new simple_html_dom();
		foreach($program_students as $current_student) {
		    $current_student= (array) $current_student;
		    
		    $current_student['is_eligible'] = (($current_student['grade'] >= 80) && ($current_student['attendance'] >= 80) && ($current_student['completegrade'] == 1)) ? true : false ;
			if (!$current_student['is_eligible']) {
				bpm_prepare_email_and_certificate_templates($current_student, $course, ' - אין זכאות לתעודת הסמכה(אבלטון) ');
			} else {

				$ableton_cert_file = bpm_prepare_certificate($current_student, $course, 'ableton');
				$file_arr = array($ableton_cert_file);
				$message = get_message_template(true, 'pass_ableton_mid_program');
				
				$html_message = $html_dom->load($message);
				$html_message->find('[id=studentName]', 0)->innertext = 'שלום ' . $current_student['firstname'] . ',';
				$branch = (strpos($course->shortname, " חיפה") ? "haifa" : '');
				$certificate_url = new moodle_url('/blocks/bpm_utils/cert_view.php', array('courseparentid' => $course->parent_m_id,
																	'courseid' => $course->id,
																	'userid' => $current_student['id'],
																	'ssdrequest' => 1,
																	'certtype' => 'ableton',
																	'branch' => $branch));
				$html_message->find('[id=certificateUrl]', 0)->href = $certificate_url;
				$docHtml = $html_message->save(); 
				$html_message->clear();
				unset($html_message);
				
				$email = $current_student['email'];  //TODO comment when testing
				// $email = 'erez@bpm-music.com';
				$subject = "סיום קורס אבלטון במכללת BPM - " . $current_student['firstname'] . ' ' . $current_student['lastname'];

				$log_string = $current_student['firstname'] . ' ' . $current_student['lastname'];
						$log_course = $course->shortname;
						$log_status = ($current_student['is_eligible']) ? 'עבר' : 'נכשל';
						log_certificate($log_string .  ' - תעודת הסמכה בלבד - אבלטון');//$log_course . ' - ' . 
				        echo PHP_EOL;
				
				send_certificate_by_email($email, $file_arr, $subject, $docHtml, true); 			
			}
		}
		
	}
}


function bpm_prepare_certificate($current_student, $course, $cert_type) {
	$program_name;
	$course_type = get_course_type($course->shortname, $program_name);
	$cert_data_record = get_certification_data($current_student['id'], $course->parent_m_id);
	
	$html_template = fill_html_template($cert_type, $cert_data_record);

	// Create a new Pdf object with some global PDF options
	$pdf = new Pdf(array(
		'no-outline',         // Make Chrome not complain
		'margin-top'    => 0,
		'margin-right'  => 0,
		'margin-bottom' => 0,
		'margin-left'   => 0,

		// Default page options
		'disable-smart-shrinking',
		'binary' => realpath( dirname(dirname(dirname( __FILE__ )))) . '/blocks/bpm_utils/wkhtmltopdf'
	));
		
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

	if (!$pdf->saveAs($pdf_name)) {
		throw new Exception('Could not create PDF: '.$pdf->getError());
	} else {
		unlink('tempTemplate.html');
		return $pdf_name;
	}
}

function bpm_prepare_email_and_certificate_templates($current_student, $course, $meta=NULL) {

    $html_dom = new simple_html_dom();
	$email = $current_student['email']; //TODO comment when testing
// 	$email = 'erez@bpm-music.com';
	$subject = "סיום לימודים במכללת BPM - " . $current_student['firstname'] . ' ' . $current_student['lastname'];

	$log_string = $current_student['firstname'] . ' ' . $current_student['lastname'];
	if ($meta) { $log_string .= $meta;}
			$log_course = $course->shortname;
			$log_status = ($current_student['is_eligible']) ? 'עבר' : 'נכשל';
			log_certificate($log_string .  //$log_course . ' - ' . 
							  ' - ' . $log_status);
							  echo PHP_EOL;
    if (!($current_student['is_eligible'])) { //fail
			if (isset($meta) && $meta == ' - אין זכאות לתעודת הסמכה(אבלטון) ') {
				$message = get_message_template(false, 'fail_ableton_mid_program');
				$subject = str_replace("לימודים", "קורס אבלטון", $subject);
			} else {
				$message = get_message_template(false);
			}
    	    $html_message = $html_dom->load($message);
    	    
    	    if ($course->course_type == '0') {//clali
    	        $failure_details = get_program_failure_details($current_student, $course);
    	        
    	    } else if ($course->course_type == '2' || $course->course_type == '1') { //standalone && from program
    	        $failure_details = get_failure_details($current_student, $course->id);
    	    }
    	    
    	    $html_message->find('div[id=failureDetails]', 0)->innertext .= $failure_details;   	    
    	    $html_message->find('[id=studentName]', 0)->innertext = 'שלום ' . $current_student['firstname'] . ',';
            $docHtml = $html_message->save(); 
            $html_message->clear();
            unset($html_message);
            
            //don't send emails to non-eligibles, Shelly 29.7.20
			//send_certificate_by_email($email, NULL, $subject, $docHtml, false);
            
	} else { //pass, certificate/s
		$files = array();
		$bpm_cert_file = bpm_prepare_certificate($current_student, $course, 'BPM');
		array_push($files, $bpm_cert_file);
	    
		if (strpos($course->shortname, "אבלטון") !== false) {
			$ableton_cert_file = bpm_prepare_certificate($current_student, $course, 'ableton');          
			array_push($files, $ableton_cert_file);
		}
		
		if (strpos($course->shortname, "קיובייס") !== false) {
            $message = get_message_template(true, 'pass_cubase');
            $steinberg_cert_url = new moodle_url('/blocks/bpm_utils/cert_view.php', array('courseparentid' => $course->parent_m_id,
																	'courseid' => $course->id,
																	'userid' => $current_student['id'],
																	'ssdrequest' => 1,
																	'certtype' => 'cubase',
																	'branch' => $branch));
		} else {
		    $message = get_message_template(true);
		}
		$html_message = $html_dom->load($message);
		$html_message->find('[id=studentName]', 0)->innertext = 'שלום ' . $current_student['firstname'] . ',';
		$branch = (strpos($course->shortname, " חיפה") >0 ? "haifa" : '');
		$certificate_url = new moodle_url('/blocks/bpm_utils/cert_view.php', array('courseparentid' => $course->parent_m_id,
																	'courseid' => $course->id,
																	'userid' => $current_student['id'],
																	'ssdrequest' => 1,
																	'certtype' => 'BPM',
																	'branch' => $branch));
	
		$html_message->find('[id=certificateUrl]', 0)->href = $certificate_url;
		if (isset($steinberg_cert_url)) {
		    $html_message->find('[id=certificateUrl_steinberg]', 0)->href = $steinberg_cert_url;
		}
		
		$docHtml = $html_message->save(); 
		$html_message->clear();
		unset($html_message);
		send_certificate_by_email($email, $files, $subject, $docHtml, true); 
	}
	         
}

function check_es_requirement_for_course($course_name, $course_type) {
    global $BU_CFG;
    
    if ($course_type == '1') { //from program
        return false;
    } else if (strpos($course_name, 'חיפה') !== false) {
        return false;
    }
    else if (bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY_STRING, $program_name) ||
                bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY_STRING, $course_name) ||
                bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY__ATTENDANCE_SESSION_STRING, $course_name)) {
        return true;
    }
}

function get_category_name($cat_id) {
    global $DB;
    $sql = "SELECT name FROM mdl_course_categories WHERE id = $cat_id";
    
    return $DB->get_field_sql($sql);
}


function log_certificate($message=NULL, $course=NULL) {
    global $_GLOBALS;
    
    $today = date('m-d-Y');
    $log_file_name = 'certificates_log/certificates_' . $today . '.txt';
    ob_start();
    ob_flush();

    $current = file_get_contents($log_file_name);

    if ($course != null) {
        $url = new moodle_url('/course/view.php', ['id' => $course->id]);
        echo PHP_EOL . '<h3 style="margin-bottom:0"><a href="' . $url . 
                        '"><img src="https://my.bpm-music.com/local/BPM_pix/ak_favicon(5).ico" width="30" style="vertical-align:middle;margin-left:6px"></a>' . 
                        '<a href="https://eu15.salesforce.com/' . $course->sf_id . 
                        '"><img src="https://eu15.salesforce.com/favicon.ico" width="30" style="vertical-align:middle;margin-left:6px"/></a>' .
                        $course->shortname . "</h3>";
    } else if ($message != null) {
		$_GLOBALS['emails_sent'] = true;
        print_r($message);
    } else {
        //todo comment
        echo '<div dir="rtl">';
    }
    file_put_contents($log_file_name, $current . PHP_EOL . ob_get_flush());
    ob_end_flush();
}

function export_log($address) {
    $today = date('m-d-Y');
    $log_file_name = 'certificates_log/certificates_' . $today . '.txt';
    $current = nl2br(file_get_contents($log_file_name));
    $subject = "פירוט הפצת תעודות/הודעות כישלון לתאריך " . date('d/m/Y');
    $headers  = "To: " . $address . " <" . $address . ">"     . "\r\n";
    $headers = "From: מכללת BPM <noreply@bpm-music.com>"     . "\r\n";
    $headers .= "MIME-Version: 1.0"                           . "\r\n";
    $headers .= "Content-Type: text/html; charset=iso-8859-1" . "\r\n";

    $response = mail($address, $subject, $current, $headers);
	
	if($response) {
        return 'all good';
    } else {
        return 'error';
    };
}

