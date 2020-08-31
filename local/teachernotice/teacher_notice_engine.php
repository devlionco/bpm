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
 * Declare and define the methods responsible for notices being sent to teachers 
 * and management of the "local_teacher_notice" table 
 * (to be called via an external file that is run by a cron job)
 *
 * @author  Ben Laor, BPM
 * @package local/teachernotice
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once($CFG->dirroot . '/lib/classes/user.php');
require_once('config.php');
require_once('lib.php');
require_once('add_record.php');

/**
 * Send notices to teachers and clean the local_teacher_notice table of expired notices.
 *
 * @global stdClass $DB Moodle DataBase API.
 *
 */
function bpm_process_teacher_notices() {
    global $DB;

    // Sync teacher notices where the assignments dates changed
    bpm_sync_notice_data_with_moodle();

    // Send last notices to the student services department and delete them from the database
    $delete_count = bpm_send_and_delete_last_notices();

    $notices_sql = "SELECT * 
                    FROM   mdl_local_teacher_notice
                    WHERE  DATE(from_unixtime(lastnoticedate)) = CURDATE()
                    AND lastnoticedate <> originaldate";

    // Fetch the teacher notice rows from the Moodle db
    $notice_rows = $DB->get_records_sql($notices_sql);

    // Go over each notice queried and send the appropriate message to teacher,
    // as well as a message to the student services department 
    foreach ($notice_rows as $row) {
        
        if (!$notice = bpm_get_notice_object($row)) {
            return false;
        }

        $notice_result_success = bpm_send_teacher_notice($notice);

        if (!$notice_result_success) {
            bpm_log_error($notice);
        } else {
            $ssd_result_success = bpm_send_ssd_notice($notice);

            if (!$ssd_result_success) {
                bpm_log_error($notice);            
            }

            $row->count++;
            bpm_update_notice_date_and_count($row);
        }
		if ($row->count == 3) {
			$limudim_result_success = (bpm_send_ssd_notice($notice, false, "limudim@bpm-music.com"));
		}
    }
}

/**
 * Fetch all notices that had their duedate changed, and update the notice table rows accordingly
 *
 * @global stdClass $DB Moodle DataBase API.
 *
 */
function bpm_sync_notice_data_with_moodle() {
    global $DB;

    // Add notices for assignments that already contain a duedate
    bpm_add_notices_predefined_assignments();

    $sql = "SELECT tn.id, tn.lastnoticedate, tn.courseid, tn.assignmentid, tn.originaldate, a.duedate
            FROM   mdl_local_teacher_notice tn
            JOIN   mdl_assign a
            ON     a.id = tn.assignmentid
            WHERE  tn.originaldate <> a.duedate";

    // Fetch the teacher notice rows from the Moodle db
    $notice_rows = $DB->get_records_sql($sql);

    // If rows exist sync each ones dates accordingly  
    if (count($notice_rows) > 0) {
        foreach ($notice_rows as $row) {
            $new_original_date = $row->duedate;
            if ($row->originaldate > $row->duedate) {
                $date_interval = $row->originaldate - $row->duedate;
                $new_last_notice_date = $row->lastnoticedate - $date_interval;
            } else {
                $date_interval = $row->duedate - $row->originaldate;
                $new_last_notice_date = $row->lastnoticedate + $date_interval;
            }

            $update_sql = "UPDATE mdl_local_teacher_notice
                           SET    lastnoticedate = $new_last_notice_date,
                                  originaldate   = $new_original_date
                           WHERE  id = $row->id";

            $update_success = $DB->execute($update_sql);

            // Log results
            if ($update_success) {
                echo 'Success in update date course: ' . $row->courseid .
                     ' on assignment: ' . $row->assignmentid . 
                     ' from date:' . $row->originaldate . 
                     ' to date: ' . $new_original_date . PHP_EOL;
            } else {
                echo 'Failed to update date of course: '  . $row->courseid .
                     ' on assignment: ' . $row->assignmentid . 
                     ' from date:' . $row->originaldate . 
                     ' to date: ' . $new_original_date . PHP_EOL;
            }
        }
    }
}

/**
 * Create notices for all assignments that have a duedate
 *
 * @global stdClass $DB Moodle DataBase API.
 *
 */
function bpm_add_notices_predefined_assignments() {
    global $DB;

    $expired_control_date = strtotime("-2 month");

    $sql_mid_projects = "SELECT a.id, a.course, a.duedate, a.name
                         FROM   mdl_assign a, mdl_course c
                         WHERE  a.duedate <> 0
                         AND    a.course = c.id
                         AND    c.enddate >= $expired_control_date
                         AND c.id NOT IN (SELECT id FROM mdl_course WHERE shortname LIKE '%קיובייס%')
                         AND    a.nosubmissions = 0
                         AND    a.grade <> 0
                         AND    a.id NOT IN (SELECT gi.iteminstance
                                             FROM   mdl_grade_items gi
                                             JOIN   mdl_grade_grades gg 
                                             ON     gi.id = gg.itemid
                                             WHERE (gg.finalgrade IS NOT NULL
                                                 OR gi.gradetype = 3)
                                             AND   gi.itemname rlike 'פרויקט אמצע')";

    $mid_projects_rows = $DB->get_records_sql($sql_mid_projects);

    $sql_assignments = "SELECT a.id, a.course, a.duedate, a.name
                        FROM   mdl_assign a, mdl_course c
                        WHERE  a.duedate <> 0
                        AND    a.course = c.id
                        AND    c.enddate >= $expired_control_date
                        AND    a.name rlike 'מבחן סיום|פרויקט סיום|מבחן אמצע'
                        AND    a.grade <> 0
                        AND    a.id NOT IN (SELECT gi.iteminstance
                                            FROM   mdl_grade_items gi
                                            JOIN   mdl_grade_grades gg 
                                            ON     gi.id = gg.itemid
                                            WHERE gg.finalgrade IS NOT NULL
                                            AND   gi.itemname rlike 'מבחן סיום|פרויקט סיום|מבחן אמצע')";

    $assignments_rows = $DB->get_records_sql($sql_assignments);

    $assignments_rows = (count($mid_projects_rows) > 0) ? array_merge($assignments_rows, $mid_projects_rows) : $assignments_rows;

    foreach ($assignments_rows as $row) {
        $assignment_type = utils::bpm_get_assignment_type($row->name);
        bpm_insert_teacher_notice($row->course, $row->duedate, $assignment_type);
    }
}

/**
 * Send last notices to the student services department and delete them from the database.
 *
 * @global stdClass $DB Moodle DataBase API.
 *
 */
function bpm_send_and_delete_last_notices() {
    global $DB;
    
    // Select all notices that need to be deleted
    $sql = "SELECT *
            FROM   mdl_local_teacher_notice
            WHERE  count = 3";

    $notice_rows = $DB->get_records_sql($sql);  
    $rows_deleted = 0;
    $delete_string ="";

    // if rows are found send last notice and delete
    if (count($notice_rows) > 0) {
        $delete_string = "";

        // Sent the student service department last notices 
        foreach ($notice_rows as $row) {
            $notice = bpm_get_notice_object($row);

            bpm_send_ssd_notice($notice, true);
            $delete_string .= "$row->id,";
            $rows_deleted++;
        }

        $delete_string = rtrim($delete_string, ',');
    }
    
    // Delete all expired notices
    $rows_deleted += bpm_delete_notices($delete_string, $rows_deleted);

    echo 'Deleted notices for assignments with ids: ' . $delete_string . PHP_EOL;
    echo 'Total number of deleted rows: ' . $rows_deleted . PHP_EOL;
    
    return $rows_deleted;
}

/**
 * Delete teacher notices that are no longer viable (grades exist/too long has passed).
 *
 * @global stdClass $DB Moodle DataBase API.
 *
 */
function bpm_delete_notices($delete_string, $rows_deleted) {
    global $DB;

    $assignment_count_sql = "SELECT COUNT(*)
                             FROM   mdl_local_teacher_notice tn 
                             WHERE tn.assignmentid IN (SELECT gi.iteminstance
                                                       FROM   mdl_grade_items gi
                                                       JOIN   mdl_grade_grades gg ON gi.id = gg.itemid
                                                       WHERE gg.finalgrade IS NOT NULL
                                                       AND   gi.itemname rlike 'מבחן סיום|פרויקט סיום|מבחן אמצע|פרויקט אמצע')";

    $rows_deleted += $DB->get_field_sql($assignment_count_sql);

    if ($rows_deleted > 0) {
        if ($delete_string != '') {
            $delete_string_sql = "tn.id in ($delete_string) OR";
        } else {
            $delete_string_sql = '';
        }

        $delete_sql = "DELETE tn
                       FROM mdl_local_teacher_notice tn 
                       WHERE " . 
                       $delete_string_sql . 
                       " tn.assignmentid IN (SELECT gi.iteminstance
                                             FROM   mdl_grade_items gi
                                             JOIN mdl_grade_grades gg ON gi.id = gg.itemid
                                             WHERE gg.finalgrade IS NOT NULL
                                             AND   gi.itemname rlike 'מבחן סיום|פרויקט סיום|מבחן אמצע|פרויקט אמצע')";

        $remove_result = $DB->execute($delete_sql);

        if (!$remove_result) {
            echo 'Remove failed with the query: ' . $sql . PHP_EOL;
        }
    }

    return $rows_deleted;
}

/**
 * Create a notice object from a local_teacher_notice row.
 *
 * @return stdClass The notice object.
 *
 */
function bpm_get_notice_object($row) {
    $teacher_object = core_user::get_user($row->teacherid);

    $course_name = utils::bpm_get_course_name($row->courseid);

    // Create the notice object
    $notice_object = new \local_teachernotice\notice($row->id,
                                                     $row->courseid, 
                                                     $row->assignmentid,
                                                     $row->type,
                                                     $teacher_object,
                                                     $course_name,
                                                     $row->count);

    return $notice_object;
}

/**
 * Send a notice to a teacher for a specific assignment.
 *
 * @param  stdClass $notice Notice data to identify recipient and content.
 *
 * @global stdClass $TN_CFG Contaning ids and other values fixed values.
 *
 * @return bool             False if notice failed to send, True on success.
 *
 */
function bpm_send_teacher_notice($notice) {
    global $TN_CFG;

    if (!$assignment_name = utils::bpm_get_assignment_name($notice->assignment_id)) {
        return false;
    }
    if ($assignment_name == '') {return false;}

    $assignment_url = bpm_get_assignment_url($notice->assignment_id, $notice->course_id);
    $notice_count_sub_message = '';

	if ($notice->count > 1) {
       $count = $notice->count + 1;
       $notice_count_sub_message = '<b>לתשומת לבך:</b> זוהי הודעה מספר ' . $count . ' בהקשר זה.<br>';
	} else if ($notice->count == 0) { //first time - warning text
       $count = $notice->count + 1;
       $notice_count_sub_message = '<b>לתשומת לבך:</b> זוהי הודעה ראשונה בהקשר זה. בעוד שבוע תתבצע בדיקה נוספת להזנת הציונים, ואם לא יוזנו כצפוי, יירדו לך נקודות מהציון במשוב באופן אוטומטי.<br>';
	} else { //second time, sf penalty
        utils::bpm_update_notfilled_in_sf($notice->course_id);
        $count = $notice->count + 1;
        $notice_count_sub_message = '<b>לתשומת לבך:</b> זוהי הודעה מספר ' . $count . ' בהקשר זה וכעת ירדו לך נקודות באופן אוטומטי מציון המשוב הסופי של הקורס.<br>';
    }

    // Combine teacher first and last name
    $teacher_name = $notice->teacher_data->firstname . ' ' . $notice->teacher_data->lastname;

    // Build the message object
    $message = new \core\message\message();
    $message->name            = 'notice_message';
    $message->component       = 'local_teachernotice';
    $message->userfrom        = $TN_CFG->bpm_bot_id;
    $message->userto          = $notice->teacher_data->id;
    $message->subject         = 'הודעה על איחור בהזנת ציונים';
    $message->fullmessagehtml = "<p dir=\"rtl\">שלום " . $teacher_name . ",<br>" .
                                "הינך מאחר/ת בהגשת ציונים ל<b>" . $assignment_name . "</b> בקורס <b>" . $notice->course_name . "</b>.<br>" .
                                $notice_count_sub_message .
                                "נא להזין ציונים בהקדם.<br><br>" .
                                "לינק למטלה: " . $assignment_url . "</b><br><br>" . 
                                "אם הינך סבור/ה שחלה טעות, והציונים אכן הוזנו, יש ליצור קשר עם מדור לימודים.<br></p>";
    $message->smallmessage    = 'יש להזין ציונים ל ' . $assignment_name . ' בקורס ' . $notice->course_name . '.';

    $result = message_send($message);
	
    return $result;
}

/**
 * Send a notice to the student services department about a specific teacher notice.
 *
 * @param  stdClass $notice      Notice data to identify recipient and content.
 *
 * @param  bool     $last_notice Flag to determine if the notice is the last one for the assignment.
 *
 * @global stdClass $TN_CFG      Contaning ids and other values fixed values.
 *
 * @return bool                  False if notice failed to send, True on success.
 *
 */
function bpm_send_ssd_notice($notice, $last_notice = false, $limudim = false) {
    global $TN_CFG;

    $assignment_name = utils::bpm_get_assignment_name($notice->assignment_id);

    $assignment_url = bpm_get_assignment_url($notice->assignment_id, $notice->course_id);
    $urgent_topic = '';
    $notice_count_sub_message = '';

    if ($last_notice) {
        $urgent_topic = ' - הודעה אחרונה!';
    } else {
        $count = $notice->count + 1;
        $notice_count_sub_message = 'זוהי הודעה מספר ' . $count . ' בהקשר זה.<br>';
    }

    // Combine teacher first and last name
    $teacher_name = $notice->teacher_data->firstname . " " . $notice->teacher_data->lastname;

    // Build the message object
    $message = new \core\message\message();
    $message->name            = 'ssd_notice_message';
    $message->component       = 'local_teachernotice';
    $message->userfrom        = $TN_CFG->bpm_bot_id;
    $message->userto          = $TN_CFG->bpm_ssd_id;
    $message->subject         = ' איחור בהזנת ציונים ' . $urgent_topic;
    $message->fullmessagehtml = "<p dir=\"rtl\">לתשומת לבך: המרצה <b>" . $teacher_name . 
                                "</b> מאחר בהגשת ציונים ל<b>" . $assignment_name . "</b> בקורס <b>" . $notice->course_name . "</b>.<br>" .
                                $notice_count_sub_message .
                                "לינק למטלה: " . $assignment_url . "<br></p>";
    $message->smallmessage    = 'איחור בהזנת ציונים ל ' . $assignment_name . ' בקורס ' . $notice->course_name . '.';
	if ($limudim) {
		$message->userto = $TN_CFG->BPM_LIMUDIM_MAN_ID;
	}
    $result = message_send($message);
	
    
    return $result;
}

/**
 * Fetch an assignment url using a course id and assignment id
 *
 * @param  int    $assignment_id Assignment id as appears in the Moodle DB.
 * @param  int    $course_id     Course id as appears in the Moodle DB.
 *
 * @return string Assignment url in the Moodle system.
 *
 */
function bpm_get_assignment_url($assignment_id, $course_id) {
    global $DB;

    $sql = "SELECT id
            FROM   mdl_course_modules
            WHERE  instance = $assignment_id
            AND    course = $course_id";

    $context_id = $DB->get_field_sql($sql);
    $assignment_url = 'https://my.bpm-music.com/mod/assign/view.php?id=' . $context_id;

    return $assignment_url;
}

/**
 * Update date for the last notice sent, and the notice count.
 *
 * @param  stdClass $notice The notice object.
 *
 * @global stdClass $DB     Moodle DataBase API.
 *
 */
function bpm_update_notice_date_and_count($notice) {
    global $DB;

    $new_last_notice_date = strtotime('+1 week', $notice->lastnoticedate);

    $sql = "UPDATE mdl_local_teacher_notice
            SET count = $notice->count,
                lastnoticedate = $new_last_notice_date
            WHERE  id = $notice->id";

    $DB->execute($sql);
}

/**
 * Write an error message to the log.
 *
 * @param stdClass $notice The notice object.
 *
 */
function bpm_log_error($notice) {
    date_default_timezone_set('Asia/Jerusalem');
    echo 'Notice for '         . $notice->teacher_data->id . 
         ' on assignment '     . $notice->assignmentid          . 
         ' in course '         . $notice->courseid              . 
         ' failed to send at ' . date("d/m/Y h:i:s") . PHP_EOL;
}