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

/**
 * Form definition for listing all repositories in the database with edit buttons.
 *
 * @package   mod_ptogo
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die(); // TODO: check.
global $CFG;
require_once("$CFG->dirroot/lib/formslib.php");

class mod_ptogo_repository_list extends moodleform
{
    private $repositories;

    public function __construct($repositories) {
        $this->repositories = $repositories;
        parent::__construct();
    }

    function definition() {
        $mform = $this->_form;

        global $OUTPUT;

        if (is_array($this->repositories) && count($this->repositories) > 0) {
            $table = new html_table();
            $table->head = array(get_string('repository_title', 'ptogo'), get_string('repository_description', 'ptogo'),
                get_string('repository_actions', 'ptogo'));

            foreach ($this->repositories as $repository) {
                $cells = array();
                $cells[] = new \html_table_cell($repository->title);
                $cells[] = new \html_table_cell($repository->description);
                $actionscell = $OUTPUT->action_icon(new moodle_url('/mod/ptogo/repository/change.php',
                        array('repository' => $repository->id, 'action' => 'edit')),
                        new pix_icon('t/editstring', get_string('actionedit', 'ptogo')));
                $actionscell .= " ";
                // Add delete icon with confirm action.
                // TODO: Multilang.
                $actionscell .= $OUTPUT->action_link(new moodle_url('/mod/ptogo/repository/change.php',
                        array('repository' => $repository->id, 'action' => 'delete')),
                        new pix_icon('t/delete', get_string('actiondelete', 'ptogo')),
                        new confirm_action(get_string('repository_confirm_del', 'ptogo')));

                $cells[] = $actionscell;
                $row = new html_table_row();
                $row->cells = $cells;
                $table->data[] = $row;
            }
            $mform->addElement('html', html_writer::table($table));
        } else {
            $mform->addElement('html',  '<p>' . get_string('norepositories', 'ptogo') . '</p>');
        }
        $mform->addElement('html','<a href=' . new moodle_url('/mod/ptogo/repository/change.php') . '><input type="button" id="id_addrepository" value="' . get_string('repository_add' , 'ptogo') . '"></a>');
    }
}
