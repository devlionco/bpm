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
class ptogoWebApiToken {
    private $token;

    /*
     * @param: URL, this is the URL starting at action=
     * @param: Key, the secret key
     * @return: string containing the token.
    */
    public function create( $url, $key ) {
        // Both parameters have values.
        if ($url && $key) {
            // Get the part of the url to create the token.
            $tmpurl = substr($url,strpos($url, 'action=') );
            // Create the token and assign it to the private variable.
            $this->token = strtoupper(hash_hmac('sha256', $tmpurl, $key) );
            return $this->token; // Return the token.
        } else {
            return 'ERROR: Both parameters have to be filled in at ptogoWebApiToken::create'; // TODO: Multilang.
        }
    }

    /*
     * @param: URL, this is the URL starting at action=
     * @param: Key, the secret key
     * @return: string containing the token.
    */
    public function update( $url, $key ) {
        // Call the create function internally.
        self::create($url,$key);
    }

    /*
     * return: void
    */
    public function clear() {
        $this->token = null;
    }
}

class ptogoWebApiQuery {
    private $query;
    public function __construct($query,$db) {
        $this->query = $query;
        $this->db = $db;
    }

    public function doesAlreadyExist() {
        $tmpresult = $this->db->count_records('ptogo_query', array('query' => $this->query));
        return ($tmpresult==0) ? false : true;
    }

    public function getQueryId($query) {
        $tmpresult = $this->db->get_field('ptogo_query', 'id', array('query' => $query), IGNORE_MULTIPLE);
        return (int) $tmpresult;
    }

    public function getQueryById($id) {
        if(is_integer($id)) {
            $tmpresult = $this->db->get_field('ptogo_query', 'query', array('id' => $id), IGNORE_MULTIPLE);
        }
            $result = $tmpresult;
        return $result;
    }

    public function setQuery($query) {
        $this->query = $query;
    }

    public function getQuery() {
        return $this->query;
    }
}

class ptogoWebApi {
    private $params = array();
    private $server_url, $group, $expired, $key, $lang, $size, $page, $action;

    public function __construct($server, $group, $expired, $key, $action) {
        $this->server_url = $server . '/p2g/plugins/Catalogue.aspx';
        $this->group = $group;
        $this->expired = $expired;
        $this->key = $key;
        $this->action = $action;
        $this->lang = 'en'; // TODO: I don't think we want this.
    }

    public function addParams( $keys = array(), $values = array() ) {
        $iterations = sizeof($keys);
        try{
            for( $i=0; $i < $iterations; $i++ ) {
                $this->params[ $keys [ $i ] ] = $values[$i];
            }
            return true;
        } catch( Exception $e) {
            return false;
        }
    }

    public function getParams( $keys = array() ) {
        $tmparray = array();
        $iterations = sizeof($keys);
        try {
            for( $i = 0; $i < $iterations; $i++) {
                $tmparray[ $keys[ $i ] ] = $this->params[ $keys[ $i ] ];
            }
            return $tmparray;
        } catch( Exception $e) {
            return array();
        }

    }

    public function updateParams( $keys = array(), $values = array() ) {
        $iterations = sizeof($keys);
        try {
            for( $i = 0; $i < $iterations; $i++) {
                $this->params[ $keys[ $i ] ] = $values [ $i ];
            }
            return true;
        } catch( Exception $e ) {
            return false;
        }
    }

    public function removeParams( $keys = array() ) {
        try {
            $iterations = sizeof($keys);
            for( $i = 0; $i < $iterations; $i++ ) {
                unset($this->params[ $keys[ $i ] ] );
            }
            return true;
        } catch( Exception $e) {
            return false;
        }
    }

    public function makeRequest( ptogoWebApiToken $token, ptogoWebApiQuery $query ) {
        $UTC = new DateTimeZone("CET");
        $date = new DateTime('NOW', $UTC);
        $ts = $date->format('Y-m-d\TH:i:s');
        $interval = 'PT' . $this->expired . 'H';
        $date->add(new DateInterval($interval));
        $expired = $date->format('Y-m-d\TH:i:s');

        // TODO: Remove at some point.
        ////////////////////////////////////////////
        /// Area to test api calls. ////////////////
        ////////////////////////////////////////////
        $testing = 0;
        if ($testing) {
        // $this->server_url.= '?action=list&query=latest';
        // $this->server_url.= '?action=mediadetails&query=dfS1PG';
        // $this->server_url.= '?action=advancedsearch&query=id+like+dfS1PG';
        //$this->server_url.= '?action=advancedsearch&query=description+like+a';
        $this->server_url.= '?action=createasset&user=admin';
        // $this->server_url.= '?action=search&query=cmc0Ye';
        $this->server_url .= '&group=' . $this->group . '&ts=' . $ts. '&expired=' .$expired;

        }
        if (!$testing) {
        ////////////////////////////////////////////




        $this->server_url.= '?action=' .  $this->action . '&query=' . str_replace(' ', '+', $query->getQuery() ) . '&group=' . $this->group . '&ts=' . $ts. '&expired=' .$expired;
        if($this->action === 'list')
        {
            $this->server_url.= '&page=' . $this->page;
        }
        // Additional parameters for upload.
        if($this->action === 'createasset')
        {
            $this->server_url.= '&user=admin&mediatype=video';
        }
        if( $this->size ) {
            $this->server_url.= '&size=' . $this->size;
        }
        ////////////////////////////////////////////
        }
        ////////////////////////////////////////////

        $this->server_url.= '&token=' . $token->create($this->server_url, $this->key) . '&lang=' . $this->lang;
        // TODO: Remove at some point.
        if ($testing) {
            echo "<a href=". $this->server_url ." target='_blank'>testapicall</a>"; // TODO: Debug for testing API calls.
        }
        $response = file_get_contents($this->server_url);
        return $response;
    }
}
