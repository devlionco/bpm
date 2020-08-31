<?php
// This file is part of the BPM student course view block for Moodle - http://moodle.org/
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
 * Class definition for the bpm_student_course_view block
 *
 * @author  Ben Laor, BPM
 * @package blocks/bpm_student_course_view
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_bpm_student_course_view extends block_base {
    public function init() {
        $this->title = get_string('bpm_student_course_view', 'block_bpm_student_course_view');
    }

    public function get_content() {
        global $USER;

        // Init to avoid moodle warnings.
        $this->content = new stdClass;
        $this->content->text = '';

        $courses_array = $this->bpm_get_courses_arrays($USER->id);

        if (count($courses_array[0]->list) > 0) {
            $this->content->text .= $this->bpm_get_courses_details_html($courses_array[0]->list, 
                                                                        get_string('active', 'block_bpm_student_course_view'), true);
        }
        if (count($courses_array[1]->list) > 0) {
            $this->content->text .= $this->bpm_get_courses_details_html($courses_array[1]->list, 
                                                                        get_string('completed', 'block_bpm_student_course_view'));
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
     * Fetch all active and completed courses where the user is a student (not a teacher/substitute teacher).
     *
     * @param  int  $user_id  User moodle id.
     *
     * @global stdClass  $DB  Moodle DataBase API.
     *
     * @return array  Array containing two objects for active and completed courses data.
     *
     */
    public function bpm_get_courses_arrays($user_id) {
        global $DB;

        // Important to have a unique column as the first column, to iterate over the results
        $sql = "SELECT crs.id, r.shortname, crs.fullname, crs.startdate, crs.enddate
                FROM mdl_course crs
                JOIN mdl_context ctx         ON crs.id = ctx.instanceid
                JOIN mdl_role_assignments ra ON ctx.id = ra.contextid
                JOIN mdl_user usr            ON usr.id = ra.userid
                JOIN mdl_role r              ON r.id   = ra.roleid
                JOIN mdl_enrol e 			 ON crs.id = e.courseid
                JOIN mdl_user_enrolments ue	 ON e.id = ue.enrolid AND ue.userid = usr.id
                WHERE usr.id = $user_id
                AND (crs.startdate <= UNIX_TIMESTAMP() AND crs.startdate <> 0)
                AND r.shortname not in ('teacher', 'editingteacher')
                AND ue.status = 0
                ORDER BY crs.startdate ASC";
        $courses_rows = $DB->get_records_sql($sql);

        // Sort the courses into two different arrays
        $active_courses = array();
        $completed_courses = array();
        foreach ($courses_rows as $current_row) {

        	// Get the current course semester field
        	$course_parent_sf_id = $DB->get_record('course_details', array('courseid' => $current_row->id))->coursefather;
        	$current_row->semester = $DB->get_field('sf_course_parent_data', 'semester', array('sf_id' => $course_parent_sf_id));

            if ($current_row->enddate >= time()) {
                $active_courses[] = $current_row;
            } else {
                $completed_courses[] = $current_row;
            }
        }

        // Convert the arrays to objetcs for better handling
        $active_obj = (object) array('list' => $active_courses);
        $completed_obj = (object) array('list' => $completed_courses);

        return array($active_obj, $completed_obj);
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
        $semesters_html = '';
        $semesters_array = array('A' => '', 'B' => '', 'C' => '', 'D' => '');

        foreach ($courses_array as $current_course_obj) {

            // Build the current course url and row item.
            $course_url = new moodle_url('/course/view.php', array('id' => $current_course_obj->id));
            $current_course_html = '<div class="box coursebox p-y-1" style="margin-right:20px">
                                    <div class="form-group row fitem">
                                    <div class="col-md-9">
                                    <h2 class="title">
                                    <a href="' . $course_url . '">' . $current_course_obj->fullname . '</a>' . 
                                    '</h2></div></div></div>';

            // Filter courses that have ended or not yet began.
        	switch ($current_course_obj->semester) {
        		case 'A':
        			$semesters_array['A'] .= $current_course_html;
        			break;
        		case 'B':
        			$semesters_array['B'] .= $current_course_html;
        			break;
        		case 'C':
        			$semesters_array['C'] .= $current_course_html;
        			break;
        		case 'D':
        			$semesters_array['D'] .= $current_course_html;
        			break;
        		default:
        			$main_html .= $current_course_html;
        			break;
            }     
        }

        foreach ($semesters_array as $semester => $semester_courses) {
        	if ($semester_courses != '') {
        		$semesters_html .= '<details class="bpm_details" style="margin:10px 20px 0 0">
                 					<summary><h4>סמסטר ' . $this->bpm_get_hebrew_semester($semester) . '</h4></summary>' . 
                 					$semester_courses . 
                 					'</details>';
        	}
        }

        // Set the main details block for the course list
        $open = ($open) ? 'open' : '';
        $main_html = '<details class="bpm_details" style="margin-top:10px"' . $open . '>
                      <summary><h4>' . $title . '</h4></summary>' .
                      $main_html .
                      $semesters_html .
                      '</details>';

        return $main_html;
    }

    public function bpm_get_hebrew_semester($letter) {
    	switch ($letter) {
    		case 'A':
    			return 'א';
    			break;
    		case 'B':
    			return 'ב';
    			break;
    		case 'C':
    			return 'ג';
    			break;
    		case 'D':
    			return 'ד';
    			break;
    		default:
    			return '';
    			break;
    	}
    }
}