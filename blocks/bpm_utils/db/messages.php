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

defined('MOODLE_INTERNAL') || die();

$messageproviders = array (
    'ssd_cert_request_message' => array (
        'capability'  => 'block/view'
    ),
    'extension_request_message' => array (
        'capability'  => 'block/view'
    ),
    'extension_response_message' => array (
        'capability'  => 'block/view'
    ),
    'student_notice_content_message' => array (
    	'capability' => 'course/view'
    )
);