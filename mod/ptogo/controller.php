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
 * This file is called by ajax.
 *
 * @package   mod_ptogo
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('locallib.php');

switch($_POST['type']) {
    case "filter":
        if(isset($_POST['key']) && isset($_POST['server']) && isset($_POST['group'])) {
            echo  ptogo_get_filters($_POST['server'], str_replace(" ", "+",urldecode($_POST['key'])), $_POST['group']);
        }
        break;
    case "preview":
        if(isset($_POST['hasrepository']) && $_POST['hasrepository'] === "true") {
            if (isset($_POST['repository']) && isset($_POST['addQuery'])) {
                $repository = $_POST['repository'];
                $addQuery = urldecode($_POST['addQuery']);
                echo ptogo_get_selection($repository, $addQuery);
            }
        } else {
            if (isset($_POST['key']) && isset($_POST['server']) && isset($_POST['group']) && isset($_POST['addQuery'])) {
                $addQuery = urldecode($_POST['addQuery']);
                echo ptogo_get_selection_by_server($_POST['server'], str_replace(" ", "+",urldecode($_POST['key'])), $_POST['group'], $addQuery);
            }
        }
        break;
    case "getRepositoryData":
        if(isset($_POST['repository_id'])) {
            echo ptogo_get_repository_data((int) $_POST['repository_id']);
        }
        break;
    default:
        break;
}
