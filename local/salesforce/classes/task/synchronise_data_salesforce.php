<?php
namespace local_salesforce\task;

class synchronise_data_salesforce extends \core\task\scheduled_task {      
    public function get_name() {
        // Shown in admin screens
        return get_string('sync_all', 'local_salesforce');
    }
                                                                     
    public function execute() {       
		global $CFG, $sfsql;
		require_once __DIR__ . '/../../webservice.php';
        try {
    		upsert_classes_to_moodle();
    		print_r('upsert_classes_to_moodle completed\n');
        } catch(Exception $e) {
            echo 'fail upsert_classes_to_moodle: ' .$e->getMessage();
        }
        
        try {
    		insert_objects_to_sf();
    		print_r('insert_objects_to_sf completed\n');
        } catch(Exception $e) {
            echo 'fail insert_objects_to_sf: ' .$e->getMessage();
        }
        
        try {
    		upsert_enrollments_to_moodle();
    		print_r('upsert_enrollments_to_moodle completed\n');
        } catch(Exception $e) {
            echo 'fail upsert_enrollments_to_moodle: ' .$e->getMessage();
        }
        
        try {
    		show_all_modules_course();
    		print_r('show_all_modules_course completed\n');
        } catch(Exception $e) {
            echo 'fail show_all_modules_course: ' .$e->getMessage();
        }
        
        try {
    		check_student_status_endcourse();
    		print_r('check_student_status_endcourse completed\n');
        } catch(Exception $e) {
            echo 'fail check_student_status_endcourse: ' .$e->getMessage();
        }
        
        try {
    		check_changes_in_student_status();
    		print_r('check_changes_in_student_status completed\n');
        } catch(Exception $e) {
            echo 'fail check_changes_in_student_status: ' .$e->getMessage();
        }
        
		print_r('done\n');
	}  
}
