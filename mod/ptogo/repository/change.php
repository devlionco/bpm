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
 * File to call when repositories are changed.
 *
 * @package   mod_ptogo
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_once("changerepositories.php");

// If these are both not set we assume add.
$id = optional_param('repository', 0, PARAM_INT);
$action = optional_param('action', 'add', PARAM_TEXT);
require_login();

$context = context_system::instance();

admin_externalpage_setup('activitysettingptogo');
require_capability('mod/ptogo:addrepository', $context);
global $PAGE, $OUTPUT, $DB;

// If we just delete an entry, don't load anything else.
// TODO: We need to delete the instance too or we get failures.
if($action == 'delete') {
    // Check what video_ids are stored with my repository_id.
    $all_items = $DB->get_records('ptogo', array('repository_id' => $id), '', 'video_id', 0, 0);
    foreach($all_items as $item) {
        $DB->delete_records('ptogo_items', array('video_id' => $item->video_id));
    }
    $DB->delete_records('ptogo', array('repository_id' => $id));
    $DB->delete_records('ptogo_repository', array('id' => $id));
    redirect('repository.php');
}

$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/mod/ptogo/repository/change.php');

// Make form object defined in changerepositories.
$mform = new mod_ptogo_repository_change();

// Check if we got any values from form.
if ($mform->is_cancelled()) {
    redirect('repository.php'); // Go home
}
$data = $mform->get_data();
if($data) {
    $record = new stdClass();
    $record->title = $data->title;
    $record->description = $data->description;
    $record->serverurl = $data->serverurl;
    $record->secretkey = $data->secretkey;
    $record->ptogo_group = $data->ptogo_group;
    $record->basequery = $data->baseQuery;
    $record->expiration = $data->expiration;
    $record->id = $data->id;

    if($data->action == 'add') {
        $DB->insert_record('ptogo_repository', $record);
    } else if($data->action == 'edit') {
        $DB->update_record('ptogo_repository', $record);
    }
    // Go home after doing the work.
    redirect('repository.php');
}

echo $OUTPUT->header();
echo $OUTPUT->heading('<img src="../pix/Logo_Presentations2Go_275x70.png">');
// echo $OUTPUT->heading('<Presentations2go');

$formdata = $DB->get_record('ptogo_repository', array("id" => $id));
if(!$formdata) $formdata = new stdClass(); // Initiate object if no records are found.
$formdata->action = $action; // Prepopulate information from clicked link.

$mform->set_data($formdata);
$mform->display();

echo $OUTPUT->footer();
