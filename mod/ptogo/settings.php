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

/**
 * Admin Settings.
 *
 * @package   mod_ptogo
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');
require_once("$CFG->dirroot/mod/ptogo/lib.php");
require_once("$CFG->dirroot/mod/ptogo/locallib.php");

defined('MOODLE_INTERNAL') || die();

// Settings are handled in an external form dir.
$settings = new admin_externalpage('activitysettingptogo',
    get_string('modulename', 'ptogo'),
    new moodle_url('/mod/ptogo/repository/repository.php'),
    'mod/ptogo:addrepository');
