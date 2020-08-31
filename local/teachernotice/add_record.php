<?php

require_once __DIR__ . '/../../config.php';
require_once('lib.php');

if (!empty($_POST)) {
	$course_id = $_POST["courseId"];
	$assignment_date = $_POST["assignmentDate"];
	$assignment_type = $_POST["assignmentType"];

	bpm_update_assignment_date($course_id, $assignment_date, $assignment_type);
	bpm_insert_teacher_notice($course_id, $assignment_date, $assignment_type);
	bpm_add_end_assignments_notice_records($course_id);
}

/**
 * Update due date for an assignment in the database.
 *
 * @param  int 	    $course_id 	     Course moodle id.
 * @param  string   $assignment_date Assignment due date.
 * @param  string   $assignment_type Assignment type.
 *
 * @global stdClass $DB 		  	 Moodle DataBase API.
 *
 */
function bpm_update_assignment_date($course_id, $assignment_date, $assignment_type) {
	global $DB;

	$assignment_name = \utils::bpm_get_assignment_prefix($assignment_type);
	$assignment_name = "'" . $assignment_name . "'";

	$sql = "UPDATE mdl_assign
			SET    duedate = $assignment_date
			WHERE  course = $course_id
			AND    name LIKE $assignment_name";

	$DB->execute($sql);
}

/**
 * Insert a teacher notice row to the database
 *
 * @param  int 	   $course_id 	         Course moodle id.
 * @param  int     $initial_notice_date  Notices should start sending a week from this date.
 * @param  string  $assignment_type      Assignment type.
 *
 * @global stdClass  $DB  Moodle DataBase API.
 *
 */
function bpm_insert_teacher_notice($course_id, $initial_notice_date, $assignment_type) {
	global $DB;

	$validate_sql = "SELECT id
				     FROM   mdl_local_teacher_notice
				     WHERE  courseid = $course_id
				     AND    type = $assignment_type
				     LIMIT 1";

	$notice_exists = $DB->get_field_sql($validate_sql);

	// If a notice row doesn't exists for this course and assignment, create it.
	if (!$notice_exists) {
		$assignment_name = \utils::bpm_get_assignment_prefix($assignment_type);
		$notices = array();
		$teacher_id = \utils::bpm_get_teacher_id($course_id);

    	$sql = "SELECT id
    			FROM   mdl_assign
    			WHERE  course = $course_id
    			AND    name like '$assignment_name'";

		$assignments = $DB->get_records_sql($sql);

		foreach ($assignments as $assignment) {
			$notice_date = get_date_for_notice($course_id, $assignment_type, $initial_notice_date);

			$notice = new stdClass();
			$notice->type = $assignment_type;
			$notice->courseid = $course_id;
			$notice->teacherid = $teacher_id;
			$notice->assignmentid = $assignment->id;
			$notice->originaldate = $initial_notice_date;
			$notice->lastnoticedate = $notice_date;

			$success = $DB->insert_record('local_teacher_notice', $notice, false);
		}
	} else {
		echo "Notice for course: " . $course_id . " of type " . $assignment_type . " already exists.<br>";
	}
}

/**
 * Add teacher notice records for end exam/project.
 *
 * @param  int      $course_id Course moodle id.
 *
 * @global stdClass $DB        Moodle DataBase API.
 *
 */
function bpm_add_end_assignments_notice_records($course_id) {
    global $DB;

    $sql = "SELECT a.name, c.enddate, c.fullname as coursename
            FROM  mdl_assign a
            JOIN  mdl_course_modules cm
            ON    a.course = cm.course 
            AND   a.id     = cm.instance
            JOIN  mdl_course c
            ON    c.id = a.course
            WHERE c.course              = $course_id
            AND   cm.deletioninprogress = 0
			AND   a.name rlike 'מבחן סיום|פרויקט סיום'
            ORDER BY a.name";

    // Fetch all valid end course assignments from the database and order by name so end exam is always first
    $assignments = $DB->get_records_sql($sql);

    $assignments_count = count($assignments);

    // Check to see if any assignment exists
    if ($assignments_count > 0) {
    	foreach ($assignments as $current) {
    		$assignment_type = utils::bpm_get_assignment_type($current->name);
    		bpm_insert_teacher_notice($course_id, $current->enddate, $assignment_type);
    	}
    }

  //   	// Used to convert all keys to numeric values for easier manipulation
		// $assignments_array = array_values($assignments);

		// $project_duedate_interval = "+1 month";

		// // Build array of special courses identifiers and iterate
		// $special_course_ids = array('סאונד', 'מבוא להפקה במידי', 'עיבוד ותזמור');
		// foreach ($special_course_ids as $identifier) {

		// 	// If course has the word סאונד or עיבוד ותזמור or מבוא להפקה במידי, increase duedate interval to 2 months			
		// 	if (strpos($assignments_array[0]->fullname, $identifier) !== false) {
  //   			$project_duedate_interval = "+2 month";
  //   			break;
  //   		}
		// }

  //   	$assignment_date = $assignments_array[0]->enddate;

  //   	// Add appropriate teacher notices according to valid assignments
		// if ($assignments_count == 1) {
		// 	if (substr_count($assignments_array[0]->name, 'מבחן סיום') > 0) {
		// 		bpm_insert_teacher_notice($course_id, $assignment_date, 3);
		// 	} else {
		// 		bpm_insert_teacher_notice($course_id, strtotime($project_duedate_interval, $assignment_date), 4);
		// 	}
		// } else {
		// 	bpm_insert_teacher_notice($course_id, $assignment_date, 3);
		// 	bpm_insert_teacher_notice($course_id, strtotime($project_duedate_interval, $assignment_date), 4);
		// }
}

/**
 * Calculate the date in which notices should start being sent
 * by the course and assignment ids.
 *
 * @param  int  $course_id  		 The course id
 * @param  int  $assignment_id  	 The assignment id
 * @param  int  $assignment_duedate  The assignment duedate
 *
 * @return int  The date notices should start being sent (lastnoticedate)
 *
*/
function get_date_for_notice($course_id, $assignment_type, $assignment_duedate) {
	$course_name = \utils::bpm_get_course_name($course_id);
	$project_date_interval = "+1 month";

	// Build array of special courses identifiers and iterate
	$special_course_ids = array('סאונד', 'מבוא להפקה במידי', 'עיבוד ותזמור');
	foreach ($special_course_ids as $identifier) {

		// If course has the word סאונד or עיבוד ותזמור or מבוא להפקה במידי, increase duedate interval to 4 months			
		if (substr_count($course_name, $identifier) > 0) {
			$project_date_interval = "+4 month";
			break;
		}
	}

	// If course is סאונד והקלטות ב' - BSP end test date should be a month
	//course סאונד והקלטות ב is now טכניקות הקלטה AND עיצוב סאונד ומבוא למיקס
	
	if ($assignment_type == 2 && 
	        (substr_count($course_name, 'סאונד והקלטות ב\' - BSP') > 0 OR
	        substr_count($course_name, 'טכניקות הקלטה - BSP\' - BSP') > 0 OR
	        substr_count($course_name, 'עיצוב סאונד ומבוא למיקס\' - BSP') > 0
	        )
	    
	    ) {
		return strtotime('+1 month', $assignment_duedate);
	}

	if ($assignment_type == 1 || $assignment_type == 2) {
		return strtotime('+1 week', $assignment_duedate);
	} else {
		return strtotime($project_date_interval, $assignment_duedate);
	}
}