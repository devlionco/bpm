<?php

define('CLI_SCRIPT', true);

global $CFG, $sfsql;
require_once 'webservice.php';

try {
        send_mails_before_start_course();
        print_r('send_mails_before_start_course completed\n');
} catch(Exception $e) {
    echo 'fail send_mails_before_start_course: ' .$e->getMessage();
}

print_r('done\n');