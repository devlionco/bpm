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
defined('MOODLE_INTERNAL') || die(); // TODO: check.

/**
 * https://github.com/moodle/moodle/blob/master/lib/moodlelib.php#L390
 * @uses FEATURE_MOD_INTRO
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function ptogo_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:               return true;;
        // case FEATURE_BACKUP_MOODLE2:          return true; // TODO: Implement backup.
        case FEATURE_SHOW_DESCRIPTION:        return true;
        default: return null;
    }
}

function ptogo_add_instance($data) {

    global $DB, $CFG;
    $response = $DB->get_field('ptogo_items', 'MAX(video_id)', array(), $strictness=IGNORE_MULTIPLE);
    $videoid = $response + 1;

    // TODO: Form field is named incorrect, maybe change.
    $data->additional_query = $data->baseQuery;

    if($data->listitem === "list") {
        $data->video_id = null;
    } else {
        $items = explode(';', $data->ptogo_item_id);
        $data->video_id = $videoid;
        for($i=0;$i<count($items);$i++){
            $item = new stdClass();
            $item->item_id = $items[$i];
            $item->video_id = $videoid;
            // For performance this could be done at once.
            $DB->insert_record('ptogo_items', $item);
        }
    }

    // require_once("$CFG->libdir/resourcelib.php"); // I don't know why we'd use that.
    
    return $DB->insert_record('ptogo', $data);

}

function ptogo_update_instance($data) {
    global $DB, $CFG;

    $result = new stdClass();
    $result->repository_id = $data->repository_id;
    $result->additional_query = $data->baseQuery;
    $result->title = $data->title;
    $result->video_id = null;
    $result->course = $data->course;
    $result->name = $data->name;
    $result->intro = $data->intro;
    $result->introformat = FORMAT_HTML;
    $result->id = $data->id;
    
    if($data->listitem === "list") {
        $result->video_id = null; // If we want to display a list we don't want a video_id.
    } else {
        // Check if current instance has already a video_id.
        $currentvalues = $DB->get_record('ptogo', array('id'=>$result->id), $fields='video_id', $strictness=IGNORE_MISSING);
        $videoid = $currentvalues->video_id;

        if (isset($videoid)) {
            // Remove everything from ptogo_items where video_id == $videoid.
            $DB->delete_records('ptogo_items', array('video_id'=>$videoid));
        } else {
            $response = $DB->get_field('ptogo_items', 'MAX(video_id)', array(), $strictness=IGNORE_MULTIPLE);
            $videoid = $response + 1;
        }
        
        $items = explode(';', $data->ptogo_item_id);
        
        // Run through all items we selected and update with $videoid in ptogo_items.
        foreach($items as $item) {
            $newitem = new stdClass();
            $newitem->item_id = $item;
            $newitem->video_id = $videoid;
            $DB->insert_record('ptogo_items', $newitem); // TODO: Make it bulk insert.
        }
        
        $result->video_id = $videoid;

    }
    // require_once("$CFG->libdir/resourcelib.php"); // I don't know why we'd use that.
    // $cmid = $data->coursemodule; // Not sure if we need that line.

    return $DB->update_record('ptogo', $result);

}

function ptogo_delete_instance($id) {
    global $DB;
    
    // Find video_id that is connected.
    $currentvalues = $DB->get_record('ptogo', array('id'=>$id), $fields='video_id', $strictness=IGNORE_MISSING);
    $DB->delete_records('ptogo_items', array('video_id'=>$currentvalues->video_id));
    return $DB->delete_records('ptogo', array('id'=>$id));

}


/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function ptogo_get_coursemodule_info($coursemodule) {
    global $DB;
    global $CFG;
       
        $result = new cached_cm_info();

        $fields = 'id, title, intro, course';
        if (!$ptogo = $DB->get_record('ptogo', array('id' => $coursemodule->instance), $fields)) {
            return false;
        }

        $result->name = $ptogo->title;

        if ($coursemodule->showdescription) {
            // Print intro in course listing. This is cached.
            $result->content = $ptogo->intro;

            // TODO: Ask if this is a usefull feature.
            // Add videos to cached course view. This breaks after expiration
            //require_once("$CFG->dirroot/mod/ptogo/locallib.php");
            //$result->content .= ptogo_process_response($ptogo->course, $ptogo->id, 'embed');
            //$result->content .= ptogo_process_response($ptogo->course, $ptogo->id, 'embed');
        }
        
        // Template from page.
        /*if ($coursemodule->showdescription) {
            $info->content = format_module_intro('page', $page, $coursemodule->id, false);
        }*/
    
    

    return $result;
}


/**
 * When we print in course listing but don't want stuff cached.
 * @param object $coursemodule
 */
function ptogo_cm_info_view($coursemodule) {
    global $CFG;
    global $DB;

        if ($coursemodule->showdescription) {
        $fields = 'id, title, intro, course';
        if (!$ptogo = $DB->get_record('ptogo', array('id' => $coursemodule->instance), $fields)) {
            return false;
        }
        $result = '';
        // TODO: Ask if this is a usefull feature.
        // Add videos directly to course view. This takes a really long time...
        require_once("$CFG->dirroot/mod/ptogo/locallib.php");
        $result = ptogo_process_response($ptogo->course, $ptogo->id, 'embed');
        //$coursemodule->content = $result;

        $coursemodule->set_after_link($result); //$info->content = format_module_intro('page', $page, $coursemodule->id, false);
    }
}

