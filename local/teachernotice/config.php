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
 * @package local/teachernotice
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $TN_CFG;

$TN_CFG = new stdClass();

// Message types
$TN_CFG->message_types = array(
	'mid_exam' => '1',
	'final_exam' => '2',
	'mid_project' => '3',
	'final_project' => '4');

// Moodle user id for system messages
$TN_CFG->bpm_bot_id = '739'; // Production bot id
//$TN_CFG->bpm_bot_id = '609'; // Test bot id

// Moodle user id for the student service department
$TN_CFG->bpm_ssd_id = '121'; // Same id in both test and production environments

// BPM pedagogical manager id for system messages
$TN_CFG->BPM_PMAN_ID = '44';

$TN_CFG->BPM_LIMUDIM_MAN_ID = '45';

// BPM marketing manager id for system messages
$TN_CFG->BPM_MM_ID = '1425';

$TN_CFG->BPM_SF_CLIENT_ID   = '3MVG9Rd3qC6oMalW9GpkCq9MrJzk0GBpCgI7tR9_rCh55bN9GYp_rh.Zt8U_QwWaLQKUcbRXNFk9bmchrOqK0';
$TN_CFG->BPM_SF_USER_SECRET = '2097469368649063425';
$TN_CFG->BPM_SF_USER_NAME   = 'aviv@bpm-music.com';
$TN_CFG->BPM_SF_USER_PASS   = '1q2w3e4r';
$TN_CFG->BPM_SF_ACCESS_URL  = 'https://login.salesforce.com/services/oauth2/token';
$TN_CFG->BPM_SF_COURSE_URL  = '/services/data/v40.0/sobjects/Course__c/';
$TN_CFG->BPM_SF_QUERY_URL   = '/services/data/v40.0/query/?q=';