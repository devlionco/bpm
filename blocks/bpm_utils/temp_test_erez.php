<?php
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/../../config.php");

 rebuild_course_cache($courseid);
 
function bpm_acuity_scheduling_url($course_id) {
        global $DB;
        if ($course_id != 608) {
            return false;
        }
        else {
            $course_id = 795;
        }
        
        $course_parent_sql = "SELECT DISTINCT cp.course_id as parentid,
                                cd.branch
                                FROM mdl_course c, 
	                            mdl_sf_course_parent_data cp, 
	                            mdl_course_details cd 
                                WHERE cd.courseid = $course_id
                                AND cd.coursefather = cp.sf_id";
        
        if (!$course_info = $DB->get_record_sql($course_parent_sql)) {
            //error
              echo "<script>console.log('BPM ERROR 007, course id: " . $course_id . "');</script>";
              return false;
        }

        $acuity_match_sql = "SELECT appointment_type_id, name FROM mdl_bpm_acuity_appointments WHERE FIND_IN_SET($course_info->parentid, course_parent_ids) AND '$course_info->branch' = branch";

        if (!$acuity_row = $DB->get_record_sql($acuity_match_sql)) {
            echo "<script>console.log('BPM ERROR 008, course parent_id: " . $course_info->parentid . "');</script>";
              return false;
        }
        $acuity_id = $acuity_row->appointment_type_id;
        $acuity_name = $acuity_row->name;
                    $html = '<a href="https://app.acuityscheduling.com/schedule.php?owner=11342667&appointmentType=' .
                    $acuity_id . '" target="_blank" class="acuity-embed-button bpm_utils_acuity_url" ' .
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
                    קביעת שיעור פרטי ב' . $acuity_name .
                    '</a>
                    
                    <script src="https://embed.acuityscheduling.com/embed/button/11342667.js" async></script>';
            echo $html;
    
    }