<?php

defined('MOODLE_INTERNAL') || die();
 
class local_salesforce_observer {
	
	public static function course_criteria_review(\core\event\course_completed $event) {
		// global $DB, $CFG, $sfsql;
		// require_once(dirname(__FILE__) . '/../../../config.php');
		// require_once(dirname(__FILE__) . '/../webservice.php');
		// $eventdata = $event->get_record_snapshot('course_completions', $event->objectid);
		// $userid = $event->relateduserid;
		// $courseid = $event->courseid;
		// check_student_status($courseid, $userid, BPM_SF::NOTINSERT);
	}
}