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
 * @package   mod_ptogo
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once('classes/class-ptogo-web-api.php');

// Builds html output after api call.
// $format used to determine expected output: list, embed
function ptogo_process_response($id, $instance, $format = 'list')
{
    global $DB;

    $ptogoinstance = $DB->get_record('ptogo', array('id'=>$instance));
    $repository = $DB->get_record('ptogo_repository', array('id'=>$ptogoinstance->repository_id));

    $html = '';
    $html .= '<h1>' . $ptogoinstance->title .'</h1>';
    $html .= '<p>' . $ptogoinstance->intro . '</p>';

    $query = $repository->basequery;
    if(!empty($ptogoinstance->additional_query)) {
        $query.= ' AND ' . $ptogoinstance->additional_query;
    }

    // If video_id is set we show a specific set of videos.
    if($ptogoinstance->video_id != null) {
        $items = $DB->get_records_menu('ptogo_items', array('video_id' => $ptogoinstance->video_id), 'id', 'id, item_id');
    }

    $ptogoQuery = new ptogoWebApiQuery($query, $DB);
    $ptogoToken = new ptogoWebApiToken();
    $ptogoAPI = new ptogoWebApi($repository->serverurl, $repository->ptogo_group, $repository->expiration,
        $repository->secretkey, 'advancedsearch');

    // TODO: Catch timeouts.
    $response = json_decode($ptogoAPI->makeRequest($ptogoToken,$ptogoQuery));

    if(isset($response->error) && $response->error !== "") {
        return "Error: " . $response->error;
    }

    $shortlist = 3; // If we have more videos we don't show them in the course view.
    foreach($response->response as $video) {
        // Either video_id is null and we show everything or we show every video in items.
        if ($ptogoinstance->video_id == null || in_array($video->id, $items)) {
            // Handle the different formats we can offer.
            if ($format == 'list') {
                $html .= '
                <div class="displayItem">
                    <div class="displayItemBlock">
                        <a href="' . $video->playbackUrl . '" target="_blank">
                        <img src="' . $video->thumbnailUrl . '"></a>
                    </div>
                    <div class="displayItemBlock">
                        <div class="metaData">
                            <span class="Title">' . $video->title . '</span><br>
                            <span class="Contributor">' . $video->contributors . '</span><br>
                            <span class="Publishdate">' .  processDate($video->date) . '</span>
                        </div>
                    </div>
                </div>';
            } else if ($format == 'embed' && $shortlist > 0) {
                // According to P2G embed does not work at all.
                //$html .=  "<embed width='500px' src='" . $video->embedUrl . "'>";
                $html .= '<div style="padding-top: 15px; padding-bottom: 0px; width: 100%" class="mediaplugin mediaplugin_youtube">';
                $html .= '<iframe src="' . $video->playbackUrl . '&autoplay=false" allowfullscreen="allowfullscreen" style="width: 1000px; height:500px;"></iframe>';
                $html .= '</div>';
                $shortlist--; // Decrement to keep track of videos shown.
            }
        }
    }
    return $html;
}

// Quick fix to get the date.
function processDate($datestr) {
    $dateparts = explode('T', $datestr);
    return $dateparts[1] . " " . $dateparts[0];
}

// Used in api calls by controller.php
function ptogo_get_filters($server, $key, $group) {
    global $DB;
    $token = new ptogoWebApiToken();
    $query = new ptogoWebApiQuery('search', $DB);
    if(substr($server,-1,1) === "/") $server = substr($server,0, strlen($server)-1);
    $expiration = 3; // Random amount of time.
    $ptogoAPI = new ptogoWebApi($server,$group, $expiration, $key, "info");
    $result = $ptogoAPI->makeRequest($token, $query);
    return $result;
}

function ptogo_get_repository_data($id) {
    global $DB;
    $data = $DB->get_record('ptogo_repository', array('id' => $id));
    $response = new stdClass();
    $response->serverurl = $data->serverurl;
    $response->secretkey = $data->secretkey;
    $response->ptogo_group = $data->ptogo_group;
    return json_encode($response);
}

function ptogo_get_selection($repository, $addquery) {
    global $DB;
    $repositorydata = $DB->get_record('ptogo_repository', array('id'=>(int) $repository));
    $basequery = $repositorydata->basequery;
    $query = $basequery . " AND " . $addquery;
    $token = new ptogoWebApiToken();
    $query = new ptogoWebApiQuery($query, $DB);
    $expiration = $repositorydata->expiration;
    $group = $repositorydata->ptogo_group;
    $server = $repositorydata->serverurl;
    if(substr($server,-1,1) === "/") $server = substr($server,0, strlen($server)-1);
    $ptogoAPI = new ptogoWebApi($server, $group, $expiration, $repositorydata->secretkey,"advancedsearch");
    $result = $ptogoAPI->makeRequest($token, $query);
    return $result;
}

function ptogo_get_selection_by_server($server,$key,$group,$addquery) {
    global $DB;
    $token = new ptogoWebApiToken();
    $query = new ptogoWebApiQuery($addquery, $DB);
    $expiration = 3; // Random amount of time.
    if(substr($server,-1,1) === "/") $server = substr($server, 0, strlen($server)-1);
    $ptogoAPI = new ptogoWebApi($server, $group, $expiration, $key,"advancedsearch");
    $result = $ptogoAPI->makeRequest($token, $query);
    return $result;
}
