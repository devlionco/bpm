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
 * Class definition for the bpm_open_assignments_view block
 *
 * @author  Ben Laor, BPM
 * @package blocks/bpm_open_assignments_view
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/config.php");

class block_bpm_open_assignments_view extends block_base {
    public function init() {
        $this->title = get_string('bpm_open_assignments_view', 'block_bpm_open_assignments_view');
    }

    public function get_content() {
        global $USER;
        
        if ($this->content !== null) {
          return $this->content;
        }

        // Init to avoid moodle warnings.
        $this->content = new stdClass;
        $this->content->text = '';

        $courses_array = $this->bpm_get_assignments_arrays($USER->id);
        
        $encoded = json_encode($courses_array, JSON_UNESCAPED_UNICODE );
        $encoded = addcslashes($encoded, "'");
            $this->content->text .= "<script>console.log('" . $encoded . "');</script>";
            
        if (count($courses_array) > 0) {
            $this->content->text .= $this->bpm_get_courses_details_html($courses_array);
            
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
     * Fetch all assignments that have yet to be graded
     *
     * @param  int  $user_id  User moodle id.
     *
     * @global stdClass  $DB  Moodle DataBase API.
     *
     * @return array  Array containing arrays of assignments that have yet to be graded for each course
     *
     */
    public function bpm_get_assignments_arrays($user_id) {
        global $DB;

        $courses_rows = $this->get_teacher_courses($user_id);
        $assignments = [];
        // echo "<script>console.log('" . json_encode($courses_rows) . "')</script>";
        foreach ($courses_rows as $curr_course) {
            $assign_sql = "SELECT gg.id, gi.itemname, gi.iteminstance, u.id as userid, ass.id as submission
                           FROM (mdl_grade_items gi, 
                                mdl_grade_grades gg,
                                mdl_user u,
                                mdl_enrol e,
                                mdl_user_enrolments ue,
                                mdl_assign a)
                           LEFT JOIN mdl_assign_submission ass ON ass.assignment = a.id AND ass.userid = u.id AND ass.assignment = gi.iteminstance
                           WHERE gi.courseid = $curr_course->id
                           AND gi.gradetype != 3
                           AND u.id = gg.userid
                           AND gg.itemid = gi.id
                           AND ue.userid = u.id
                           AND ue.enrolid = e.id
                           AND e.courseid = gi.courseid
                           AND a.id = gi.iteminstance 
                           AND ((UNIX_TIMESTAMP() > a.duedate) OR (UNIX_TIMESTAMP() <= a.duedate AND ass.id IS NOT NULL))
                           AND a.duedate <> 0
                           AND ue.status = 0
                           AND gi.itemmodule = 'assign'
                           AND (gg.finalgrade is NULL OR gg.finalgrade = -1)
                           AND (gi.itemname rlike 'מבחן סיום|פרויקט סיום|מבחן אמצע|פרויקט אמצע' OR gi.itemname rlike '^פרויקט סיום')";
            $assign_rows = $DB->get_records_sql($assign_sql);

                // if ($curr_course->id == 608) {
                //   echo "<script>console.log('" . json_encode($assign_rows, JSON_UNESCAPED_UNICODE ) . "')</script>";
                // }
                
                
            foreach ($assign_rows as $curr_assign) {
                if ($this->is_student_in_course($curr_assign->userid, $curr_course->id)) {
                    $curr_assign->mod_id = $DB->get_field('course_modules', 
                                                      'id', 
                                                      array('course' => $curr_course->id,
                                                            'instance' => $curr_assign->iteminstance));

                    if (substr_count($curr_assign->itemname, 'פרויקט') != 0) {
                        if ($DB->record_exists('assign_submission', 
                                                array('assignment' => $curr_assign->iteminstance,
                                                      'userid' => $curr_assign->userid,
                                                      'status' => 'submitted'))) {

                            array_push($assignments, $curr_assign);
                        }
                    } else {
                        array_push($assignments, $curr_assign);
                    }
                }
            }
            $curr_course->assignments = $assignments;
            $assignments = [];
        }
        
        return $courses_rows;
    }

    /**
     * Fetch all courses where user is an editing teacher, by user_id
     *
     * @param  int  $user_id  User moodle id.
     *
     * @global stdClass  $DB  Moodle DataBase API.
     *
     * @return array  Array containing courses where the user is an 'editingteacher'
     *
     */
    public function get_teacher_courses($user_id) {
        global $DB;

        // Important to have a unique column as the first column, to iterate over the results
        $sql = "SELECT crs.id, r.shortname, crs.fullname, crs.startdate, crs.enddate
                FROM mdl_course crs
                JOIN mdl_context ctx         ON crs.id = ctx.instanceid
                JOIN mdl_role_assignments ra ON ctx.id = ra.contextid
                JOIN mdl_user usr            ON usr.id = ra.userid
                JOIN mdl_role r              ON r.id   = ra.roleid
                JOIN mdl_attendance att      ON crs.id = att.course
                WHERE usr.id = $user_id
                AND r.shortname = 'editingteacher'
                AND (crs.enddate > UNIX_TIMESTAMP('2018-02-01') OR crs.id = 608)
                GROUP BY r.shortname, crs.id";
        $courses_rows = $DB->get_records_sql($sql);

        return $courses_rows;
    }

    /**
     * Check if user is a student in a specific course.
     *
     * @param  int  $user_id    User moodle id.
     * @param  int  $course_id  Course moodle id.
     *
     * @global stdClass  $DB  Moodle DataBase API.
     *
     * @return bool  true if user is student in course, else false.
     *
     */
    public function is_student_in_course($user_id, $course_id) {
        global $DB;

        $sql = "SELECT count(*)
                FROM mdl_role r, 
                mdl_role_assignments ra, 
                mdl_context c
                WHERE r.id = ra.roleid
                AND c.id = ra.contextid
                AND r.shortname = 'student'
                AND ra.userid = $user_id
                AND c.instanceid = $course_id";

        if ($DB->get_field_sql($sql) > 0) {
            return true;
        } else {
            return false;
        }       
    }

    /**
     * Build an html block for the open courses_rows list.
     *
     * @param  array   $courses_rows  An array containing assignment objects for each course.
     *
     * @return string  $main_html  The open assignments list main_html block
     */
    public function bpm_get_courses_details_html($courses_rows) {
        global $DB;
        $main_html = '';
        $courses_html =  '';
        $total_assignments_count = 0;
        foreach ($courses_rows as $curr_course) {
            $assignments_count = count($curr_course->assignments);

            // If there are assignments for the course, build html
            if ($assignments_count > 0) {
                $total_assignments_count += $assignments_count;

                // Build an html line for each open assignment in the course
                foreach ($curr_course->assignments as $curr_assign) {
                    $user = $DB->get_record('user', array('id' => $curr_assign->userid));

                    // Build the grade assignment url for the current assignment row
                    $grade_assign_url = new moodle_url('/mod/assign/view.php', 
                                                       array('id' => $curr_assign->mod_id,
                                                             'rownum' => 0,
                                                             'action' => 'grader',
                                                             'userid' => $curr_assign->userid));

                    $courses_html .= '<h5 style="margin:10px 20px 0 0">
                                            <a href="' . $grade_assign_url . '">' .
                                                $curr_assign->itemname . ' - ' . 
                                                $user->firstname . ' ' . 
                                                $user->lastname . 
                                            '</a></h5>';
                }

                $main_html .= '<details style="margin:10px 20px 0 0;">
                               <summary><h4>' . $curr_course->fullname . '</h4> 
                               <div class="bpmNumberCircle">
                                   <span style="font-size: 11px;font-weight: bold;">' . 
                                       $assignments_count . 
                                   '</span>
                               </div></summary>' .
                               $courses_html .
                               '</details>';
                $courses_html = '';    
            }
        }

        if ($total_assignments_count > 0) {
            $main_html = '<details class="bpm_details" style="margin-top:10px;display:inline-block">
                <summary><h4>מטלות הדורשות הזנת ציון</h4>
                <div class="bpmNumberCircle">
                    <span style="font-size: 11px;font-weight: bold;">' . 
                        $total_assignments_count . 
                    '</span>
                </div></summary>' .
                $main_html .
                '</details>';
        }

        return $main_html;
    }
}