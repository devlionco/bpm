<?php

// This file is part of Moodle - http://moodle.org/
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

/* SMS Notifier Block
 * SMS notifier is a one way SMS messaging block that allows managers, teachers and administrators to
 * send text messages to their student and teacher.
 * @package blocks
 * @author: Azmat Ullah, Talha Noor
 * @date: 17-Jul-2014
 */

class block_bpm_sms extends block_base {

    public function init() {
        $this->title = get_string('bpm_sms', 'block_bpm_sms');
    }

    public function get_content() {
        global $CFG, $USER, $COURSE;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text .= html_writer::link(new moodle_url('/blocks/bpm_sms/view.php', array('viewpage' => '2', 'c_id' => $COURSE->id)), get_string('bpm_sms_send', 'block_bpm_sms')) . '<br>';
        $this->content->text .= html_writer::link(new moodle_url('/blocks/bpm_sms/view.php', array('viewpage' => '3')), get_string('bpm_sms_template', 'block_bpm_sms')) . '<br>';
        return $this->content;
    }

    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function instance_allow_config() {
        return true;
    }

}
