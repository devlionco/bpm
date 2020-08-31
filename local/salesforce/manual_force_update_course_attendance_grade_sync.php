<?php
//hey 29.7.20
require_once __DIR__ . '/../../config.php';
require_once 'config.php';
require_once 'sfsql.class.php';
require_once 'bpm_sf_lib.php';

if (!isset($_GET['cid'])) {
    echo 'no courseid';
    die();
} else {
    $sfsql = new sfsql($wsdl, $userName, $password, $token);
    bpm_update_grades_and_attendance_in_sf($_GET['cid']);
}

function bpm_update_grades_and_attendance_in_sf($courseid) {
    bpm_update_complete_grade_statuses($courseid);

    $course_sObjects = bpm_get_attendance_for_course($courseid);

    $registration_sObjects = bpm_get_registration_objects_for_sf($courseid);

    bpm_update_array_to_sf($course_sObjects, true);
    bpm_update_array_to_sf($registration_sObjects);
}

function bpm_get_attendance_for_course($courseid) {
    global $DB;

    $sql = "SELECT gi.courseid, AVG(gg.finalgrade) as avg_grade
            FROM mdl_grade_items gi
            JOIN mdl_grade_grades gg
            ON   gg.itemid = gi.id
            JOIN mdl_course c
            ON   c.id = gi.courseid
            WHERE gi.itemmodule = 'attendance'
            AND   c.id = $courseid
            GROUP BY gi.courseid
            HAVING avg_grade IS NOT NULL
            ORDER BY courseid ASC";
        
    $attendance_rows = $DB->get_records_sql($sql);

    //indexing by course id - gonna incorporate it in the following foreach based on $attendance_rows
    $attendance_sessions_sql = "SELECT c.id, count(asess.id) as sessions
                                FROM mdl_attendance_sessions asess, mdl_attendance a, mdl_course c 
                                WHERE asess.attendanceid = a.id
                                AND a.course = c.id
                                AND c.id = $courseid
                                AND asess.sessdate < UNIX_TIMESTAMP(CURDATE())
                                GROUP BY c.id
                                ORDER BY c.id ASC";
    $session_counts = $DB->get_records_sql($attendance_sessions_sql);
    
    $course_sObjects = array();

    foreach ($attendance_rows as $row) {
        $course_sObject = new sObject();
        $course_sObject->type = 'Course__c';
        $course_sObject->fields = array(
            'Moodle_Course_Id__c' => $row->courseid,
            'attendance__c' => $row->avg_grade,
            'passed_sessions_amt__c' => $session_counts[$row->courseid]->sessions
        );

        array_push($course_sObjects, $course_sObject);
    }

    return $course_sObjects;
}

function bpm_get_registration_objects_for_sf($courseid) {
    $merged_objects = array();
    $attendance_objects = bpm_get_attendance_updatable_enrollments($courseid);
    $grade_objects = bpm_get_grade_updatable_enrollments($courseid);

    foreach ($attendance_objects as $attendance_key => $attendance_data) {
        foreach ($grade_objects as $grade_key => $grade_data) {
            if ($grade_data->fields['Id'] == $attendance_data->fields['Id']) {
                $merged_object = new sObject();
                $merged_object->type = 'Registration__c';
                $merged_object->fields = array_merge($attendance_data->fields, $grade_data->fields);
                unset($attendance_objects[$attendance_key]);
                unset($grade_objects[$grade_key]);
                array_push($merged_objects, $merged_object);
            }
        }
    }
    
    $merged_objects = array_merge($merged_objects, $attendance_objects);
    $merged_objects = array_merge($merged_objects,$grade_objects);
//   echo 'merged objects: ';
//   var_dump($merged_objects);
    return $merged_objects;
}

function bpm_get_attendance_updatable_enrollments($courseid) {
    global $DB;

    $attendance_sql = "SELECT DISTINCT se.id, gg.finalgrade AS attendance_score, se.sfid
                       FROM mdl_grade_items gi, mdl_grade_grades gg, mdl_sf_enrollments se, mdl_course c, mdl_attendance att, mdl_attendance_sessions asess
                       WHERE gi.itemmodule = 'attendance'
                       AND c.id = se.courseid
                       AND att.course = se.courseid
                       AND asess.attendanceid = att.id
                       AND c.id = $courseid
                       AND gi.id = gg.itemid 
                       AND gi.courseid = se.courseid 
                       AND gg.userid = se.userid";
    $attendance_records = $DB->get_records_sql($attendance_sql);
    // echo '$attendance_records_count: ' . count($attendance_records) . PHP_EOL;
    $attendance_count_logs_sql = "SELECT se.id, se.sfid, se.userid, se.courseid, count(al.id) AS logscount
                                    FROM mdl_sf_enrollments se, mdl_course c, mdl_attendance att, mdl_attendance_sessions asess, mdl_attendance_log al
                                    WHERE c.id = se.courseid
                                    AND courseid = $courseid
                                    AND att.course = se.courseid
                                    AND asess.attendanceid = att.id
                                    AND al.studentid = se.userid
                                    AND al.sessionid = asess.id
                                    GROUP BY se.id";
    $attendance_logs_count = $DB->get_records_sql($attendance_count_logs_sql);
    
    //  echo '$attendance_logs_count: ' . count($attendance_logs_count) . PHP_EOL;
    // var_dump($attendance_logs_count);
    
    $enrollments_attendance = array();
    
    foreach ($attendance_records as $record) {
        // echo '$attendance_records:' . PHP_EOL;
        // var_dump($record);
        //  echo '$attendance_logs_count[$record->id]:' . PHP_EOL;
        //  var_dump($attendance_logs_count[$record->id]);
        // echo PHP_EOL;
        
        $attendance_object = new sObject();
        $attendance_object->type = 'Registration__c';
        $attendance_object->fields = array(
            'Id'            => $record->sfid,
            'attendance__c' => $record->attendance_score
            //'attendance_logs_count__c' => $attendance_logs_count[$record->id]->logscount
        );
        if ($attendance_logs_count[$record->id]) {
            $attendance_object->fields['attendance_logs_count__c'] = $attendance_logs_count[$record->id]->logscount;
        }
        
        if (!$record->attendance_score) {
            $record->attendance_score = 'NULL';
        } else {
            array_push($enrollments_attendance, $attendance_object);
        }
        $update_sql = "UPDATE {sf_enrollments}
                       SET    attendance = $record->attendance_score
                       WHERE  sfid = '$record->sfid'";
        //  echo $update_sql . PHP_EOL;
        $DB->execute($update_sql);

    }

    return $enrollments_attendance;
}

function bpm_get_grade_updatable_enrollments($courseid) {
    global $DB;
    $grades_control_date = strtotime('-2 day');
    
    $grades_sql = "SELECT se.id, gg.finalgrade AS grade, se.sfid
                   FROM mdl_grade_grades gg, mdl_grade_items gi, mdl_sf_enrollments se
                   WHERE gi.id = gg.itemid
                   AND   se.courseid = gi.courseid
                   AND   se.userid = gg.userid
                   AND   gi.itemtype='course'
                   AND   gg.finalgrade IS NOT NULL
                   AND   se.courseid = $courseid";
    // echo $grades_sql;
    $grade_records = $DB->get_recordset_sql($grades_sql);
    // var_dump($grade_records);
    $enrollments_grades = array();

    foreach ($grade_records as $record) {
        $grade_object = new sObject();
        $grade_object->type = 'Registration__c';
        $grade_object->fields = array(
            'Id'       => $record->sfid,
            'Grade__c' => $record->grade,
        );

        $update_sql = "UPDATE {sf_enrollments}
                       SET    grade = $record->grade
                       WHERE  sfid = '$record->sfid'";
        // echo $update_sql;
        $DB->execute($update_sql);

        array_push($enrollments_grades, $grade_object);
    }

    return $enrollments_grades;
}

// Updates an array of sObjects to sf (splits if array larger than 200 records)
function bpm_update_array_to_sf($array, $is_course = false){
    global $sfsql;

echo "<pre>";

    if (count($array) > 200) {
        $split_array = array_chunk($array, 150);
        foreach ($split_array as $small_array) {
            if ($is_course) {
                echo 'upserting stats to course ';
                $sfsql->upsert("Moodle_Course_Id__c", $small_array);
            } else {
                $sfsql->update($small_array);
            }
        }
    } else {
        if ($is_course) {
            $sfsql->upsert("Moodle_Course_Id__c", $array);
        } else {
            echo 'upserting reg ' . PHP_EOL;
            var_dump($array);
            $sfsql->update($array);
        }
    }
echo "</pre>";
}


function bpm_update_complete_grade_statuses($courseid) {
    global $DB;

    $sql = "SELECT se.id 
            FROM mdl_sf_enrollments se
            WHERE se.courseid = $courseid";
    
    $grade_records = $DB->get_records_sql($sql);
    
     echo 'grade_records: <br>';
     var_dump($grade_records);

    foreach ($grade_records as $current_record) {
        $update_sql = "UPDATE mdl_sf_enrollments se
                       SET se.completegrade = 1
                       WHERE se.id = $current_record->id";   

        $DB->execute($update_sql);               
    }
}
