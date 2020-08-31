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
 * Generate edit form for teachers.
 *
 * @package   mod_ptogo
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../config.php');
require_once("$CFG->dirroot/course/moodleform_mod.php");

class mod_ptogo_mod_form extends moodleform_mod {
    function __construct($data, $section, $cm, $course)
    {
        parent::__construct($data, $section, $cm, $course);
        global $PAGE, $DB;

        // TODO: Consolidate the requires and put them in amd. This is hacky and bad form.
        $PAGE->requires->js(new moodle_url('/mod/ptogo/queryWizard/queryWizard.js'), true);
        $PAGE->requires->css(new moodle_url('/mod/ptogo/queryWizard/queryWizard.css'), true);
        $PAGE->requires->js(new moodle_url('/mod/ptogo/preview/preview.js'), true);
        $PAGE->requires->css(new moodle_url('/mod/ptogo/preview/preview.css'), true);
        $PAGE->set_url(new moodle_url('/course/modedit.php', array('update' => $data->coursemodule, 'return' => 1,  'ptogoid' => $data->id)));
    }

    function definition() {

        global $CFG, $DB, $PAGE;
        if(isset($this->current->update) && is_int($this->current->update)) {
            $ptogoinstance = $DB->get_record('ptogo', array('id'=>$this->current->id));
            $repository = $DB->get_record('ptogo_repository', array('id'=>$this->current->repository_id));
            $items = $DB->get_records_menu('ptogo_items', array('video_id' => $ptogoinstance->video_id), $sort='', $fields='id, item_id', $limitfrom=0, $limitnum=0) ;

            // Create querystring.
            /*if (strlen($repository->basequery) > 0 && strlen($ptogoinstance->additional_query) > 0) {
                $query = $repository->basequery . " AND " . $ptogoinstance->additional_query;
            } elseif (strlen($repository->basequery) > 1) {
                $query = $repository->basequery;
            } elseif (strlen($ptogoinstance->additional_query) > 1) {
            */
            if (strlen($ptogoinstance->additional_query) > 1) {
                $query = $ptogoinstance->additional_query;
            } else {
                $query = 'Title like '; // Get rid of the undefined state when no query is added.
            }

            // Build jsdata to hand over to js.
            $jsdata = new stdClass();
            $jsdata->query = $query;
            $jsdata->items = array();
            if(is_array($items)) {
                foreach($items as $item) {
                    $jsdata->items[] = $item;
                }
            }
            $jsdata = json_encode($jsdata);
        }

        $mform = $this->_form;

        $mform->addElement('text','title', get_string('page_title', 'ptogo'));
        $mform->setType('title', PARAM_TEXT);

        $this->standard_intro_elements(get_string('page_description', 'ptogo'));

        // TODO: Ask if this setting is needed.
        // $mform->addElement('advcheckbox', 'showinlisting', 'Display', 'Display vidoes on course listing site.', array(), array(0, 1));
        // $mform->addElement('advcheckbox', 'showselected', 'Selection', 'Only show selected videos.', array(), array(0, 1));

        // Choose displayform list or single item.
        // TODO: Can we just assume it is a list if nothing is selected.
        $radioarray = array();
        $radioarray[] =& $mform->createElement('radio', 'listitem', '', get_string('show_dynamic', 'ptogo'), 'list');
        $radioarray[] =& $mform->createElement('radio', 'listitem', '', get_string('show_selected', 'ptogo'), 'item');
        $mform->addGroup($radioarray, 'radioar', get_string('displayform', 'ptogo'), array(' '), false);
        $mform->addHelpButton('radioar', 'displayform', 'ptogo');

        // If we have a value in video_id we assume you had an item selected.
        if(isset($ptogoinstance) && $ptogoinstance->video_id != null) $listitem = 'item';
        else $listitem = 'list';
        $mform->setDefault('listitem', $listitem);

        // Form to check which repository should be used.
        $repositories = $DB->get_records('ptogo_repository');

        // Fails if no repos are set up. Write error message.
        if(empty($repositories)) {
            Global $COURSE;
            $url = new moodle_url('/course/view.php', array('id' => $COURSE->id ));
            $message = "There are no Repositories set up, please contact your Administrator."; // TODO: Multilang.
            redirect($url, $message, null, \core\output\notification::NOTIFY_ERROR);
        }

        // In case no repo is set just use the first.
        if (!isset($repository)) $repository = $repositories[key($repositories)];

        foreach($repositories as $repo) $options[$repo->id] = $repo->title;

        $selectrepo = $mform->addElement('select', 'repository_id', get_string('repository', 'ptogo'), $options);
        if(isset($ptogoinstance->repository_id)) $selectrepo->setSelected($ptogoinstance->repository_id);
        $mform->addHelpButton('repository_id', 'repository', 'ptogo');

        // Hidden form fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $serverurl = $mform->addElement('hidden', 'serverurl', $repository->serverurl);
        $mform->setType('serverurl', PARAM_TEXT);
        $serverurl->updateAttributes(array('id' => 'id_serverurl'));

        $secretkey = $mform->addElement('hidden', 'secretkey', $repository->secretkey);
        $mform->setType('secretkey', PARAM_TEXT);
        $secretkey->updateAttributes(array('id' => 'id_secretkey'));

        $group = $mform->addElement('hidden', 'ptogo_group', $repository->ptogo_group);
        $mform->setType('ptogo_group', PARAM_TEXT);
        $group->updateAttributes(array('id' => 'id_ptogo_group'));

        // Stores ; separated list of videos for "selected videos".
        $item = $mform->addElement('hidden', 'ptogo_item_id', ''); // Can be text for testing.
        $mform->setType('ptogo_item_id', PARAM_TEXT);
        $item->updateAttributes(array('id' => 'id_ptogo_item_id'));

        // If video_id is defined in my ptogo database.
        if(isset($ptogoinstance) && $ptogoinstance->video_id != null && is_array($items)) {
            $items = implode (";", $items); // Make a representation that the form gets.
            $mform->setDefault('ptogo_item_id', $items);
        }

        // Query building form and form fields.
        $aquery = $mform->addElement('hidden', 'baseQuery', ' '); // TODO: This has a false name, should be additional_query all the way. Can be text for testing.
        $mform->setType('baseQuery', PARAM_TEXT);
        $aquery->updateAttributes(array('id' => 'id_baseQuery'));
        if(isset($ptogoinstance->additional_query)) $mform->setDefault('baseQuery', $ptogoinstance->additional_query);

        $mform->addElement('static', 'description', get_string('filterquery', 'ptogo'),'<div id="queryWizardContainer"></div>');
        $mform->addHelpButton('description', 'filterquery', 'ptogo');

        // Add container to hold the preview, no label needed here.
        $mform->addElement('html', '<div id="preview"></div>');

        // On change we call ajax.
        // TODO: Use amd to call js in next iteration.
        $mform->addElement('html', '<script>
        // When we change the repository we want to play with.
        document.getElementById("id_repository_id").addEventListener("change", function() {
            console.log("we changed repository");
            var id = document.getElementById("id_repository_id").options[document.getElementById("id_repository_id").selectedIndex].value;
            var xmlHttp = new XMLHttpRequest();
            xmlHttp.open("POST", "' . new moodle_url('/mod/ptogo/') . '" + "controller.php");
            xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlHttp.send("type=getRepositoryData&repository_id=" + id);

            xmlHttp.onload = function() {
                changeRepositoryData(xmlHttp.responseText);
            }
        });

        function changeRepositoryData(data) {
            var object = JSON.parse(data);
            var server = document.getElementById("id_serverurl");
            var key = document.getElementById("id_secretkey");
            var group = document.getElementById("id_ptogo_group");

            server.value = object.serverurl;
            key.value = object.secretkey;
            group.value = object.ptogo_group;
            new queryWizard("id_baseQuery", "' . get_string("query_add", "ptogo") . '", "' . new moodle_url("/mod/ptogo/") . '");
        }

        // Here the searchicon gets its functionality.
        function attachNewEvent() {
            document.getElementById("search").addEventListener("click", function() {
                console.log("Clicked search."); //DEBUG
                var preview =  new itemPreview("id_ptogo_item_id", "' . get_string("item_add", "ptogo") . '", "' . new moodle_url("/mod/ptogo/") . '", true);
            });
        }

        var qw = new queryWizard("id_baseQuery", "' . get_string("query_add", "ptogo") . '", "' . new moodle_url("/mod/ptogo/") . '");
        </script>');

        /*
        // Amd doesn't work as expected.
        // TODO: Simplify and streamline this implementation.
        // On change we call ajax.
        $PAGE->requires->js_amd_inline("require([], function() {
            // When we change the repository we want to play with.
            document.getElementById('id_repository_id').addEventListener('change', function() {
                console.log('we changed repository'); // TODO: delete when debugged.
                var id = document.getElementById('id_repository_id').options[document.getElementById('id_repository_id').selectedIndex].value;
                var xmlHttp = new XMLHttpRequest();
                xmlHttp.open('POST', '" . new moodle_url('/mod/ptogo/') . "' + 'controller.php');
                xmlHttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xmlHttp.send('type=getRepositoryData&repository_id=' + id);

                xmlHttp.onload = function() {
                    changeRepositoryData(xmlHttp.responseText);
                }
            });

            // Here the searchicon gets its functionality.
            function attachNewEvent() {
                alert('we loaded this');
                document.getElementById('search').addEventListener('click', function() {
                    var preview =  new itemPreview('id_ptogo_item_id', '" . get_string("item_add", "ptogo") . "', '" . new moodle_url("/mod/ptogo/") . "', true);
                });
            }

            function changeRepositoryData(data) {
                var object = JSON.parse(data);
                var server = document.getElementById('id_serverurl');
                var key = document.getElementById('id_secretkey');
                var group = document.getElementById('id_ptogo_group');

                server.value = object.serverurl;
                key.value = object.secretkey;
                group.value = object.ptogo_group;
                new queryWizard('id_baseQuery', '" . get_string("query_add", "ptogo") . "', '" . new moodle_url("/mod/ptogo/") . "');
            }

            var qw = new queryWizard('id_baseQuery', '" . get_string("query_add", "ptogo") . "', '" . new moodle_url("/mod/ptogo/") . "');
        });
        ");
        */

        if(isset($this->current->update) && is_int($this->current->update)) {

            // Scripts that are called onload if edit.
            // TODO: Use amd to call js in next iteration.
            $mform->addElement('html', '<script>
                window.onload = function() {
                    var preview =  new itemPreview("id_ptogo_item_id", "' . get_string("item_add", "ptogo") . '", "' . new moodle_url("/mod/ptogo/") . '", true, ' . $jsdata . ');

                    var query = JSON.parse(JSON.stringify(' . $jsdata . ')).query;
                    var queryParts = query.split(" AND ");

                    for(var i=1,j=queryParts.length;i<j;i++) {
                        qw.addQuery();
                    }

                    for(var i=0,j=queryParts.length;i<j;i++) {
                        var tmpQuery = queryParts[i].split(" ");
                            setIndex(document.getElementById("subject"+i), tmpQuery[0]);
                            setIndex(document.getElementById("filter"+i), tmpQuery[1]);
                        if(document.getElementById("value"+i).tagName === "INPUT") {
                            document.getElementById("value"+i).value = tmpQuery[2];
                        } else {
                            setIndex(document.getElementById("value"+i), tmpQuery[2]);
                        }
                    }

                    function setIndex(obj, value) {
                        var options = obj.options;
                        for(var i=0,j=options.length;i<j;i++) {
                            if(options[i].value == value) {
                                obj.selectedIndex = i;
                                return;
                            }
                        }
                    }
                }
            </script>');
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons(true);
    }

    function validation($data, $files) {
        $errors = array();
        parent::validation($data, $files);
        if(!is_numeric($data['repository_id'])) {
            $errors['repository_id'] = get_string('repository_notnumeric', 'ptogo');
        }
        return $errors;
    }
}
