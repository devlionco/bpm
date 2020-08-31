<?php

// This file is part of the Teacher notice plugin for Moodle - http://moodle.org/
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

/**
 * Config file to contain frequently used strings and other settings
 *
 * @author  Ben Laor, BPM
 * @package local/absencenotice
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $AN_CFG;

$AN_CFG = new stdClass();

// Moodle user id for system messages
$AN_CFG->BPM_BOT_ID = '739'; // Production bot id
//$AN_CFG->BPM_BOT_ID = '609'; // Test bot id

// Moodle user id for the student service department
$AN_CFG->BPM_SSD_ID = '121'; // Same id in both test and production environments

$AN_CFG->BPM_SF_CLIENT_ID   	 = "3MVG9Rd3qC6oMalW9GpkCq9MrJzk0GBpCgI7tR9_rCh55bN9GYp_rh.Zt8U_QwWaLQKUcbRXNFk9bmchrOqK0";
$AN_CFG->BPM_SF_USER_SECRET 	 = '2097469368649063425';
$AN_CFG->BPM_SF_USER_NAME   	 = 'aviv@bpm-music.com';
$AN_CFG->BPM_SF_USER_PASS   	 = '1q2w3e4r';
$AN_CFG->BPM_SF_ACCESS_URL  	 = 'https://login.salesforce.com/services/oauth2/token';
$AN_CFG->BPM_SF_CASE_URL    	 = "/services/data/v40.0/sobjects/Case/";
$AN_CFG->BPM_SF_REGISTRATION_URL = '/services/data/v20.0/sobjects/Registration/';
$AN_CFG->BPM_SF_QUERY_URL   	 = '/services/data/v40.0/query/?q=';
$AN_CFG->BPM_EMAIL_FOOTER 		 = '<body><div dir="rtl" style="text-align:right;"><br><span style="font-style:italic">אין להשיב לדוא"ל זה</span><br><br> בברכה,<br>מכללת BPM<p><a href="http://www.bpm-music.com/" style="color:rgb(17,85,204);font-size:12.8px;text-align:right" target="_blank">BPM Website</a><span style="color:rgb(136,136,136);text-align:right;font-size:small">&nbsp;|&nbsp;</span><font style="color:rgb(136,136,136);text-align:right;font-size:small" size="2"><a href="https://www.facebook.com/BPM.College" style="color:rgb(17,85,204);text-align:start" target="_blank">Facebook</a>&nbsp;<span style="color:rgb(0,0,0);text-align:start">|&nbsp;</span><a href="https://instagram.com/bpmcollege/" style="color:rgb(17,85,204);text-align:start" target="_blank">Instagram</a>&nbsp;|&nbsp;<a href="https://soundcloud.com/bpmsoundschool" style="color:rgb(17,85,204)" target="_blank">Soundcloud</a>&nbsp;|&nbsp;<a href="https://www.youtube.com/user/BPMcollege" style="color:rgb(17,85,204)" target="_blank">Youtube</a></font></p><img src="https://my.bpm-music.com/local/BPM_pix/footer2018/mail_sign_heb.png" style="border:none" width="500"></div></body>';
$AN_CFG->CELLACT = array("ENDPOINT" => 'http://la.cellactpro.com/http_req.asp?',
						 "CREDENTIALS" => 'FROM=bpmmusic&USER=bpmmusic&PASSWORD=G81hDNfB&APP=LA&CMD=sendtextmt&SENDER=0557003672&CONTENT=',
						 "RECIEVER" => '&TO=');