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
 * Form definition for changing repository entries.
 *
 * @package   mod_ptogo
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die(); // TODO: check.

global $CFG;
require_once("$CFG->libdir/formslib.php");

class mod_ptogo_repository_change extends moodleform {

    function definition() {
        global $DB;

        $mform = $this->_form;

        $mform->addElement('text','title', get_string('repository_title', 'ptogo'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('add_title', 'ptogo'), 'required', null, 'client');

        $mform->addElement('text','description', get_string('repository_description', 'ptogo'));
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('text', 'serverurl', get_string('serverurl','ptogo'));
        $mform->setType('serverurl', PARAM_TEXT);
        $mform->setDefault('serverurl', 'https://serverurl/');
        $mform->addRule('serverurl', get_string('add_url', 'ptogo'), 'required', null, 'client');

        $mform->addElement('text', 'secretkey', get_string('secretkey','ptogo'));
        $mform->setType('secretkey', PARAM_TEXT);
        $mform->setDefault('secretkey', 'enter key');
        $mform->addRule('secretkey', get_string('add_key', 'ptogo'), 'required', null, 'client');

        $mform->addElement('text', 'ptogo_group', get_string('group', 'ptogo'));
        $mform->setType('ptogo_group', PARAM_NOTAGS);

        $mform->addElement('text', 'expiration', get_string('expiration', 'ptogo'));
        $mform->setType('expiration', PARAM_INT);
        $mform->setDefault('expiration',5);

        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'baseQuery', ' ');
        $mform->setType('baseQuery', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('repository_edit', 'ptogo'));
    }

    function validation($data, $files) {
        $errors = array();
        parent::validation($data, $files);
        if($data) {
            // Check if expiration > 0.
            if ($data['expiration'] < 1) {
                $errors['expiration'] = 'Must be bigger than 0.'; // TODO: Multilang.
            }
            // Check if serverurl starts correctly.
            $https = substr($data['serverurl'], 0, 8) == 'https://';
            $http = substr($data['serverurl'], 0, 7) == 'http://';
            // Note to future self, this is AND bc. we check for neither A nor B. duh.
            if (!$https && !$http) {
                $errors['serverurl'] = get_string('serverurl_needsprefix', 'ptogo');
            }
        }
        return $errors;
    }
}
