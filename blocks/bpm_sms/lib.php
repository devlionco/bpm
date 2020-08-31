<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/* SMS Notifier Block
 * SMS notifier is a one way SMS messaging block that allows managers, teachers and administrators to
 * send text messages to their student and teacher.
 * @package blocks
 * @author: Azmat Ullah, Talha Noor
 * @date: 06-Jun-2013
*/

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/cellact.php');
// Send SMS pk Api Function
/**
 * This function will send the SMS using sendbpm_sms.pk.API is only for Pakistan's users.
 *
 * @param int   $to  User id
 * @param string $msg  Message Text
 * @return String $status return will shows the status of message.
 */
function send_bpm_sms($to, $msg) {
    global $CFG;
    /* THE SMS API WORK BEGINS HERE */
    require_once('bpm_sms_api/bpm_sms.php');

    $apikey=$CFG->block_bpm_sms_apikey;         // API Key.

    $bpm_sms = new sendbpm_smsdotpk($apikey);	    // Making a new sendbpm_sms dot pk object. 
    
    // isValid.
    if ($bpm_sms->isValid()) {
        $status = get_string('valid_key', 'block_bpm_sms');
    } else {
        $status = "KEY: " . $apikey . " IS NOT VALID";
    }
    $msg = stripslashes($msg);
	
    // SEND SMS.
    if ($bpm_sms->sendbpm_sms($to, $msg, 0)) {
    	$status = get_string('sent', 'block_bpm_sms');
    } else {
        $status = get_string('error', 'block_bpm_sms');
    }
    return $status;
}

function send_bpm_sms_cellact($to, $msg) {
    global $CFG;
    /* THE SMS API WORK BEGINS HERE */
    require_once('bpm_sms_api/bpm_sms.php');

    $username = $CFG->block_bpm_sms_api_username;
    $password = $CFG->block_bpm_sms_api_password;
    $bpm_sms = new CellactHttpRequest($username, $username, $password); 
    
    $msg = stripslashes($msg);
    $status = $bpm_sms->sendRequest("0557003672", $to, $msg);
	
    // SEND SMS.
    $status = get_string('sent', 'block_bpm_sms');

    return $status;
}


/**
 * This function will send the SMS using Clickatells API, by this API Users can send international messages.
 *
 * @param int   $to  User id
 * @param string $msg  Message Text
 * @return Call back URL through clickatell
 */
function send_bpm_sms_clickatell($to, $message) {
    global $CFG;
    /*User Numbers*/
    $numbers = '';
    foreach($to as $num){
        if($numbers == '') {
            $numbers =  $num;
        }
        else {
            $numbers .=  ','.$num;
        }
    }

    // Usernames.
    $username = $CFG->block_bpm_sms_api_username;
    // Password
    $password = $CFG->block_bpm_sms_api_password;
    // SMS API.
    $api_id = $CFG->block_bpm_sms_apikey;
    // Send Sms.
    $url = "http://api.clickatell.com/http/sendmsg?user=".$username."&password=".$password."&api_id=".$api_id."&to=".$numbers."&text=".$message;
    redirect($url);
}

function block_bpm_sms_print_page($bpm_sms) {
    global $OUTPUT, $COURSE;
    $display = $OUTPUT->heading($bpm_sms->pagetitle);
    $display .= $OUTPUT->box_start();
    if($bpm_sms->displaydate) {
        $display .= userdate($bpm_sms->displaydate);
    }
    if($return) {
        return $display;
    } else {
        echo $display;
    }
}

/**
 * This function will return the message template.
 *
 * @param int   $to  Message id
 * @return string $result->msg return message template on the base of message id
 */
function get_msg($id) {
    global $DB;
    $result = $DB->get_record_sql('SELECT cju.j_id, cj.job FROM {competency_job} AS cj inner join {competency_job_user} AS cju ON cj.id = cju.j_id
                                  WHERE cju.u_id = ?', array($id));
    return $result->msg;
}
