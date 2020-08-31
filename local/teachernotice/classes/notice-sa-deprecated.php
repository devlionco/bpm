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
 * Declare and define the "notice" class and all its functions
 *
 * @author  Ben Laor, BPM
 * @package local/teachernotice
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teachernotice;

defined('MOODLE_INTERNAL') || die();

class notice {
   
    /**
     * Add a new record to the teacher_notice table
     * @param \core\event\base $event
     */
    public static function bpm_add_record(\core\event\base $event) {
        global $DB, $PAGE ,$CFG;

        $swalurl = dirname(__DIR__) . '/js/sweetalert2.min.js';
        $day_lov = "<div class=\"form-group  fitem  \">
    <label class=\"col-form-label sr-only\" for=\"id_allowsubmissionsfromdate_day\">
        יום        
    </label>
    <span data-fieldtype=\"select\">
    <select class=\"custom-select\" id=\"id_allowsubmissionsfromdate_day\">
        <option value=\"1\">1</option>
        <option value=\"2\">2</option>
    </select>
    </span>
    <div class=\"form-control-feedback\" style=\"display: none;\">
        
    </div>
</div>";

        echo("<link rel='stylesheet' type='text/css' href=\"https://cdn.jsdelivr.net/sweetalert2/latest/sweetalert2.min.css\" />");
        echo("<script src=\"https://cdn.jsdelivr.net/sweetalert2/6.4.4/sweetalert2.min.js\"</script>");
        echo("<script language='javascript' type='text/javascript'> 

swal({
                title: 'האם מתוכנן להיום מבחן אמצע?',
                type: 'question',
                showCancelButton: true,
                cancelButtonColor: '#00aab4',
                confirmButtonColor: '#f9b000',
                cancelButtonText: 'כן',
                confirmButtonText: 'לא'
            }).then(function (result) {
swal({
                    title: 'נא להזין תאריך בו יתבצע המבחן',
                    html: '\"<select>
                            <option value=\"1\"></option>
                            <option value=\"2\"></option>
                            <option value=\"3\"></option>
                           </select> + 
                           <select>
                            <option value=\"4\"></option>
                            <option value=\"5\"></option>
                            <option value=\"6\"></option>
                           </select>\"' ,
                    type: 'warning',
                    confirmButtonColor: '#00aab4',
                    confirmButtonText: 'אישור',
                    inputValidator: function (value) {
                        return new Promise(function (resolve, reject) {
                            if (value) {
                                resolve()
                            } else {
                                reject(' לא הוזן תאריך')
                            }
                        })
                      }
                }).then(function (result) {
                    swal({
                        type: 'success',
                        html: 'התאריך בו יתבצע מבחן אמצע הוא: ' + result,
                        confirmButtonColor: '#00aab4',
                        confirmButtonText: 'אישור'
                    })
                })
            })
</script>");





        //$PAGE->requires->js('js/sweetalert2.min.js');
        //$PAGE->requires->js_init_call('swal',
                                      //array('swal', 'hello BPM'));




        // $course_id = $event->courseid;
        // $user_id = $event->userid;

        // if ($course_id != 1) {
        //     $teacher_id = $DB->get_field_sql("SELECT usr.id 
        //                                       FROM {course} crs
        //                                       JOIN {context} ctx         ON crs.id = ctx.instanceid
        //                                       JOIN {role_assignments} ra ON ctx.id = ra.contextid
        //                                       JOIN {user} usr            ON usr.id = ra.userid
        //                                       JOIN {role} r              ON r.id = ra.roleid
        //                                       WHERE crs.id    = ?
        //                                       AND r.archetype = 'editingteacher'", 
        //                                       array($course_id));

        //     if ($teacher_id == $user_id) {
        //         $assignments = $DB->get_records_sql("SELECT a.name
        //                                              FROM {assign} a
        //                                              WHERE a.course = ?
        //                                              AND a.name IN ('מבחן אמצע', 'מבחן סיום', 'פרויקט סיום', 'פרויקט אמצע')",
        //                                              array($course_id));

        //         foreach ($assignments as $name) {
        //             switch ($name)
        //                 case 'מבחן אמצע':
        //                     // TODO: Popup mid-term project
        //                     break;
        //                 case 'פרויקט אמצע':
        //                     // TODO: Popup mid-term test
        //                     break;
        //                 default:
        //                     break;
        //         }
        //     }
        // }
    }
}