<?php
// This file is part of the Absence notice plugin for Moodle - http://moodle.org/
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
 * @package local/absencenotice
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once dirname(__DIR__) . '/config.php';

class utils{

    /**
     * Fetch number of consecutive times a student has been absent in a course
     * Or the date of the first session for the course
     *
     * @param  int      $user_id                The user id
     * @param  int      $course_id              The course id
     *
     * @global stdClass $DB                     Moodle database api
     *
     * @return int/int  $absence_count/sessdate Absence counter / First session date
     *
     */
    public static function bpm_count_consecutive_absence($user_id, $course_id) {
        global $DB;

        $absence_count = 0;
        $attendance_index = 0;

        $attendance_sql = "SELECT al.id, ass.id as sessid, ast.acronym
                           FROM mdl_attendance_log al
                           JOIN mdl_attendance_sessions ass
                           ON   ass.id = al.sessionid
                           JOIN mdl_attendance a
                           ON   ass.attendanceid = a.id
                           JOIN mdl_attendance_statuses ast
                           ON   ast.id = al.statusid
                           WHERE al.studentid = $user_id
                           AND   a.course = $course_id
                           ORDER BY ass.sessdate DESC
                           LIMIT 6";

        $attendance_records = $DB->get_records_sql($attendance_sql);
        $attendance_records = array_values($attendance_records);

        // Count number of consecutive absence accurances
        while ($attendance_records[$attendance_index] && $attendance_records[$attendance_index]->acronym == 'נע' && $absence_count < 6) {
            $absence_count++;
            $attendance_index++;
        }

        // If only one row counted it is the first attendance log taken for the course and we return the attendance date instead
        if ($absence_count == 1) {
            $first_session = \utils::bpm_get_first_attendance_session($course_id);
            if ($first_session->id == $attendance_records[0]->sessid) {
                return $first_session->sessdate;
            }
        }

        return $absence_count;
    }

    /**
     * Fetch the first attendance session of a course from the database
     *
     * @param  int      $course_id  The course id
     *
     * @global stdClass $DB         Moodle database api
     *
     * @return int      $session_id The attendance session record
     *
     */
    public static function bpm_get_first_attendance_session($course_id) {
        global $DB;

        $first_session_sql = "SELECT ass.id, ass.sessdate
                              FROM mdl_attendance_sessions ass
                              JOIN mdl_attendance a
                              ON   a.id = ass.attendanceid
                              WHERE a.course = $course_id
                              ORDER BY sessdate asc
                              LIMIT 1";

        $session_record = $DB->get_record_sql($first_session_sql);

        return $session_record;
    }

    /**
     * Fetch course name from the database via course id
     *
     * @param  int      $course_id   Course id
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
     * retrieve list of user ids for a specific attendance session
     *
     * @param  int  $session_id  The attendance session id
     *
     * @global stdClass  $DB  Moodle database api
     *
     * @return array  Array contaning attendance_log records
     *
      */
    public static function bpm_get_session_user_ids($session_id) {
        global $DB;

        $sql = "SELECT al.id, al.studentid
                FROM mdl_attendance_sessions ass
                JOIN  mdl_attendance_log al
                ON    ass.id = al.sessionid
                WHERE ass.id = $session_id";

        return $DB->get_records_sql($sql);
    }

    /**
     * Check if an absent case was opened in salesforce for a user in a course
     *
     * @param  int      $course_id   Course id
     * @param  int      $user_id     User id
     *
     * @global stdClass $DB          Moodle database api
     *
     * @return bool     true/false   Is case opened
     *
     */
    public static function bpm_is_case_opened($user_id, $course_id) {
        global $DB;

        $sql = "SELECT absent_case_open
                FROM   mdl_sf_enrollments
                WHERE  userid   = $user_id
                AND    courseid = $course_id";

        $is_case_open = $DB->get_field_sql($sql, null, IGNORE_MULTIPLE);

        return $is_case_open;
    }

    /**
     * TODO describe, include user_enrolments created-date
     *
     * @param  int  $userid  student's userid
     * @param  int  $courseid  course from which he was absent
     *
     * @global stdClass  $DB  Moodle database api
     *
     * @return array  Array contaning attendance_log records
     *
      */
    public static function get_total_points_for_user_in_course_till_now($userid, $courseid) {
        global $DB;

        $sql = "SELECT SUM(astats.grade)
				FROM mdl_attendance_statuses astats, mdl_user_enrolments ue, mdl_enrol e, mdl_attendance_sessions asess, mdl_attendance att
				WHERE ue.userid = $userid
				AND e.courseid = $courseid
				AND asess.attendanceid = att.id
				AND att.course = e.courseid
				AND astats.attendanceid = att.id
				AND astats.acronym = 'נ'
				AND ue.enrolid = e.id
				AND asess.sessdate >= ue.timestart
				AND asess.sessdate < UNIX_TIMESTAMP()";

        return $DB->get_field_sql($sql);
    }
	
	/**
     * TODO describe, include user_enrolments created-date
     *
     * @param  int  $userid  student's userid
     * @param  int  $courseid  course from which he was absent
     *
     * @global stdClass  $DB  Moodle database api
     *
     * @return array  Array contaning attendance_log records
     *
      */
	public static function get_current_student_points_sum($userid, $courseid) {
		global $DB;

		$sql = "SELECT SUM(astats.grade)
				FROM mdl_attendance_log al, mdl_attendance_statuses astats, mdl_user_enrolments ue, mdl_enrol e, mdl_attendance_sessions asess
				WHERE al.studentid = $userid
				AND al.statusid = astats.id
				AND ue.enrolid = e.id
				AND e.courseid = $courseid
				AND ue.userid = al.studentid
				AND al.sessionid = asess.id
				AND al.sessionid IN (
					SELECT id 
					FROM mdl_attendance_sessions 
					WHERE attendanceid = (
						SELECT id 
						FROM mdl_attendance 
						WHERE course = $courseid
					)
				)
				AND asess.sessdate >= ue.timestart";
	
		return $DB->get_field_sql($sql);
	}

    /**
    * Write an error to the local error file
    *
    * @param string $error_message The error message text
    */
    public static function bpm_log_error($error_message) {
        $file = fopen(__DIR__ . '/debug.txt', 'a');
        fwrite('<br>' . date("d/m/Y") . '<br>\n');
        fwrite($file, $error_message);
        fclose($file);
    }
}