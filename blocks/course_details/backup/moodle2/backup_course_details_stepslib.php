<?php
 
/**
 * Define all the backup steps that will be used by the backup_course_details_block_task
 */
 
 class backup_course_details_block_structure_step extends backup_block_structure_step {
 
    protected function define_structure() {
		global $DB, $COURSE;
		
        $course_details = new backup_nested_element('course_details', array('id'), array('idnumber', 'courseid', 'sfcodeproduct', 'course_open_to_sale', 'capacityclass', 'meetings_amount', 'several_days'));
		
		$sql = 'SELECT idnumber, cd.* 
				FROM {course_details} cd
				JOIN {course} c ON cd.courseid = c.id
				WHERE c.id = ?';
		$course_details->set_source_sql($sql, array(backup::VAR_COURSEID));
		return $this->prepare_block_structure($course_details);
    }
}