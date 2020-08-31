<?php
if (!isset($_GET['exec_mode']) && $_GET['exec_mode'] != 'user') {
    define('CLI_SCRIPT', true);
}

global $CFG, $sfsql;
require_once 'webservice.php';

    try {
        bpm_update_grades_and_attendance_in_sf();
        print_r('bpm_update_grades_and_attendance_in_sf completed\n');
    } catch(Exception $e) {
        echo 'fail bpm_update_grades_and_attendance_in_sf: ' .$e->getMessage();
    }
        
    print_r('done\n');