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
 * Used to handle the popup windows for teachers requiring due date input for assignments
 * As well as adding teacher notices for mid tests/projects
 *
 * @author  Ben Laor, BPM
 * @package local/teachernotice
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teachernotice;

defined('MOODLE_INTERNAL') || die();

require_once('utils.php');

use plugin_renderer_base;

class notice extends plugin_renderer_base {

    /** @var int The notice id */
    private $id;

    /** @var int The course moodle id */
    private $course_id;

    /** @var int The assignment moodle id */
    private $assignment_id;

    /** @var int The notice type */
    private $type;

    /** @var stdClass The user class object */
    private $teacher_data;

    /** @var string The course name */
    private $course_name;

    /** @var int Times the notice was sent */
    private $count;

    /** @var array A list of properties that is allowed for each notice object. */
    private $properties = array(
        'id',
        'course_id',
        'assignment_id',
        'type',
        'teacher_data',
        'course_name',
        'count');

    function __construct($id,
                         $course_id, 
                         $assignment_id, 
                         $type, 
                         $teacher_data, 
                         $course_name, 
                         $count = null) {
        $this->id = $id;
        $this->course_id = $course_id;
        $this->assignment_id = $assignment_id;
        $this->type = $type;
        $this->teacher_data = $teacher_data;
        $this->course_name = $course_name;
        $this->count = $count;
    }

    /**
     * Handle popups for teacher when entering a course, following up with adding teacher notice records
     * for mid test/project, and end test/project
     *
     * @param  \core\event\base $event.
     *
     * @global stdClass         $PAGE   Moodle page API.
     * @global stdClass         $CFG    Moodle configuration API.
     *
     */
    public static function bpm_show_popup($event) {
        global $PAGE ,$CFG;

        $css_url = $CFG->wwwroot . '/local/teachernotice/js/theme/jquery-ui.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $css_url . '" />';

        //html_writer::start_tag('div', array('id' => 'dialog', 'title' => 'Dialog Title')) . 'I\'m a dialog' . html_writer::end_tag('div');

        $event_data = $event->get_data();

        $course_id = $event_data['courseid'];
        $user_id = $event_data['userid'];

        if ($course_id != 1) {
            $teacher_id = \utils::bpm_get_teacher_id($course_id);
            $mid_course_date = \utils::bpm_get_course_mid_date($course_id);

            if ($teacher_id == $user_id) {
                $assignments_status = \utils::bpm_get_course_assignments_status($course_id, $mid_course_date);
                $PAGE->requires->js_call_amd('local_teachernotice/popupteacher', 'generatePopup', [$assignments_status]);
            }
        }
    }

    /**
     * Magic getter method.
     *
     * @param string $prop Name of property to get.
     *
     * @return mixed
     * @throws \coding_exception
     */
    public function __get($prop) {
        if (in_array($prop, $this->properties)) {
            return $this->$prop;
        }
        throw new \coding_exception("Invalid property $prop specified");
    }

    /**
     * Magic setter method.
     *
     * @param  string $prop  Name of property to set.
     * @param  mixed  $value Value to assign to the property.
     *
     * @return mixed
     * @throws \coding_exception
     */
    public function __set($prop, $value) {
        if (in_array($prop, $this->properties)) {
            return $this->$prop = $value;
        }
        throw new \coding_exception("Invalid property $prop specified");
    }

    /**
     * Magic method to check if property is set.
     *
     * @param  string $prop Name of property to check.
     * @return bool
     * @throws \coding_exception
     */
    public function __isset($prop) {
        if (in_array($prop, $this->properties)) {
            return isset($this->$prop);
        }
        throw new \coding_exception("Invalid property $prop specified");
    }
}