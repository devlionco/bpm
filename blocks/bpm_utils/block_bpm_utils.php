<?php
// This file is part of the bpm_utils block for Moodle - http://moodle.org/
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
 * Class definition for the bpm_utils block
 *
 * @author  Ben Laor, BPM
 * @package blocks/bpm_utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/config.php");

class block_bpm_utils extends block_base {
    public function init() {
        $this->title = get_string('bpm_utils', 'block_bpm_utils');
    }

    public function get_content() {
        global $COURSE, $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $course_parent_sf_id = $DB->get_record('course_details', array('courseid' => $COURSE->id))->coursefather;
        $course_parent_id = $DB->get_record('sf_course_parent_data', array('sf_id' => $course_parent_sf_id))->course_id;
        $this->content = new stdClass;
        $this->content->text = '';

        // Profile picture update URL
        $sf_id = $this->bpm_get_user_sfid($USER->id, $COURSE->id);
        if (($DB->get_field('user', 'picture', array('id' => $USER->id)) == 0) && ($sf_id != '')) {
            $url = "https://my.bpm-music.com/Extra_content/profile_picture/upload_profile_picture.html?id=" . $sf_id;
            $html = "<a class=\"bpm_utils_links\" href=" . $url . 
                    "><i class=\"fa fa-user\">" . 
                    "</i>" . get_string('profilepicturelink', 'block_bpm_utils') . "</a>";
            $this->content->text .= $html;
        }

        //class schedule url
        if ($schedule_url = $this->get_schedule_url($COURSE)) {
            $schedule_html = '<script>window.addEventListener(\'load\',  function() {var scheduleBtn = "<a class=\"list-group-item list-group-item-action\" target=\"_blank\" href=\"' .
                                $schedule_url . '\"><div class=\"m-1-0\">מערכת שעות   <i class=\"fa fa-external-link\" aria-hidden=\"true\"></i></div></a>";' .
                                'document.querySelector(\'#nav-drawer .list-group-item-action\').insertAdjacentHTML("afterend", scheduleBtn);'. '})</script>';
            echo $schedule_html;
        }
    
        // Student forms URL
        $url = "https://my.bpm-music.com/Extra_content/student_approved_absence/absence_request.html?userId=" . $USER->id;
        $html = "<a class=\"bpm_utils_links\" href=" . $url . 
                "><i class=\"fa fa-user-times\">" . 
                "</i>בקשה להיעדרות מוצדקת</a>";
        $this->content->text .= $html;

        if ($this->bpm_check_ssd_capabilities($USER->id)) {
            
            // Opening presentation URL
            $url = "https://docs.google.com/presentation/d/1bEsoofQ3sS_0tpOz7vdYaAQ3yV3GUj1HdNc6in_hZPg/present"; // previously "https://docs.google.com/presentation/d/1y1DCzL2Dwtsgp69-RTcSqjph7-mrKm09ycUgGXxR4gA/present";
            $html = "<a class=\"bpm_utils_links\" href=" . $url . 
                    "><i class=\"fa fa-television\">" . 
                    "</i>" . get_string('openingpresentationlink', 'block_bpm_utils') . "</a>";
            $this->content->text .= $html;
            
            // Postpone classes module
            if ($postpone_html = $postpone_html = $this->bpm_get_postpone_html($COURSE->id)) {
                $this->content->text .= $postpone_html;
                $this->content->text .= $this->bpm_get_postpone_js($COURSE->id, $COURSE->enddate);
            }
        }

        // Time extensions URL
        $assignment_id = $this->bpm_allowed_time_extension();
        if ($assignment_id) {
            $url = new moodle_url('/blocks/bpm_utils/extension/extension_request_view.php', 
                                  array('courseid' => $COURSE->id,
                                        'userid' => $USER->id,
                                        'assignmentid' => $assignment_id));
            $html = "<a class=\"bpm_utils_links\" href=" . $url . 
                    "><i class=\"fa fa-calendar-plus-o\">" . 
                    "</i>" . get_string('extensionreq', 'block_bpm_utils') . "</a>";
            $this->content->text .= $html;
        }
        
       //assignment share feedback
        if ($USER->id == 3 || $USER->id == 4058) {
            $url = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "id"));

        if ($url == "/mod/assign/view.php?") {
                $course_module_id = $_GET['id'];
                $assign_share_url = $_SERVER['DOCUMENT_ROOT'] . '/Extra_content/share_assignment_feedback/renderer.php';
                require_once($assign_share_url);
            }
        }
        
        //TEMP covid19
        //remove option קבע פגישת קידום בוגרים
         echo "<script>document.addEventListener('DOMContentLoaded', function() {
                    $('a[title=\'קביעת פגישת קידום בוגרים\']').addClass('es_unavailable').attr({href: '#', target: ''}).click(function(){
                        alert('זמנית לא ניתן לתאם פגישות קידום בוגרים. אנו מתנצלים על אי הנוחות ומקווים להחזיר את השירות לאוויר בהקדם!');
                        return false;
                        });
                    }, false);</script>";
        
        
        //es registration url
        if (!$this->is_bpm_staff($USER->id)) {
            if (!$this->bpm_check_for_es_course_reg_and_completion($USER->id)) { //no es
                // echo "<script>console.log('no es');</script>"; 
                $passed_time_since_graduation = $this->alumni_for_months($USER->id, "3 months"); //are they allowed access to register?
                if ($passed_time_since_graduation) {
                    // echo "<script>console.log('over 3 months since last course ended, no es reg url');</script>"; 
                    
                      echo "<script>document.addEventListener('DOMContentLoaded', function() {
                        $('a[title=\'הרשמה לקורס בטיחות בחשמל\']').addClass('es_unavailable').attr({href: '#', target: ''}).click(function(){
                            alert('מאחר ולימודיך הסתיימו לפני יותר מ-3 חודשים, לא ניתן להרשם ל\'בטיחות בחשמל\' ללא תשלום.\\nיש לתאם הרשמה ותשלום עבורה מול מזכירות המכללה בטלפון: 03-5604781');
                            return false;
                            });
                        }, false);</script>";
                } else {
                    //echo "<script>console.log('no es,  url');</script>";
                }
            } else {
               // echo "<script>console.log('passed es');</script>";
               
                echo "<script>document.addEventListener('DOMContentLoaded', function() {
                        $('a[title=\'הרשמה לקורס בטיחות בחשמל\']').remove();
                        }, false);</script>";
            }
        
            
            //hide es registration url from header if user is taking courses in Haifa and none in telaviv
            // $course_list = $this->bpm_get_course_list($USER->id);
            $branch_list = $this->bpm_get_branch_list($USER->id);
            $branch_names = array();
            foreach ($branch_list as $course) {
                array_push($branch_names, $course->branch);
            }
            $branch_names_string = implode("|", $branch_names);
            $haifa = false; $tel_aviv = false; $online = false;
            
            if (strpos($branch_names_string, "חיפה") > -1) {
                $haifa = true;
            }
            if (strpos($branch_names_string, "אונליין") > -1) {
                $online = true;
            }
            if (strpos($branch_names_string, "תל אביב") > -1) {
                $tel_aviv = true;
            }
            
                
            if (!$this->bpm_check_ssd_capabilities($USER->id)) {
                $this->content->text .= $this->bpm_branch_links_to_hide($tel_aviv, $haifa, $online);
            }
    
            if (!$tel_aviv) {
                echo "<script>document.addEventListener('DOMContentLoaded', function() {
                    $('a[title=\'הרשמה לקורס בטיחות בחשמל\']').remove();
                    }, false);</script>";
            }
            if (!$tel_aviv && !$haifa && !$this->is_bpm_staff($USER->id)) {
                    echo "<script>document.addEventListener('DOMContentLoaded', function() {
                    $('a[title=\'מערכת לקביעת אולפנים\']').remove();
                    }, false);</script>";
            }
        }
        // Certificates URL
        $course_type = $this->bpm_get_course_type($COURSE->fullname);
        $branch = strpos($COURSE->shortname, " חיפה") >0 ? "haifa" : "_";
        
        if ($this->bpm_cert_check_eligibility($USER->id, $COURSE->fullname, $COURSE->enddate, $course_parent_id, $COURSE->startdate)) {
            $cert_issuing_url = new moodle_url('/blocks/bpm_utils/cert_download_view.php', array('courseparentid' => $course_parent_id,
                                                                                                 'courseid' => $COURSE->id,
                                                                                                 'userid' => $USER->id,
                                                                                                 'coursetype' => $course_type,
                                                                                                 'branch' => $branch));
            $html = "<a class=\"bpm_utils_links\" href=" . $cert_issuing_url . 
                    "><i class=\"fa fa-certificate\">" . 
                    "</i>" . get_string('selfcert', 'block_bpm_utils') . "</a>";
            
            $this->content->text .= $html;
        }
        $is_director = $this->bpm_check_director_capabilities($USER->id);
        if (( $is_director || 
            $this->bpm_check_instructor_capabilities($USER->id)) &&
            $COURSE->id <> 1) {
            $html = '<a class="bpm_utils_links" href="#" id="sendMidFeed">' .
                    '<i class="fa fa-share-square-o" aria-hidden="true"></i>שליחת משוב אמצע</a>';
            if ($is_director) {
                $html .= '<a class="bpm_utils_links" href="#" id="sendMidSum">' . 
                        '<i class="fa fa-list" aria-hidden="true"></i>סיכום משוב אמצע</a>';
            }
            $this->content->text .= $html;
            $this->content->text .= $this->bpm_get_mid_feed_js($COURSE->id);
        }
        
        $url = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "id"));
        if ($url == "/course/view.php?" && $this->is_bpm_staff($USER->id)) {
            
            //add button for quick sharing of zoom url  
            $zoom_script_loader = "<script>window.addEventListener('load',  function() {var zoomScript = document.createElement('script');
                                    zoomScript.setAttribute('src','https://my.bpm-music.com/Extra_content/zoomvideo/quick_share_url.js');
                                    zoomScript.setAttribute('userid'," . $USER->id . ");
                                    zoomScript.setAttribute('courseid'," . $COURSE->id . ");
                                    document.head.appendChild(zoomScript);
                                    });
                                    </script>";
            echo $zoom_script_loader;
            
            //if ($COURSE->id == 608) {
                $sql = "SELECT qm.name, qm.id, qm.timeclose, cm.visible, cm.id as cmid
                        FROM mdl_quiz qm, mdl_course_modules cm
                        WHERE qm.course = cm.course
                        AND cm.instance = qm.id
                        AND qm.course = $COURSE->id
                        AND qm.name LIKE '%מבחן%'";
                if ($_SESSION['quizmodules'] = $DB->get_records_sql($sql)) { //found quiz modules
                    require_once(__DIR__ . "/start_quiz/start_quiz.php");
                }
            //}
        }
        if ($url == "/course/view.php?") {
            $rec_file_path = $_SERVER['DOCUMENT_ROOT'] . '/Extra_content/zoomvideo/recordings_folder_button_renderer.php';
             require_once($rec_file_path);
             
             //tech class survey - 19.8.20 - removed
            //  if ($this->bpm_check_instructor_capabilities($USER->id) || $USER->id == 3) {
            //     $technical_class_survey_renderer = $_SERVER['DOCUMENT_ROOT'] . '/Extra_content/instructor_tech_survey/renderer.php';
            //     require_once($technical_class_survey_renderer);
                
            //  }
        }
        

        $this->content->text .= $this->bpm_print_userid_to_element($USER->id);
        $this->content->text .= $this->bpm_staff_search_engine_url($USER->id);
        $this->content->text .= $this->bpm_acuity_scheduling_url($COURSE->id);
        $this->content->text .= $this->bpm_get_makeup_test_url($COURSE->id);
        $this->content->text .= $this->bpm_exercise_bank_url($USER->id);
        
        if (!$course = $DB->get_record('course', array('id' => $COURSE->id))) {
            $this->content->text = '';
        }
        return $this->content;
    }

    public function has_config() {
        return true;
    }

    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('defaulttitle', 'block_bpm_utils');            
            } else {
                $this->title = $this->config->title;
            }
     
            if (empty($this->config->text)) {
                $this->config->text = get_string('defaulttext', 'block_bpm_utils');
            }    
           }
    }
    
    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block_'. $this->name(); // Append our class to class attribute
        return $attributes;
    }

    public function bpm_cert_check_eligibility($user_id, $course_name, $course_end_date, $course_father_id, $course_start_date) {
        global $BU_CFG, $COURSE;
        
        $es_father_id = 141; //const
        if ($course_father_id == $es_father_id) {echo "<script>console.log('BPM 009 - ES');</script>"; return false;}
        
        $eligible = false;
        if ($this->user_in_debt($user_id)) {
            echo '<script>console.log("BPM 007");</script>';
            return false;
        }
        if ($course_end_date) {
            $eligible = true;
            $program_name;
            $two_months_ago = strtotime("-2 months" , time());
            $fails_allowed = 1;

            $music_type = $this->bpm_strpos_array($BU_CFG->MUSIC_COURSES_NAMES, $course_name);
            $passing_grade = $music_type ? 60 : 80;
            $course_type = $this->bpm_get_course_type($course_name, $program_name);

            if (($this->bpm_is_english_name($user_id, 'user') != true) || ($this->bpm_is_english_name($course_father_id, 'course') != true)) {
                $eligible = false;
                echo '<script>console.log("BPM 001");</script>';
            }
            
            //commented out by erez 15/4, as soon as a grade is complete the option should be available
            /*
            if ($two_months_ago < $course_end_date) {
                $eligible = false;
                echo '<script>console.log("BPM 002");</script>';
            }*/
            
            
            //We're going to check for several variations on the electric safety eligibility condition
            //But first - Cubase SA courses that started before Oct-18 did not document ES sessions, so they get an auto-pass on this,
            //via the following block, using $bypass_es as a flag for irrelevant if statements that follow this one:
            $october_first_2018 = 1538341200;
            $cubase_father_id = 129;
            $bypass_es = false;
            if ($course_father_id == $cubase_father_id && $course_start_date < $october_first_2018) {
                $bypass_es = true;
                //echo '<script>console.log("skipping, es = true");</script>';
            }
            if (strpos($COURSE->shortname, "חיפה")) {
                $bypass_es = true;
            }
            if ($this->bpm_passed_electric_safety($user_id, $course_name, $program_name) != true && $bypass_es == false) {
                echo '<script>console.log("BPM 003");</script>';
                if (substr_count($course_name, 'אבלטון') != true) {
                    $eligible = false;
                    echo '<script>console.log("BPM 0003");</script>';
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
                        //echo "<script>console.log('" . $program_name . "');</script>";
                        $number_of_fails = $this->bpm_get_number_of_fails($user_id, $program_name, $passing_grade);
                        echo "<script>console.log('num f = " . $number_of_fails . "');</script>";
                        if ($number_of_fails > $fails_allowed) {
                            $eligible = false;
                            echo '<script>console.log("BPM 005");</script>';
                        }
                    }
                } else {
                    /* Here we use the COURSE object because we want the grade of the actual course taken 
                      and not the course parent */
                    if ($this->bpm_passed_course($user_id, $COURSE->id, $passing_grade) != true) { 
                        $eligible = false;
                        echo '<script>console.log("BPM 006");</script>';
                    }
                }
            }
        }
        return $eligible;
    }

    //returns 1 for program, 2 standalone;
    public function bpm_get_course_type($course_name, &$program_name = NULL) {
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

    public function get_schedule_url($course_obj) {
        global $DB;
        
        $courseid = $course_obj->id;
        if ($this->bpm_get_course_type($course_obj->shortname) == 1) {
            if (strpos($course_obj->shortname, "כללי") === false) {
                $clali_sql = "SELECT c.id FROM mdl_course c 
                    WHERE category = $course_obj->category
                    AND shortname LIKE '%כללי%'";
                if ($clali_course = $DB->get_record_sql($clali_sql)) {
                    $courseid = $clali_course->id;
                }
            }
        }
        $schedule_url = $DB->get_record('bpm_course_schedule', array('courseid' => $courseid))->schedule_url;
        return $schedule_url;   
    }
        
    public function bpm_passed_electric_safety($user_id, $course_name, $program_name) {
        global $BU_CFG, $DB;

        if ($this->bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY_STRING, $program_name) ||
            $this->bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY_STRING, $course_name)) {
                
                //courses that require only an attendance session called 'בטיחות בחשמל'
                if ($this->bpm_strpos_array($BU_CFG->ELECTRIC_MANDATORY__ATTENDANCE_SESSION_STRING, $course_name)) {
                    
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
                        if (!$this->bpm_check_for_es_course_reg_and_completion($user_id)) {
                            return false;
                        }
                    }
                } else {
                    
                    if (!$this->bpm_check_for_es_course_reg_and_completion($user_id)) {
                            return false;
                        }
                }
        }
        return true;
    }
    public function bpm_check_for_es_course_reg_and_completion($user_id) {
        global $DB;
        $electric_session_sql = "SELECT COUNT(al.id)
                                    FROM mdl_attendance_log al, mdl_attendance_sessions asess, mdl_attendance_statuses astats, mdl_course c, mdl_attendance a, mdl_user u
                                    WHERE al.sessionid = asess.id
                                    AND asess.attendanceid = a.id
                                    AND a.course = c.id
                                    AND al.statusid = astats.id
                                    AND astats.description = 'נוכח'
                                    AND asess.description LIKE '%בטיחות בחשמל%'
                                    AND studentid = u.id
                                    AND u.id = $user_id";
                                    
                    $electric_session_count = $DB->get_field_sql($electric_session_sql, NULL);
        
        $electric_sql = "SELECT c.id
                         FROM mdl_user_enrolments ue, mdl_enrol e, mdl_course c
                         WHERE ue.enrolid = e.id
                         AND e.courseid = c.id
                         AND ue.userid = $user_id
                         AND c.fullname LIKE '%בטיחות בחשמל%'
                         ORDER BY c.startdate DESC";
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
                           AND ((se.attendance >= 80 
                           AND se.grade >= $passed_grade) OR se.exempt = '1')";
            $electric_flag = $DB->get_field_sql($passed_sql, NULL);
            $message = ($electric_flag >= 1) ? 'yep' : 'nope';
            //echo "<script>console.log('" . $message . "');</script>";    
            return $electric_flag >= 1 ? true : false;
        } else if ($electric_session_count > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    public function bpm_get_number_of_fails($user_id, $program_name, $passing_grade) {
        global $DB;
        
        
        $sql = "SELECT COUNT(*)
                FROM  mdl_sf_enrollments
                WHERE userid = $user_id 
                AND ((grade < $passing_grade) OR (attendance < 80) OR (completegrade = 0))
                AND (grade <> -1 AND attendance <> -1)
                AND   courseid in (SELECT id
                                   FROM mdl_course c
                                   WHERE fullname like '%$program_name%')";
                                   
        return $DB->get_field_sql($sql);
    }

    public function bpm_passed_course($user_id, $course_id, $passing_grade) {
        global $DB;
        
        $sql = "SELECT grade
                FROM mdl_sf_enrollments
                WHERE userid = $user_id
                AND   courseid = $course_id
                AND   completegrade = 1";
        $grade = $DB->get_field_sql($sql);

        if (!$grade) {
            return false;
        } else {
            return $grade >= $passing_grade;
        }
    }
    
    public function bpm_strpos_array($array, $string) {
        if (isset($string)) {
            foreach ($array as $array_string) {
                if  (strpos($string, $array_string) !== false){
                    return true;
                }
            }
        }
    }

    public function bpm_is_english_name($id, $type) {
        global $DB;
        $result = 0;

        if ($id) {
            $table = ($type == 'user') ? 'mdl_sf_user_data' : 'mdl_sf_course_parent_data';
            $where_type = ($type == 'user') ? 'user_id' : 'course_id';

            $sql = "SELECT count(english_name) 
                    FROM $table
                    WHERE $where_type = $id
                    AND english_name <> ''";
            $result = $DB->get_field_sql($sql);
        }
        
        return ($result == 1);
    }

    public function bpm_check_ssd_capabilities($user_id) {
        global $DB, $BU_CFG;

        $ssd_role_sql = "SELECT count(id)
                         FROM mdl_role_assignments
                         WHERE userid = $user_id
                         AND   roleid IN ($BU_CFG->SSD_ROLE_ID, $BU_CFG->COURSE_MANAGER_ROLE_ID)";

        if (($DB->get_field_sql($ssd_role_sql) != 0) || is_siteadmin($user_id)) {
            return true;
        } else {
            return false;
        }
    }

    public function bpm_get_postpone_html($course_id) {
        global $DB;

        $html = "<select class=\"custom-select\" id=\"postpone_lov\" name=\"מפגשי נוכחות\"><option disabled selected value>בחר/י מפגש</option>";

        $sql = "SELECT a.id, ass.id, ass.sessdate
                FROM   mdl_attendance_sessions ass,
                       mdl_attendance a
                WHERE a.id = ass.attendanceid
                AND   a.course = $course_id
                AND   ass.sessdate >= UNIX_TIMESTAMP(CURDATE())
                ORDER BY ass.sessdate ASC";

        $attendance_sessions = $DB->get_records_sql($sql);

        if (count($attendance_sessions) == 0) {
            return false;
        }

        foreach ($attendance_sessions as $current_session) {
            $formatted_date = date("d/m/Y", $current_session->sessdate);
            $html .= "<option value=\"" . $current_session->id . "\">" . $formatted_date . "</option>";
        }
        $html .= "</select>";
        $html .= "<button class=\"btn btn-primary\" id=\"postpone_button\" disabled=\"true\">ביטול מפגש</button>";
        return $html;
    }

    public function bpm_staff_search_engine_url($user_id) {
        global $BU_CFG;
        
        $is_staff = false;
        foreach($BU_CFG->STAFF_ROLES as $role_id) {
            if (user_has_role_assignment($user_id,$role_id)) {
                $is_staff = true;
            }    
        }
        
        if (!$is_staff) {
            return false;
        } else {
            $html = '<a href="#" class="bpm_utils_links" ' . ' id="bpmstaffSearchModal" onclick="bpmSearchModal()">' .
                            '<i class="fa fa-search" style="font-size:1.5em;vertical-align:bottom;margin-left:6px;"></i>' .
                    'חיפוש סטודנטים וקורסים';
            $html .= '<script>function bpmSearchModal() {
                        $.get("https://my.bpm-music.com/blocks/bpm_utils/search/search_modal.php", function(data){
                            let searchModal = data;
                            $("body").append(searchModal);
                        });
                        
                    }</script>';
                    
            return $html;
        }
    }

    public function bpm_get_postpone_js($course_id, $course_enddate) {
        $js = "<script type='text/javascript'>
                var url_prefix = \"https://my.bpm-music.com/blocks/bpm_utils/postpone_attendance_view.php?sessionid=\";
                var url_params = \"&courseid=" . $course_id . "&courseenddate=" . $course_enddate . "\";
                var postponeLov = document.getElementById(\"postpone_lov\");
                var postponeButton = document.getElementById(\"postpone_button\");
                postponeLov.addEventListener('change', function() {  
                    if (this.value != '') {
                        postponeButton.disabled = false;
                        var url = url_prefix + this.value + url_params;
                        postponeButton.addEventListener('click', function() {
                            window.location = url;
                        });
                    }
                });
               </script>";
        return $js;
    }

    public function bpm_allowed_time_extension() {
        global $DB;

        $url = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "id"));

        if ($url == "/mod/assign/view.php?") {
            if (isset($_GET['id'])) {
                
                $course_module_id = $_GET['id'];
                $sql = "SELECT ass.id
                        FROM mdl_assign ass, mdl_course_modules cm
                        WHERE ass.id = cm.instance
                        AND cm.id = $course_module_id
                        AND ass.duedate != 0
                        AND ass.nosubmissions = 0";

                return $DB->get_field_sql($sql);
            }
        } else {
            return false;
        }
    }

    public function bpm_check_director_capabilities($user_id) {
        global $DB, $BU_CFG;
        $allowed = false;

        $ssd_role_sql = "SELECT count(ra.id)
                         FROM mdl_role_assignments ra, 
                              mdl_role r
                         WHERE r.id = ra.roleid
                         AND   ra.userid = $user_id
                         AND   r.shortname LIKE 'programdirector'";

        if (($DB->get_field_sql($ssd_role_sql) != 0) || 
            is_siteadmin($user_id) || 
            ($user_id == 45 || $user_id == 121)) {
            return true;
        } else {
            return false;
        }
    }


    public function bpm_check_instructor_capabilities($user_id) {
        global $DB, $BU_CFG;
        $allowed = false;

        $instructor_role_sql = "SELECT count(ra.id)
                         FROM mdl_role_assignments ra, 
                              mdl_role r
                         WHERE r.id = ra.roleid
                         AND   ra.userid = $user_id
                         AND   r.shortname LIKE 'instructor'";
        if (($DB->get_field_sql($instructor_role_sql) != 0) 
            /*
            || 
            is_siteadmin($user_id) || 
            ($user_id == 45 || $user_id == 121)) 
            */)
            {
            return true;
        } else {
            return false;
        }
    }
    
    public function bpm_get_mid_feed_js($course_id){
        $js = "<script type='text/javascript'>
                $(\"#sendMidFeed\").on(\"click\", function(){
                    if (window.confirm(\"האם את/ה בטוח/ה שברצונך לשלוח משוב?\")) {
                        var midFeedUrl = \"https://my.bpm-music.com/Extra_content/student_feedback/midterm_feedback/send_midterm_feedback.php?input=" . 
                                      $course_id . "\";
                        $.get(midFeedUrl, function() {}).always(function() {
                            alert(\"נשלח משוב בהצלחה\");
                        });
                    }
                });

                $(\"#sendMidSum\").on(\"click\", function(){
                    if (window.confirm(\"האם את/ה בטוח/ה שברצונך לשלוח סיכום משוב?\")) {
                        var midSumUrl = \"https://script.google.com/macros/s/AKfycbzTd9yWH1e2n-sXX32tqowdaozaBmRYGwutj_8Ik06lQNOLZoFL/exec?cid=" . 
                                          $course_id . "\";
                        $.get(midSumUrl, function() {}).always(function() {
                            alert(\"נשלח סיכום בהצלחה\");
                        }); 
                    }
                });    
               </script>";
        return $js;
    }

    public function bpm_get_user_sfid($user_id, $course_id) {
        global $DB, $COURSE;

        $sql = "SELECT sf_id
                FROM mdl_sf_user_data
                WHERE user_id = $user_id
                LIMIT 1";
        $sf_id = $DB->get_field_sql($sql);        

        return $sf_id;
    }

    public function bpm_print_userid_to_element($user_id) {
        $js = "<script type='text/javascript'>
               var elements = document.getElementsByClassName(\"moodleUserId\");
               if (elements.length > 0) {
                    elements[0].innerHTML = $user_id;
               }
               </script>";
        return $js;
    }
    
    public function bpm_exercise_bank_url($user_id) {
        global $DB, $BU_CFG, $CFG, $USER;
        $arr = $BU_CFG->EXERCISE_BANK_COURSEPARENT_MATCHING_IDS; //array(76, 100, 659,81, 98, 133, 68, 95, 108, 119, 129,79, 83, 93,72,68, 95, 108, 119, 129,77, 101, 699);
        $str = implode(", ", $arr);
        $query = "SELECT DISTINCT cp.course_id
                FROM mdl_enrol e, mdl_user_enrolments ue, mdl_course c, mdl_user u, mdl_sf_course_parent_data cp, mdl_course_details cd
                WHERE u.id = " . $user_id .
                " AND cd.courseid = c.id
                    AND cp.course_id IN ($str)
                    AND cd.coursefather = cp.sf_id
                    AND u.id = ue.userid 
                    AND e.id = ue.enrolid
                    AND c.id = e.courseid
                    AND c.startdate <= now()";
                    
                    
        $query_result =  $DB->get_records_sql($query);
        
        $is_staff = false;
        foreach($BU_CFG->STAFF_ROLES as $role_id) {
            if (user_has_role_assignment($USER->id,$role_id)) {
                $is_staff = true;
            }    
        }
        
        if (count($query_result) == 0 && !$is_staff) {
            return false;
        }
        $html = '<a href="/Extra_content/exercise_bank/exercise_bank.php" class="bpm_utils_links" ' . ' id="bpmExerciseBankUrl">' .
                            '<i class="fa fa-bullseye" aria-hidden="true"></i>
                    מאגר חומרי תרגול';
        /*if (true) { // TODO cutoff date?                     
            $html .= '<span id="newFancyText">חדש!</span>';
        }*/
         $html .= '</a>';
            return $html; 
        }
    
    public function alumni_for_months($userid, $interval) { //returns bool
        global $DB;
        
        $sql = "SELECT u.id, u.firstname, u.lastname, ue.status, c.shortname, c.enddate, from_unixtime(c.enddate)
                FROM mdl_user u, mdl_user_enrolments ue, mdl_enrol e, mdl_course c
                WHERE u.id = ue.userid
                AND ue.enrolid = e.id
                AND c.enddate != 0
                AND e.courseid = c.id
                AND u.id = $userid
                ORDER BY enddate DESC
                LIMIT 1
                ";
        $regs = $DB->get_records_sql($sql);
        $flag = false;
        foreach($regs as $reg) {
            
            $course_end_cutoff = strtotime('+' . $interval, $reg->enddate); //* 1000;
            if (time() > $course_end_cutoff) {
                echo "<script>console.log('true,  ' + " .time() . " + ' > $course_end_cutoff');</script>";
                $flag = true;
            } else {
                //$flag = false;
                
                echo "<script>console.log('false,  ' + " .time() . " + ' < $course_end_cutoff');</script>";
            }
            return $flag;
            
        }
        
        
    }
    
    public function bpm_acuity_scheduling_url($course_id) {
        global $DB, $COURSE;
        if ($course_id == 1) {
            return false;
        }

        
        $course_parent_sql = "SELECT DISTINCT cp.course_id as parentid,
                                cd.branch
                                FROM mdl_course c, 
                                mdl_sf_course_parent_data cp, 
                                mdl_course_details cd 
                                WHERE cd.courseid = $course_id
                                AND cd.coursefather = cp.sf_id";
        
        if (!$course_info = $DB->get_record_sql($course_parent_sql)) {
            //debug
            //echo "<script>console.log('BPM 007, course id: " . $course_id . "');</script>";
            //return false;
        }
 echo "<script>console.log('" . $course_id . "');</script>";
        $acuity_match_sql = "SELECT appointment_type_id, name FROM mdl_bpm_acuity_appointments 
                            WHERE 
                            name NOT LIKE '%מועדי ב%'
                            AND
                            ((LOCATE(BINARY ' $course_info->parentid,', CONCAT(' ', course_parent_ids))
                            OR LOCATE(BINARY ' $course_info->parentid ', CONCAT(' ', course_parent_ids, ' '))
                            OR LOCATE(BINARY ',$course_info->parentid ', CONCAT(' ', course_parent_ids, ' ')))
                            AND (branch LIKE '$course_info->branch' OR ('$course_info->branch' = NULL)))
                            OR LOCATE(BINARY ' $course_id ', CONCAT(' ', course_parent_ids, ' '))";
        
        //debug
        //  echo "<script>console.log('" . urlencode($acuity_match_sql) . "');</script>";
        
        if (!$acuity_row = $DB->get_record_sql($acuity_match_sql)) {
           
           //debug
           echo "<script>console.log('BPM 008, course parent_id: " . $course_info->parentid . "');</script>";
              return false;
        }
        
        $courses_w_special_covid_19_extension = array(1065,1142,1143,1186,1206,1136,1200,909,912,
                                                        910,1176,1183,1182,1227,1207,1204,987,997,996,
                                                        1240,1230,1236,1165,1318,1202,1196,1189,1145,1146,
                                                        1159,1067,1085,1201,1041,1033,1039,1040,1267,1160,1133,
                                                        1203,1134,1106,1185,1099,1265,1111,1125,1116,966,954,
                                                        951,970,971,973,1184,1217,1209,1216,1144,1140);
        $course_type = $this->bpm_get_course_type($COURSE->fullname);
        $standard_cutoff_date_str = "3 month";
        $covid_19_cutoff_date_str = "6 month";
        
        if (in_array($COURSE->id, $courses_w_special_covid_19_extension)) {
            $acuity_appt_url_availability = $covid_19_cutoff_date_str;
        } else {
            $acuity_appt_url_availability = $standard_cutoff_date_str;
        }
        
        if ($this->course_is_over($COURSE->enddate, $acuity_appt_url_availability) && $COURSE->enddate != 0) {
            if ($course_type == "1") { // programs
                
                if ($this->program_has_ended($COURSE->category,  $acuity_appt_url_availability)) {
                    // echo "<script>console.log('program has ended');</script>";
                    return false;
                }
                
            } else {
                // echo "<script>console.log('course has ended');</script>";
                return false;
            }
        }
        
        echo "<script>console.log('acuity_appt_available');</script>";
        $acuity_id = $acuity_row->appointment_type_id;
        $acuity_name = $acuity_row->name;
        $html = '<a href="https://app.acuityscheduling.com/schedule.php?owner=11342667&appointmentType=' .
                    $acuity_id . '" target="_blank" class="acuity-embed-button bpm_utils_links" ' . ' id="bpmUtilsAcuityUrl"' .
                        'style="color:#0070a8 !important;margin-bottom: 10px;"' .
                 /*   style="background: #00b1bb; 
                            color: #fff; 
                            padding: 8px 12px; 
                            border: 0px;
                            -webkit-box-shadow: 0 -2px 0 rgba(0,0,0,0.15) inset;
                            -moz-box-shadow: 0 -2px 0 rgba(0,0,0,0.15) inset;
                            box-shadow: 0 -2px 0 rgba(0,0,0,0.15) inset
                            ;border-radius: 4px;
                            text-decoration: none;
                            display: inline-block;*/'>
                            <i class="fa fa-life-ring" aria-hidden="true"></i>
                    קביעת שיעור פרטי ב' . $acuity_name .
                    '</a>
                    <script src="https://embed.acuityscheduling.com/embed/button/11342667.js" async></script>';
            return $html;
    }
    
    public function bpm_get_makeup_test_url($course_id) {
        global $DB, $COURSE;
        if ($course_id == 1) {
            return false;
        }

        
        $course_parent_sql = "SELECT DISTINCT cp.course_id as parentid,
                                cd.branch
                                FROM mdl_course c, 
                                mdl_sf_course_parent_data cp, 
                                mdl_course_details cd 
                                WHERE cd.courseid = $course_id
                                AND cd.coursefather = cp.sf_id";
        
        if (!$course_info = $DB->get_record_sql($course_parent_sql)) {
            //debug
            // echo "<script>console.log('BPM 007, course id: " . $course_id . "');</script>";
            return false;
        }
        
        $acuity_match_sql = "SELECT appointment_type_id, name FROM mdl_bpm_acuity_appointments 
                            WHERE 
                            name LIKE '%מועדי ב%'
                            AND
                            (LOCATE(BINARY ' $course_info->parentid,', CONCAT(' ', course_parent_ids))
                            OR LOCATE(BINARY ' $course_info->parentid ', CONCAT(' ', course_parent_ids, ' '))
                            OR LOCATE(BINARY ',$course_info->parentid ', CONCAT(' ', course_parent_ids, ' ')))
                            AND (branch LIKE '$course_info->branch' OR ('$course_info->branch' = NULL))";
        
        //debug
        // echo "<script>console.log('" . urlencode($acuity_match_sql) . "');</script>";
        
        if (!$acuity_row = $DB->get_record_sql($acuity_match_sql)) {
           
           //debug
           echo "<script>console.log('BPM 008, course parent_id: " . $course_info->parentid . "');</script>";
              return false;
        }
        $acuity_id = $acuity_row->appointment_type_id;
        $acuity_name = $acuity_row->name;
        $html = '<a href="https://app.acuityscheduling.com/schedule.php?owner=11342667&appointmentType=' .
                        $acuity_id . '" target="_blank" class="acuity-embed-button bpm_utils_links" ' . ' id="bpmUtilsAcuityUrl"' .
                            'style="color:#0070a8 !important;margin-bottom: 10px;">' .
                                '<i class="fa fa-repeat" aria-hidden="true"></i>' .
                                'הרשמה למועדי ב\' כלליים' .
                        
                        '</a>
                        <script src="https://embed.acuityscheduling.com/embed/button/11342667.js" async></script>';
                return $html;
        }
    
    public function course_is_over($end_date, $interval) {
        // echo "<script>console.log('time: ', " . time() . ");</script>";
        // echo "<script>console.log('enddate: ', " . $end_date. ");</script>";
        // echo "<script>console.log('strtotime_no_interval: ', " . $end_date . ");</script>";
        // echo "<script>console.log('strtotime_with_interval: ', " . strtotime('+' . $interval, $end_date) . ");</script>";
        // echo "<script>console.log('time is greater than strtotime with interval: ', " . (time() > strtotime('+' . $interval, $end_date)) ? "yes" : "no" . ");</script>";
        
        if (time() > strtotime('+' . $interval, $end_date)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function program_has_ended($category_number, $interval) {
        global $DB;
        
        $courses_sql = "SELECT id, enddate FROM mdl_course WHERE category = $category_number";
        //echo "<script>console.log('" . $courses_sql . "');</script>";
        $courses = $DB->get_records_sql($courses_sql);
        $still_active_flag = false;
        foreach($courses as $course) {
            
            $course_end_cutoff = strtotime('+' . $interval, $course->enddate);
                // echo "<script>console.log('" . $course_end_cutoff .  "');</script>";
            if (time() < $course_end_cutoff) {
                $still_active_flag = true;
            }
            
        }
        $still_active_flag = !$still_active_flag;
        return $still_active_flag;
        
    }
    
    //returns an invisible span with branch name, script in additionalHtml finds it and hides the forum, Q&A accordingly
    public function bpm_branch_links_to_hide($tel_aviv, $haifa, $online) {
        $html = '';
        if (!$tel_aviv) {
            $html .= "<span class='branchNameToHide' id='Tel Aviv' style='display:none;'>תל אביב</span>";
        }
        if (!$haifa) {
            $html .= "<span class='branchNameToHide' id='Haifa' style='display:none;'>חיפה</span>";
        }
        if (!$online) {
            $html .= "<span class='branchNameToHide' id='Online' style='display:none;'>אונליין</span>";
        }
            
        return $html;
    }
    
    
    public function bpm_get_course_list($user_id) {
    global $DB;
    
        $query = "SELECT c.shortname
                FROM mdl_enrol e, mdl_user_enrolments ue, mdl_course c, mdl_user u 
                WHERE u.id = " . $user_id .
                " AND u.id = ue.userid 
                AND e.id = ue.enrolid
                AND c.id = e.courseid";
        $query_result =  $DB->get_records_sql($query);
    
        return $query_result;
    }
    
    public function bpm_get_branch_list($user_id) {
        global $DB;
    
        $query = "SELECT cd.branch
                FROM mdl_enrol e, mdl_user_enrolments ue, mdl_course c, mdl_user u, mdl_course_details cd
                WHERE u.id = " . $user_id .
                " AND u.id = ue.userid 
                AND cd.courseid = c.id
                AND e.id = ue.enrolid
                AND c.id = e.courseid";
        $query_result =  $DB->get_records_sql($query);
    
        return $query_result;
    }
    
    public function is_bpm_staff($user_id) {
        global $DB, $CFG, $USER, $BU_CFG;
    
        if ($user_id == 3) {
            return true;
        }
        foreach($BU_CFG->STAFF_ROLES as $role_id) {
            if (user_has_role_assignment($user_id,$role_id)) {
                //echo "<script>console.log('user " . $user_id . " has role " . $role_id . "')</script>";
                return true;
            }    
        }   
        return false;
    }
    public function user_in_debt($user_id) {
        global $DB;
        
        $sql = "SELECT debt FROM mdl_sf_user_data WHERE user_id = $user_id";
        return ($DB->get_field_sql($sql) != NULL);
        // echo "<script>console.log('" . json_encode($result) . "')</script>";
        
    }
}