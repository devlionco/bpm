<?php
// This file is part of the BPM Utilities block plugin for Moodle - http://moodle.org/
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
 * Code fragment to define the version of bpm_utils
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author  Ben Laor, BPM
 * @package blocks/bpm_utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

$plugin->version = 2019061202;  // The current plugin version (Date: YYYYMMDDXX)
$plugin->release = '3.2+';
$plugin->requires = 2016120501; // Moodle 3.2.1+.
$plugin->component = 'block_bpm_utils';