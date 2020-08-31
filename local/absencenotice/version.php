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
 * Code fragment to define the version of absencenotice
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author  Ben Laor, BPM
 * @package local/absencenotice
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

$plugin->version = 2020080500; //2018062800;  // The current plugin version (Date: YYYYMMDDXX)
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '3.2+';
$plugin->requires = 2014051200; // Moodle 2.7+.
$plugin->component = 'local_absencenotice';