<?php

/**
 * @package    block_course_details
 * @subpackage backup-moodle2
 */

require_once($CFG->dirroot . '/blocks/course_details/backup/moodle2/restore_course_details_stepslib.php'); // We have structure steps

/**
 * Specialised restore task for the course_details block
 * (has own DB structures to backup)
 *
 * TODO: Finish phpdocs
 */
 
class restore_course_details_block_task extends restore_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new restore_course_details_block_structure_step('course_details_structure', 'course_details.xml'));
    }

    public function get_fileareas() {
        return array(); // No associated fileareas
    }

    public function get_configdata_encoded_attributes() {
        return array(); // No special handling of configdata
    }

    static public function define_decode_contents() {
        return array();
    }

    static public function define_decode_rules() {
        return array();
    }
}

