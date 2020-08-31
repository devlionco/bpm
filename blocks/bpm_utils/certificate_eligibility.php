<?php
require_once('config.php');
require_once('../../config.php');

function bpm_cert_check_eligibility($user_id, $course_name, $course_end_date, $course_father_id, $course_id, $is_standalone=false) { //$is_standalone means from program
    global $BU_CFG, $COURSE;
    
    if (user_in_debt($user_id)) {
        echo '<script>console.log("BPM 007");</script>';
        return false;
    }
    $eligible = false;
    $es_father_id = 141; //const
    if ($course_father_id == $es_father_id) {echo "<script>console.log('BPM 009 - ES');</script>"; return false;}
    
    
    $music_type = bpm_strpos_array($BU_CFG->MUSIC_COURSES_NAMES, $course_name);
    $passing_grade = $music_type ? 60 : 80;
    
    if ($is_standalone) {
        if (bpm_passed_course($user_id, $course_id, $passing_grade) != true) { 
            return false;
        } else {
            return true;
        }
        
    }
    
    if ($course_end_date) {
        $eligible = true;
        $program_name;
        $fails_allowed = 1;

        $course_type = bpm_get_course_type($course_name, $program_name);
        if (bpm_passed_electric_safety($user_id, $course_name, $program_name) != true &&
            strpos($course_name, "חיפה") === false) {//haifa courses do not require ES
            
            echo '<script>console.log("BPM 003");</script>';
            if (substr_count($course_name, 'אבלטון') != true) {
                $eligible = false;
                echo '<script>console.log("BPM 003");</script>';
            } else if ( substr_count($course_name, 'קיובייס' ) != true) {
                   $eligible = false;
                   echo '<script>console.log("BPM 003");</script>';
            }
        } else {
            
            if ($course_type == $BU_CFG->COURSE_TYPES['program']) {
                
                if (substr_count($course_name, 'כללי') == 0) {
                    if ((substr_count($course_name, 'אבלטון') ==  0) && (substr_count($course_name, 'קיובייס') ==  0)) {
                        $eligible = false;
                        echo '<script>console.log("BPM 004");</script>';
                    }
                } else {
                    if ($program_name == 'DMP') {
                        $fails_allowed = 0;
                    }
                    $number_of_fails = bpm_get_number_of_fails($user_id, $program_name, $passing_grade);
                    if ($number_of_fails > $fails_allowed) {
                        $eligible = false;
                        echo '<script>console.log("BPM 005");</script>';
                    }
                    echo '<script>console.log("' . $user_id . '");</script>';
                    echo '<script>console.log("fails: ' . $number_of_fails . ', allowed: ' . $fails_allowed . '");</script>';
                }
            } else {
                
                if (bpm_passed_course($user_id, $course_id, $passing_grade) != true) { 
                    $eligible = false;
                    echo '<script>console.log("BPM 006");</script>';
                    // echo "<p>did not pass</p>";
                } else {
                    $eligible = true;
                    echo '<script>console.log("BPM 0066");</script>';
                    // echo "<p>passed</p>";
                }
            }
        }
    }
    return $eligible;
}

//returns 1 for program, 2 standalone;
function bpm_get_course_type($course_name, &$program_name = NULL) {
    global $BU_CFG;
    
    $program_names = $BU_CFG->PROGRAM_NAMES;
    for ($name_index=0; $name_index < count($program_names); $name_index++) { 
        if (strpos($course_name, $program_names[$name_index]) !== false) {
            $program_name = $program_names[$name_index];
            return $BU_CFG->COURSE_TYPES['program'];
        }
    }
    return $BU_CFG->COURSE_TYPES['standalone'];
}

function bpm_passed_electric_safety($user_id, $course_name, $program_name) {
    global $BU_CFG, $DB;

    if (bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY_STRING, $program_name) ||
        bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY_STRING, $course_name)) {
                            
            //courses that require only an attendance session called 'בטיחות בחשמל'
            if (bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY__ATTENDANCE_SESSION_STRING, $course_name)) {
                
                //not using $course_id in the following sql because it doens't matter which course they had electric safety in, as long as they had it
                $electric_sql = "SELECT COUNT(al.id)
                                FROM mdl_attendance_log al, mdl_attendance_sessions asess, mdl_attendance_statuses astats, mdl_course c, mdl_attendance a, mdl_user u
                                WHERE al.sessionid = asess.id
                                AND asess.attendanceid = a.id
                                AND a.course = c.id
                                AND al.statusid = astats.id
                                AND astats.description = 'נוכח'
                                AND asess.description LIKE '%בטיחות בחשמל%'
                                AND studentid = u.id
                                AND u.id = $user_id";
                                
                $electric_session_count = $DB->get_field_sql($electric_sql, NULL);
                
                if ($electric_session_count >= 1) {
                    return true;
                } else {
                    if (!bpm_check_for_es_course_reg_and_completion($user_id)) {
                        return false;
                    }
                }
            } else {
                if (!bpm_check_for_es_course_reg_and_completion($user_id)) {
                    return false;
                }
            }
    }
    return true;
}
function bpm_check_for_es_course_reg_and_completion($user_id) {
    global $DB;
    
    
    $electric_sql = "SELECT c.id
                     FROM mdl_user_enrolments ue, mdl_enrol e, mdl_course c
                     WHERE ue.enrolid = e.id
                     AND e.courseid = c.id
                     AND ue.userid = $user_id
                     AND c.fullname LIKE '%בטיחות בחשמל%'";
                     
     //echo $electric_sql;
    $electric_course_id = $DB->get_field_sql($electric_sql, NULL);
    
    if ($electric_course_id) {
        $passed_grade_sql = "SELECT gi.gradepass
                             FROM mdl_grade_items gi
                             WHERE gi.courseid = $electric_course_id
                             AND gi.itemtype = 'course'";
        $passed_grade = $DB->get_field_sql($passed_grade_sql, NULL);

        $passed_sql = "SELECT count(se.id)
                       FROM mdl_sf_enrollments se
                       WHERE se.userid = $user_id
                       AND se.courseid = $electric_course_id
                       AND se.grade >= $passed_grade";
        $electric_flag = $DB->get_field_sql($passed_sql, NULL);
        $message = ($electric_flag >= 1) ? 'yep' : 'nope';
        echo "<script>console.log('" . $message . "');</script>";    
        return $electric_flag >= 1 ? true : false;
    } else {
        return false;
    }
}

function bpm_get_number_of_fails($user_id, $program_name, $passing_grade) {
    global $DB;
    
    $sql = "SELECT COUNT(*)
            FROM  mdl_sf_enrollments
            WHERE userid = $user_id 
            AND ((grade < $passing_grade) OR (attendance < 80) OR (completegrade = 0)
            OR (grade=-1) OR (attendance=-1))
            AND exempt <> 1
            AND  courseid in (SELECT id
                               FROM mdl_course c
                               WHERE fullname like '%$program_name%')";
                               
    echo "<script>console.log('sql: " . urlencode($sql) . "')</script>";
    
    return $DB->get_field_sql($sql);
}

function bpm_get_failed_courses($user_id, $program_name, $passing_grade) {
    global $DB;
    
    $sql = "SELECT courseid
            FROM  mdl_sf_enrollments
            WHERE userid = $user_id 
            AND ((grade < $passing_grade) OR (attendance < 80) OR (completegrade = 0)
            OR (grade=-1) OR (attendance=-1))
            AND   courseid in (SELECT id
                               FROM mdl_course c
                               WHERE fullname like '%$program_name%')";
    return $DB->get_field_sql($sql);
}


function bpm_passed_course($user_id, $course_id, $passing_grade) {
    global $DB;
    
    $sql = "SELECT grade
            FROM mdl_sf_enrollments
            WHERE userid = $user_id
            AND   courseid = $course_id
            AND   completegrade = 1
			AND   attendance >= 80";
    
    $grade = $DB->get_field_sql($sql);

    if (!$grade) {
        return false;
    } else {
        return $grade >= $passing_grade;
    }
}

function bpm_strpos_array($array, $string) {
    if (isset($string)) {
        foreach ($array as $array_string) {
            if  (strpos($string, $array_string) !== false){
                return true;
            }
        }
    }
}

function user_in_debt($user_id) {
        global $DB;
        
        $sql = "SELECT debt FROM mdl_sf_user_data WHERE user_id = $user_id";
        return ($DB->get_field_sql($sql) != NULL);
}