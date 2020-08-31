<?php
 
require_once($CFG->dirroot . '/blocks/course_details/backup/moodle2/backup_course_details_stepslib.php'); // Because it exists (must)
 
/**
 * course_details backup task 
 */
class backup_course_details_block_task extends backup_block_task {
 
    protected function define_my_settings() {
        // No particular settings for this block
    }
 
    protected function define_my_steps() {
        // course_details only has one structure step
		$this->add_step(new backup_course_details_block_structure_step('course_details_structure', 'course_details.xml'));
    }
	
    public function get_fileareas() {
        return array('content');
    }

    public function get_configdata_encoded_attributes() {
        return array('text'); 
    }
	
    static public function encode_content_links($content) {
        return $content;
    }
}