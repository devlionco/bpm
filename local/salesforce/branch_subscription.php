<?php
require_once __DIR__ . '/../../config.php';
require_once 'config.php';

function bpm_add_branch_forum_subscription($userid, $courseid) {
    global $DB, $S_CFG;
    $branch_names = array("חיפה", "אונליין");
    $coursesql = 'SELECT shortname FROM mdl_course WHERE id = ' . $courseid;
    $coursename = $DB->get_record_sql($coursesql);
    
    //echo $coursename->shortname;
    if ($branch_name = substring_in_array($branch_names, $coursename->shortname)) { //any specification of branch
        $branch_name = bpm_translate($branch_name);
        $forum_id = $S_CFG->branches[ $branch_name ]->NEWSFORUM_ID;
    } else { //default (tel aviv)
        
       $forum_id = $S_CFG->branches['Tel_Aviv']->NEWSFORUM_ID;
    }
    
    //check if subscription exists
    $subscription_sql = 'SELECT * FROM mdl_forum_subscriptions WHERE userid = ' . $userid . ' AND forum = ' . $forum_id;
    if (!$existing_sub = $DB->get_record_sql($subscription_sql)) {
        $insert_sub_sql = "INSERT INTO mdl_forum_subscriptions(userid, forum) VALUES ($userid, $forum_id)";
        $DB->execute($insert_sub_sql);
    }
}

function substring_in_array($haystack_arr, $needle) {
    foreach ($haystack_arr as $haystack_item) {
        if (stripos($needle, $haystack_item) !== FALSE) {
            return $haystack_item;
        } else {
            $result = false;
        }
    }
    return $result;
}

function bpm_translate($input) {
    switch ($input) {
        case 'חיפה':
            $output = "Haifa";
            break;
        case 'אונליין':
            $output = "Online";
            break;
        default:
            $output = $input;
    }
    return $output;
}