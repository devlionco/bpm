<?php
// This file is part of the BPM Course view block for Moodle - http://moodle.org/
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
 * Class definition for the bpm_course_view block
 *
 * @author  Ben Laor, BPM
 * @package blocks/bpm_course_view
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/config.php");

class block_bpm_course_view extends block_base {
    public function init() {
        $this->title = get_string('bpm_course_view', 'block_bpm_course_view');
    }

    public function get_content() {
        global $USER;

        // Init to avoid moodle warnings.
        $this->content = new stdClass;
        $this->content->text = '';

        $courses_array = $this->bpm_get_courses_arrays($USER->id);
        $this->bpm_mark_attendance_on_courses($courses_array);

        if (count($courses_array[0]->list) > 0) {
            $this->content->text .= $this->bpm_get_courses_details_html($courses_array[0]->list, 'קורסים בהם אני מרצה', true);
        }
        if (count($courses_array[1]->list) > 0) {
            $this->content->text .= $this->bpm_get_courses_details_html($courses_array[1]->list, 'קורסים בהם אני מרצה מחליף');
        }
                          
        return $this->content;
    }

    public function has_config() {
        return false;
    }
    
    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block_'. $this->name(); // Append our class to class attribute
        return $attributes;
    }

    /**
     * Fetch all courses where the user is either a teacher or a substitute teacher.
     *
     * @param  int  $user_id  User moodle id.
     *
     * @global stdClass  $DB  Moodle DataBase API.
     *
     * @return array  Array containing two objects for teacher and substitute teacher courses data.
     *
     */
    public function bpm_get_courses_arrays($user_id) {
        global $DB;

        // Important to have a unique column as the first column, to iterate over the results
        $sql = "SELECT crs.id, r.shortname, att.id as att_id, crs.fullname, crs.startdate, crs.enddate
                FROM mdl_course crs
                JOIN mdl_context ctx         ON crs.id = ctx.instanceid
                JOIN mdl_role_assignments ra ON ctx.id = ra.contextid
                JOIN mdl_user usr            ON usr.id = ra.userid
                JOIN mdl_role r              ON r.id   = ra.roleid
                LEFT JOIN mdl_attendance att      ON crs.id = att.course
                WHERE usr.id = $user_id
                AND r.shortname in ('teacher', 'editingteacher')
                GROUP BY r.shortname, crs.id, att.id";
        $courses_rows = $DB->get_records_sql($sql);

        // Sort the courses into two different arrays
        $teacher_courses = array();
        $sub_courses = array();
        foreach ($courses_rows as $current_row) {
            if ($current_row->shortname == 'editingteacher') {
                $teacher_courses[] = $current_row;
            } else {
                $sub_courses[] = $current_row;
            }
        }

        // Convert the arrays to objetcs for better handling
        $teacher_obj = (object) array('list' => $teacher_courses);
        $sub_obj = (object) array('list' => $sub_courses);

        return array($teacher_obj, $sub_obj);
    }

    /**
     * Go over the courses array (containing both substitue teacher and teacher courses)
     * And set a flag (as the attendance session id) if there is an attendance session today.
     *
     * @param  array  &$courses_array  Array containing two objects for teacher and substitute teacher 
     *                                 courses data passed by reference.
     *
     */
    public function bpm_mark_attendance_on_courses(&$courses_array) {
        global $DB;

        for ($i=0; $i < count($courses_array); $i++) { 
            foreach ($courses_array[$i]->list as &$current_course_obj) {
                if ($current_course_obj->att_id != NULL) {
                    $sess_sql = "SELECT cm.id as mod_id, ass.id as sess_id
                                 FROM mdl_attendance_sessions ass, mdl_course_modules cm
                                 WHERE cm.course = $current_course_obj->id
                                 AND   cm.module = 23
                                 AND   cm.instance = $current_course_obj->att_id
                                 AND FROM_UNIXTIME(ass.sessdate, '%Y-%m-%d') = CURDATE()
                                 AND  ass.attendanceid = $current_course_obj->att_id";
                    
                    if ($sess_record = $DB->get_record_sql($sess_sql)) {
                        $current_course_obj->mod_id = $sess_record->mod_id;
                        $current_course_obj->sess_id = $sess_record->sess_id;
                    }
                }
            }
        }  
    }

    /**
     * Build an html details block for a course list.
     *
     * @param  array   $courses_array  An array containing course objects.
     * @param  string  $title  The title for the course details block.
     * @param  bool  $open  Flag to indicate if details block should be open.
     *
     * @return string  $main_html  The course list details main_html block
     */
    public function bpm_get_courses_details_html($courses_array, $title, $open = false) {
        $main_html = '';
        $not_started_html = '';
        $ended_html = '';

        foreach ($courses_array as $current_course_obj) {
            $sess_html = '';

            // If there is an attendance session add a url to the attendance take page.
            if (isset($current_course_obj->sess_id)) {
                $session_edit_url = new moodle_url('/mod/attendance/sessions.php', 
                                          array('id' => $current_course_obj->mod_id,
                                                'sessionid' => $current_course_obj->sess_id,
                                                'action' => 2));

                // Build the attendance plugin icon.
                $sess_html .= '<a href=' . $session_edit_url . '>
                               <i title="לעריכת מפגש"
                                  class="fa fa-cog" 
                                  style="color:#999999;display:inline;padding-right:4px">
                               </i>
                               </a>';
                $attendance_url = new moodle_url('/mod/attendance/take.php', 
                                                 array('id' => $current_course_obj->mod_id,
                                                       'sessionid' => $current_course_obj->sess_id,
                                                       'grouptype' => 0));

                // Build the attendance plugin icon.
                $sess_html .= '<a href=' . $attendance_url . '>
                               <i title="להזנת נוכחות"
                                  class="fa fa-circle" 
                                  style="color:#99CC33;display:inline;padding-right:4px">
                               </i>
                               </a>';
            }

            // Build the current course url and row item.
            $course_url = new moodle_url('/course/view.php', array('id' => $current_course_obj->id));
            $current_course_html = '<div class="box coursebox p-y-1" style="margin-right:20px">
                                    <div class="form-group row fitem">
                                    <div class="col-md-9">
                                    <h2 class="title">
                                    <a href="' . $course_url . '">' . $current_course_obj->fullname . '</a>' . $sess_html . 
                                    '</h2></div></div></div>';

            // Filter courses that have ended or not yet began.
            if ($current_course_obj->startdate > time()) {
                $not_started_html .= $current_course_html;
            } else if ($current_course_obj->enddate < strtotime('-1 day', time())) {
                $ended_html .= $current_course_html;
            } else {
                $main_html .= $current_course_html;
            }       
        }

        // Build additional details blocks for courses that have ended or not yet began.
        $not_started_html = ($not_started_html) ? '<details class="bpm_details" style="margin:10px 20px 0 0">
                                                   <summary><h4>טרם התחילו</h4></summary>' . 
                                                   $not_started_html . 
                                                   '</details>' 
                                                : '';
        $ended_html = ($ended_html) ? '<details class="bpm_details" style="margin:10px 20px 0 0">
                                       <summary><h4>הסתיימו</h4></summary>' . 
                                       $ended_html . 
                                       '</details>' 
                                    : '';

        // Set the main details block for the course list
        $open = ($open) ? 'open' : '';
        $main_html = '<details class="bpm_details" style="margin-top:10px"' . $open . '>
                      <summary><h4>' . $title . '</h4></summary>' .
                      $main_html .
                      $not_started_html . 
                      $ended_html .
                      '</details>';

        return $main_html;
    }
}