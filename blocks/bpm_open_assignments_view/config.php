<?php
// This file is part of the BPM Open Assignments view block plugin for Moodle - http://moodle.org/
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
 * @package blocks/bpm_open_assignments_view
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $BCV_CFG;

$BCV_CFG = new stdClass();

// Moodle user id for system messages
//$BCV_CFG->BPM_BOT_ID = '739'; // Production
$BCV_CFG->BPM_BOT_ID = '609'; // Test

// Moodle user id for the student service department
$BCV_CFG->BPM_SSD_ID = '121'; // Same id in both test and production environments