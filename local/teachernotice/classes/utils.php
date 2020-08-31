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
 * Declare and define the "utils" class and all its functions.
 * Used to provide certain utility and reused functions.
 *
 * @author  Ben Laor, BPM
 * @package local/teachernotice
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once dirname(__DIR__) . '/config.php';

const ZERO_TIMESTAMP = 0;

class utils{

    /**
     * Fetch assignment type via assignment_name
     *
     * @param  string  $assignment_name  Assignment name
     *
     * @global stdClass  $TN_CFG  Configuration class for teacher_notice plugin
     *
     * @return int  $assignment_type  Assignment type
     *
      */
    public static function bpm_get_assignment_type($assignment_name) {
        global $TN_CFG;

        switch (true) {
            case substr_count($assignment_name, 'מבחן אמצע') > 0:
                $assignment_type = $TN_CFG->message_types['mid_exam'];
                break;
            case substr_count($assignment_name, 'מבחן סיום') > 0:
                $assignment_type = $TN_CFG->message_types['final_exam'];
                break;
            case substr_count($assignment_name, 'פרויקט אמצע') > 0:
                $assignment_type = $TN_CFG->message_types['mid_project'];
                break;
            case substr_count($assignment_name, 'פרויקט סיום') > 0:
                $assignment_type = $TN_CFG->message_types['final_project'];
                break;
            default:
                break;
        }
        return $assignment_type;
    }

    /**
     * Fetch assignmnet prefix for search via assignment type
     *
     * @param  int  $assignment_type  Assignment type
     *
     * @global stdClass  $TN_CFG  Configuration class for teacher_notice plugin
     *
     * @return string  $assignment_prefix  Assignment prefix
     *
      */
    public static function bpm_get_assignment_prefix($assignment_type) {
        global $TN_CFG;

        switch ($assignment_type) {
            case $TN_CFG->message_types['mid_exam']:
                $assignment_prefix = 'מבחן אמצע%';
                break;
            case $TN_CFG->message_types['final_exam']:
                $assignment_prefix = 'מבחן סיום%';
                break;
            case $TN_CFG->message_types['mid_project']:
                $assignment_prefix = 'פרויקט אמצע%';
                break;
            case $TN_CFG->message_types['final_project']:
                $assignment_prefix = 'פרויקט סיום%';
                break;
            default:
                break;
        }
        return $assignment_prefix;
    }

    /**
     * Fetch assignment name from the database via assignment id
     *
     * @param  int  $assignment_id  Assignment id
     *
     * @global stdClass  $DB  Moodle database api
     *
     * @return string  $assignment_name  Assignment name
     *
      */
    public static function bpm_get_assignment_name($assignment_id) {
        global $DB;

        $sql = "SELECT name
                FROM   mdl_assign
                WHERE id = $assignment_id";

        $assignment_name = $DB->get_field_sql($sql);
                
        return $assignment_name;
    }

    /**
     * Fetch course name from the database via course id
     *
     * @param  int         $course_id   Course id
     *
     * @global stdClass $DB          Moodle database api
     *
     * @return string   $course_name Course name
     *
      */
    public static function bpm_get_course_name($course_id) {
        global $DB;

        $sql = "SELECT fullname
                FROM   mdl_course
                WHERE  id = $course_id";

        $course_name = $DB->get_field_sql($sql);

        return $course_name;
    }

    /**
     * Get teacher id of a specific course
     *
     * @param  int         $course_id  Course id
     *
     * @global stdClass $DB         Moodle database api
     *
     * @return int         mdl_user.id Teacher id
     *
      */
    public static function bpm_get_teacher_id($course_id) {

        global $DB;

        $sql = "SELECT usr.id 
                FROM mdl_course crs
                JOIN mdl_context ctx         ON crs.id = ctx.instanceid
                JOIN mdl_role_assignments ra ON ctx.id = ra.contextid
                JOIN mdl_user usr            ON usr.id = ra.userid
                JOIN mdl_role r              ON r.id   = ra.roleid
                WHERE crs.id    = $course_id
                AND r.shortname = 'editingteacher'";

        return $DB->get_field_sql($sql);
    }

    /**
     * Calculate the mid point of a course
     *
     * @param  int         $course_id     Course id
     *
     * @global stdClass $DB         Moodle database api
     *
     * @return int      $mid_date   Unix time stamp representing the middle of the course
     *
      */
    public static function bpm_get_course_mid_date($course_id) {

        global $DB;

        $mid_date = 0;
        $difference = 0;

        $dates_record = $DB->get_record_sql("SELECT startdate, enddate
                                             FROM   mdl_course
                                             WHERE id = $course_id");

        if ($dates_record->enddate != 0 && $dates_record->startdate != 0) {
            $difference = intval(($dates_record->enddate - $dates_record->startdate) / 2);
        }

        $mid_date = $dates_record->startdate + $difference;

        return $mid_date;
    }

    /**
     * Determine the assignments status for a course
     *
     * @param  int         $course_id     Course id
     *
     * @global stdClass $DB         Moodle database api
     *
     * @return int      $status     status (1-5) used to determine type of popup to produce
     *
      */
    public static function bpm_get_course_assignments_status($course_id, $course_mid_timestamp) {

        global $DB;

        $popup_input_scenarios = array(
            'exam' => '1',                        // Exam popup by mid course date
            'exam_and_project_by_exam' => '2', // Exam popup by mid course date and project popup by exam date
            'project' => '3',                   // Project popup by mid course date
            'project_by_exam' => '4',           // Project popup by exam date
            'none' => '5');                       // No popup

        $status = $popup_input_scenarios['none'];
        $today_timestamp = time();

        $assignments_sql = "SELECT a.name, a.duedate
                            FROM   mdl_assign a
                            JOIN  mdl_course_modules cm
                            ON    a.course = cm.course
                            AND   a.id        = cm.instance
                            JOIN  mdl_course c
                            ON       c.id = cm.course
                            JOIN  mdl_course_details cd
                            ON    cd.courseid = c.id
                            WHERE a.course = $course_id
                            AND   cm.deletioninprogress = 0
                            AND   (a.name = 'מבחן אמצע' OR 
                                   a.name LIKE 'פרויקט אמצע%')
                            AND   cd.coursefather <> -1
                            ORDER BY a.name";

        // Fetch all valid mid course assignments from the database and order by name so mid exam is always first
        $assignments = $DB->get_records_sql($assignments_sql);

        // Used to convert all keys to numeric values for easier manipulation
        $assignments_array = array_values($assignments);

        if (count($assignments_array) == 1) {
            if ($assignments_array[0]->duedate == ZERO_TIMESTAMP) {
                if ($today_timestamp >= $course_mid_timestamp) {
                    if ($assignments_array[0]->name == 'מבחן אמצע') {
                        $status = $popup_input_scenarios['exam'];
                    } else {
                        $status = $popup_input_scenarios['project'];
                    }
                }
            }
        } else if (count($assignments_array) >= 2) {
            if ($assignments_array[0]->duedate == ZERO_TIMESTAMP) {
                if ($today_timestamp >= $course_mid_timestamp) {
                    if ($assignments_array[1]->duedate == ZERO_TIMESTAMP) {
                        $status = $popup_input_scenarios['exam_and_project_by_exam'];
                    } else {
                        $status = $popup_input_scenarios['exam'];
                    }
                }
            } else {
                if ($assignments_array[1]->duedate == ZERO_TIMESTAMP && $today_timestamp >= $assignments_array[0]->duedate) {
                    $status = $popup_input_scenarios['project_by_exam'];
                }
            }
        }

        return $status;
    }

    /**
     * Update due date of an assignment
     *
     * @param  int  $assignment_id  The assignment id
     * @param  int  $duedate        New due date for the assignment
     *
     * @global stdClass $DB         Moodle database api
     *
      */
    public static function bpm_update_assignment_duedate($assignment_id, $duedate) {
        global $DB;

        $sql = "UPDATE mdl_assign
                SET    duedate = $duedate
                WHERE  id = $assignment_id";

        $dates_record = $DB->execute($sql);
    }
    
    /**
     * Update hasNotFilledInClassLog field in sf course object
     *
     * @param  int $course_id The moodle course id
     *
     * @global stdClass $TN_CFG Object Containing config data
     *
     */
    public static function bpm_update_notfilled_in_sf($course_id) {
        global $TN_CFG;

        $sf_access_data = self::bpm_get_sf_auth_data();

        $post_data = array(
            "hasNotFilledInClassLog__c" => true
        );

        $url = $sf_access_data['instance_url'] . $TN_CFG->BPM_SF_COURSE_URL . "Moodle_Course_Id__c/" . $course_id;
        $json_data = json_encode($post_data);
         
        $headers = array(
            "Authorization: OAuth " . $sf_access_data['access_token'],
            "Content-type: application/json"
        );

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);

        $response = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ( $status != 204 ) {
            die("Error: call to URL $url failed with status $status, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        }

        curl_close($curl);
    }

    /**
     * Retrieve Salesforce access token and instance (session) url
     *
     * @global stdClass $TN_CFG Object containing config data
     *
     * @return stdClass Object containing the sf connection data
     */
    public static function bpm_get_sf_auth_data() {
        global $TN_CFG;

        $post_data = array(
            'grant_type'    => 'password',
            'client_id'     => $TN_CFG->BPM_SF_CLIENT_ID,
            'client_secret' => $TN_CFG->BPM_SF_USER_SECRET,
            'username'      => $TN_CFG->BPM_SF_USER_NAME,
            'password'      => $TN_CFG->BPM_SF_USER_PASS
        );
        
        $headers = array(
            'Content-type' => 'application/x-www-form-urlencoded;charset=UTF-8'
        );

        $curl = curl_init($TN_CFG->BPM_SF_ACCESS_URL);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

        $response = curl_exec($curl);
        curl_close($curl);

        // Retrieve and parse response body
        $sf_response_data = json_decode($response, true);

        return $sf_response_data;
    }
}