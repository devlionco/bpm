<?php
// This file is part of the BPM student data display block for Moodle - http://moodle.org/
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
 * Class definition for the bpm_student_data_display block
 *
 * @author  Ben Laor, BPM
 * @package blocks/bpm_student_data_display
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 require_once(__DIR__ . "/../../config.php");
 require_once(__DIR__ . "/../../lib/accesslib.php");

class block_bpm_student_data_display extends block_base {
    public function init() {
        $this->title = get_string('bpm_student_data_display', 'block_bpm_student_data_display');
    }

    public function get_content() {
        global $USER, $DB, $BPM_CFG;
        $user_courses = [];

        // Init to avoid moodle warnings.
        $this->content = new stdClass;
        $this->content->text = '';
        //BPM verbose attendance report url for staff 
        if ($this->is_staff($USER->id)) {
            $var_url = "<script>console.log('hiya');</script>";
            $var_url = '<a style="display:inline-block; margin-bottom: 1em";id="bpm_vbr_url" ; .
                        href="../../local/verbose_attendance_report/?userid=' . 
                        $_GET['id'] . '">דוח נוכחות מפורט' . '</a>';
            $this->content->text .= $var_url;
        }
        $get_user_id = $_GET['id'];
           //  echo "<script>console.log('hi');</script>";
       if ($this->is_staff($USER->id)) {
            $userid = $get_user_id;
        } else {
            $userid = $USER->id;
        }
        // echo "<script>console.log('" . user_has_role_assignment(3,3) . "');</script>";
        // Fill the block with data only if the student has any grades or attendance
        $overall_row = $this->get_overall_grade_and_attendance($userid);
        if ($overall_row != false && $get_user_id == $userid) {
            $sql = "SELECT se.courseid as id, se.grade, crs.fullname as name
                    FROM mdl_sf_enrollments se, mdl_course crs
                    WHERE se.courseid = crs.id
                    AND se.userid = $userid
                    AND se.grade <> -1";

            $enrollment_rows = $DB->get_records_sql($sql);

            if (count($enrollment_rows) > 0) {
                foreach ($enrollment_rows as $current_row) {
                    
                    $course_parent_sf_id = $DB->get_field('course_details', 'coursefather', array('courseid' => $current_row->id));
                    $current_row->semester = $DB->get_field('sf_course_parent_data', 'semester', array('sf_id' => $course_parent_sf_id)); 
                }

                $courses_html = $this->bpm_get_student_data_html($enrollment_rows);
                $courses_html .= $this->bpm_get_student_data_js($overall_row);
                $this->content->text .= $courses_html;
            }
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


    public function get_overall_grade_and_attendance($user_id) {
        global $DB;

        $sql = "SELECT ROUND(AVG(grade)) as grade, ROUND(AVG(attendance)) as attendance
                FROM mdl_sf_enrollments
                WHERE userid = $user_id
                AND   (grade <> 0 AND grade <> -1)
                AND   (attendance <> 0 AND attendance <> -1)";
        $grade_attendance_row = $DB->get_record_sql($sql);

        if ($grade_attendance_row->grade != 0) {
            return $grade_attendance_row;
        } else {
            return false;
        }
    }

    /**
     * Build the courses list of values html
     *
     * @param  int  $user_id  User moodle id.
     *
     * @return string  html of the courses list of values
     *
     */
    public function bpm_get_student_data_html($user_courses) {
        $semesters_array = array('A' => '<optgroup label="סמסטר א\'">', 
                                 'B' => '<optgroup label="סמסטר ב\'">', 
                                 'C' => '<optgroup label="סמסטר ג\'">', 
                                 'D' => '<optgroup label="סמסטר ד\'">');

        $html = '<link rel="stylesheet" type="text/css" href=https://cdn.jsdelivr.net/chartist.js/latest/chartist.min.css/>';
        $html .= '<link rel="stylesheet" type="text/css" href="https://my.bpm-music.com/blocks/bpm_student_data_display/student_data_display.css"/>';

        $html .= '<div>
                    <div class="student-chart-block">
                        <select class="custom-select student-chart-cell" id="coursesLov">
                        <option disabled selected value>בחר/י קורס</option>';
        foreach ($user_courses as $current_course) {
            $current_course_html = '<option value="' . $current_course->id . '">' . $current_course->name . '</option>';

            // Filter courses that have ended or not yet began.
            switch ($current_course->semester) {
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
                    $html .= $current_course_html;
                    break;
            }  
        }

        foreach ($semesters_array as $semester => $semester_courses) {
            if (substr_count($semester_courses, '<option') > 0) {
                $semester_courses .= '</optgroup>';
                $html .= $semester_courses;
            }
        }

        $html .= '</select>';

        foreach ($user_courses as $current_course)  {
            $html .= '<input type="hidden" value="' . $current_course->grade . '" id="grade' . $current_course->id . '">';
        }

        $html .= '      <div class="ct-chart ct-square student-chart-cell" style="width:250px;height:250px"></div>
                    </div>
                    <div class="student-gauge-block">
                        <div class="student-gauge-title">ממוצע ציונים כללי</div>
                        <div id="overall-textfield" style="font-size: 26px;" class="overall-textfield reset"></div>
                        <canvas class="student-gauge-cell" id="overallGradeGauge" display="inline-block"></canvas>
                    </div>
                    <div class="student-gauge-block">
                        <div class="student-gauge-title">אחוז נוכחות</div>
                        <div id="attendance-textfield" style="font-size: 26px;" class="attendance-textfield reset"></div>
                        <canvas class="student-gauge-cell" id="attendanceGauge" display="inline-block"></canvas>
                    </div>
                  </div>';

        return $html;
    }

    public function bpm_get_student_data_js($overall_row) {
        $js  = "<script src='https://cdn.jsdelivr.net/chartist.js/latest/chartist.min.js'></script>
                <script src='https://my.bpm-music.com/blocks/bpm_student_data_display/chartist-plugin-tooltip.min.js'></script>
                <script src='https://my.bpm-music.com/blocks/bpm_student_data_display/gauge.min.js'></script>
                <script type='text/javascript'>
                var coursesLov = document.getElementById(\"coursesLov\");
                renderChart(0, 0);
                coursesLov.addEventListener('change', function() {  
                    if (this.value != '') {
                        var currentGrade = $('#grade' + this.value).val();
                        currentGrade = parseFloat(currentGrade).toFixed(1);
                        renderChart(currentGrade, this.value);
                    }
                });
                
                var GaugeOptions = {
                    angle: 0, // The span of the gauge arc
                    lineWidth: 0.30, // The line thickness
                    radiusScale: 0.50, // Relative radius
                    pointer: {
                        length: 0.28, // // Relative to gauge radius
                        strokeWidth: 0.066, // The thickness
                        color: '#000000' // Fill color
                    },
                    staticLabels: {
                      font: '10px sans-serif',  // Specifies font
                      labels: [0, 25, 50, 75, 100],
                      color: '#000000',  // Optional: Label text color
                      fractionDigits: 0  // Optional: Numerical precision. 0=round off.
                    },
                    limitMax: false,     // If false, max value increases automatically if value > maxValue
                    limitMin: false,     // If true, the min value of the gauge will be fixed
                    colorStart: '#EEEEEE',   // Colors
                    colorStop: '#00AAB5',    // just experiment with them
                    strokeColor: '#EEEEEE',  // to see which ones work best for you
                    generateGradient: true,
                    highDpiSupport: true,     // High resolution support  
                }

                var overallGaugeTarget = document.getElementById(\"overallGradeGauge\");
                var attendanceGaugeTarget = document.getElementById(\"attendanceGauge\");

                var overallGauge = new Gauge(overallGaugeTarget).setOptions(GaugeOptions);
                overallGauge.setTextField(document.getElementById(\"overall-textfield\"));
                overallGauge.maxValue = 100;
                overallGauge.minValue = 0;
                overallGauge.animationSpeed = 10;
                overallGauge.set(" . $overall_row->grade . ");

                GaugeOptions.colorStop = '#00AAB5';
                var attendanceGauge = new Gauge(attendanceGaugeTarget).setOptions(GaugeOptions);
                attendanceGauge.setTextField(document.getElementById(\"attendance-textfield\"));
                attendanceGauge.maxValue = 100;
                attendanceGauge.minValue = 0;
                attendanceGauge.animationSpeed = 10;
                attendanceGauge.set(" . $overall_row->attendance . ");
                
                function renderChart(grade, course) {
                    var url = 'https://my.bpm-music.com/blocks/bpm_student_data_display/utils.php';
                    var data = {
                        labels: ['ממוצע שלי', 'ממוצע כיתתי']
                    };
                    var options = {
                        high: 100,
                        low: 0,
                        seriesBarDistance: 50,
                        plugins: [
                            Chartist.plugins.tooltip()
                        ]
                    };
                    if (!grade) {
                        var chart = new Chartist.Bar('.ct-chart', data, options);
                    } else {
                        $.post(url, {function: 'get_course_average', courseid: course}).done(function(result) {
                            data.series = [[grade], [result]];
                            var chart = new Chartist.Bar('.ct-chart', data, options);
                            chart.on('created', function() {
                                $('.ct-series-a .ct-bar').attr('x1', 97);
                                $('.ct-series-a .ct-bar').attr('x2', 97);
                                $('.ct-series-b .ct-bar').attr('x1', 190);
                                $('.ct-series-b .ct-bar').attr('x2', 190);
                            });
                            chart.on('draw', function(data) {
                                if(data.type == 'bar') {
                                    data.element.animate({
                                        y2: {
                                            dur: '0.5s',
                                            from: data.y1,
                                            to: data.y2,
                                            easing: 'easeOutQuart'
                                        }
                                    });
                                }
                            });
                        });
                    }
                }

                </script>";
        return $js;
    }
    public function is_staff($user_id) {
        global $BPM_CFG, $CFG;
        
        $is_staff = false;
        foreach($BPM_CFG->STAFF_ROLES as $role_id) {
            if (user_has_role_assignment($user_id,$role_id)) {
                $is_staff = true;
            }
        }
        
        return $is_staff;
    }
}