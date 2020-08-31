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
require('../../config.php');
require_once("$CFG->dirroot/mod/ptogo/locallib.php");

$id = required_param('id', PARAM_INT);


list ($course, $cm) = get_course_and_cm_from_cmid($id);

$ptogo = $DB->get_record('ptogo', array('id'=> $cm->instance), '*', MUST_EXIST);

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);
has_capability('mod/ptogo:view', $context);

global $PAGE, $DB, $CFG, $OUTPUT;
$PAGE->set_url($CFG->wwwroot . '/mod/ptogo/view.php', array("id"=>$id));
$PAGE->set_title($course->shortname.': '.$ptogo->name);
$PAGE->set_heading($course->fullname);
$PAGE->requires->css(new moodle_url("/mod/ptogo/view.css"));
$PAGE->set_pagelayout("incourse");

global $OUTPUT;

echo $OUTPUT->header();
echo ptogo_process_response($course->id, $cm->instance);

echo $OUTPUT->footer();
