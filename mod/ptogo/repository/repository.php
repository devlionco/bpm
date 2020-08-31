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
 * @package   mod_ptogo
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('listrepositories.php');

require_login();
$context = context_system::instance();

admin_externalpage_setup('activitysettingptogo');
require_capability('mod/ptogo:addrepository', $context);
global $PAGE, $OUTPUT, $DB;

$repositories = $DB->get_records('ptogo_repository');

$mform = new mod_ptogo_repository_list($repositories);

echo $OUTPUT->header();
echo $OUTPUT->heading('<img src="../pix/Logo_Presentations2Go_275x70.png">');
// echo $OUTPUT->heading('<Presentations2go');


$mform->display();

echo $OUTPUT->footer();
