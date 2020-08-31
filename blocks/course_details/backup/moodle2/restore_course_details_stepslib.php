<?php

/**
 * Structure step to restore course_details block
 */
 
class restore_course_details_block_structure_step extends restore_structure_step {
 
    protected function define_structure() {
        $paths = array();
        $paths[] = new restore_path_element('course_details', '/block/course_details');
        return $paths;
    }
 
    protected function process_course_details($data) {
        global $DB;
		
		if(!$DB->record_exists('course_details', array('courseid' => $this->get_courseid()))) {
			$data = (object)$data;
			$data->courseid = $this->get_courseid();
			$data->coursefather = $data->idnumber;
			$data->sfcodeproduct = -1;
			unset($data->id);
			$newitemid = $DB->insert_record('course_details', $data);
		}


    }
}