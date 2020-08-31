<?php

// This file is part of the bpm_utils block for Moodle - http://moodle.org/
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
 * Config file to contain frequently used values and other settings
 *
 * @author  Ben Laor, BPM
 * @package blocks/bpm_utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $BU_CFG;

$BU_CFG = new stdClass();

// Moodle user id for system messages
$BU_CFG->BPM_BOT_ID = '739'; // Production
//$BU_CFG->BPM_BOT_ID = '609'; // Test

// Moodle user id for the student service department
$BU_CFG->BPM_SSD_ID = '121'; // Same id in both test and production environments
$BU_CFG->BPM_IRIS = '45'; // Same id in both test and production environments
$BU_CFG->BPM_TE = '1501'; // Time extension managing mail

// User data info field id to indicate if user passed electric safety course
//$BU_CFG->PASSED_ELECTRIC = '1'; // Test
$BU_CFG->PASSED_ELECTRIC = '6'; // Production

// Students services department role id
//$BU_CFG->SSD_ROLE_ID = '13'; // Test
$BU_CFG->SSD_ROLE_ID = '17'; // Production

$BU_CFG->COURSE_MANAGER_ROLE_ID = '2';
$BU_CFG->COURSE_TYPES  = array("program" => 1, "standalone" => 2);   // Course types
$BU_CFG->PROGRAM_NAMES = array("BSP", "EMP", "GFA", "זמר יוצר", "DMP"); // Course program names
$BU_CFG->ELECTRIC_MANDATORY_STRING = array('BSP','EMP','DMP','DJ','רדיו', 'קיובייס');
$BU_CFG->ELECTRIC_MANDATORY__ATTENDANCE_SESSION_STRING = array('קיובייס');
$BU_CFG->MUSIC_COURSES_NAMES = array("תיאוריה","תולדות","הרמוניה","פיתוח שמיעה","מוסיקה א","עיבוד","מוסיקה ב","יסודות בהלחנה","קומפוזיציה","תיווי","תזמור","מקלדת");
$BU_CFG->CELLACT = array("ENDPOINT" => 'http://la.cellactpro.com/http_req.asp?',
						 "CREDENTIALS" => 'FROM=bpmmusic&USER=bpmmusic&PASSWORD=G81hDNfB&APP=LA&CMD=sendtextmt&SENDER=0557003672&CONTENT=',
						 "RECIEVER" => '&TO=');
$BU_CFG->EXERCISE_BANK_COURSEPARENT_MATCHING_IDS = array(76, 100, 659,81, 98, 133, 68, 95, 108, 119, 129,79, 83, 93,72,68, 95, 108, 119, 129,77, 101, 699);
$BU_CFG->STAFF_ROLES = array(1, 2, 3, 4, 9, 11, 12, 13, 17, 18, 19);
