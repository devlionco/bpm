<?php

die(); // Comment out this line while testing

global $CFG, $sfsql;
require_once 'webservice.php';
require_once 'misc_scripts.php';
header('Content-type: text/plain');

    // try {
    //     bpm_populate_sf_account_link_in_users();
    //     print_r('bpm_populate_sf_account_link_in_users completed\n');
    // } catch(Exception $e) {
    //     echo 'fail bpm_populate_sf_account_link_in_users: ' .$e->getMessage();
    // }
    // try {
    //     temp_bpm_update_sf_account_with_moodle_ids();
    //     print_r('temp_bpm_update_sf_account_with_moodle_ids completed\n');
    // } catch(Exception $e) {
    //     echo 'fail temp_bpm_update_sf_account_with_moodle_ids: ' .$e->getMessage();
    // }
        
print_r('done\n');