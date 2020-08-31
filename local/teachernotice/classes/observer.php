<?php

namespace local_teachernotice;

defined('MOODLE_INTERNAL') || die();

require_once('utils.php');

class observer
{
	/**
	 * Add teacher notice records for end exam/project.
	 *
	 * @param  \core\event\course_updated $event The course update event
	 *
	 * @global stdClass $DB Moodle DataBase API.
	 *
	 */
	public static function bpm_sync_course_enddate_with_assignments(\core\event\course_updated $event) {
	    global $DB;
    
        $event_data = $event->get_data();
	    $course_id = $event_data['courseid'];
    
    	// Fetch course data
	    $course_sql = "SELECT *
                       FROM mdl_course
                       WHERE id = $course_id";      
        $course_data = $DB->get_record_sql($course_sql);

	    $assignments_sql = "SELECT a.id, a.name, a.duedate, c.enddate, c.fullname as coursename
	            			FROM  mdl_assign a
				            JOIN  mdl_course_modules cm
				            ON    a.course = cm.course 
				            AND   a.id     = cm.instance
				            JOIN  mdl_course c
				            ON    c.id = a.course
				            WHERE c.id = $course_id
				            AND   cm.deletioninprogress = 0
				            AND   a.name rlike 'מבחן סיום|פרויקט סיום'
				            ORDER BY a.name";

	    // Fetch all valid end course assignments from the database and order by name so end exam is always first
	    $assignments = $DB->get_records_sql($assignments_sql);
	    $assignments_count = count($assignments);

	    // Check to see if any assignment exists
	    if ($assignments_count > 0) {
	        $assignment_duedate_interval = "+1 month";

	        // Build array of special courses identifiers and iterate
	        $two_months_courses = array('סאונד', 'מבוא להפקה במידי', 'עיבוד ותזמור');
	        $two_weeks_courses = array('רדיו', 'קריינות');

	        // If course has one of the special keyword in the name increase interval to 2 months  
	        foreach ($two_months_courses as $special_name) {      
	            if (strpos($course_data->fullname, $special_name) !== false) {
	                $assignment_duedate_interval = "+2 month";
	                break;
	            }
	        }

	        // If course has one of the special keyword in the name increase interval to 2 weeks  
	        foreach ($two_weeks_courses as $special_name) {      
	            if (strpos($course_data->fullname, $special_name) !== false) {
	                $assignment_duedate_interval = "+2 week";
	                break;
	            }
	        }

	        // For each assignmnet check for existing duedate and update accordingly
	        foreach ($assignments as $assignment) {
	        	$assignment_new_duedate = $course_data->enddate;
	            if (strpos($assignment->name, "פרויקט") !== false)  {
	            	if (strpos($course_data->fullname, 'קיובייס') == false && strpos($course_data->fullname, 'אבלטון') == false) {
	            		$assignment_new_duedate = strtotime($assignment_duedate_interval, $course_data->enddate);
	            	}
	            }
	            \utils::bpm_update_assignment_duedate($assignment->id, $assignment_new_duedate);
	        }
	    }
	}
}