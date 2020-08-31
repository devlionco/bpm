<?php

require_once('../../../config.php');
require('../vendor/autoload.php');

use mikehaertl\wkhtmlto\Pdf;
global $DB;

if (isset($_GET['id'])) {
	$content_id = $_GET['id'];
	if ($content_record = $DB->get_record('bpm_pdf_engine', array('id' => $content_id))) {

		// Create a new Pdf object with some global PDF options
		$pdf = new Pdf(array(
		    'no-outline',   // Make Chrome not complain
		    'margin-top'    => 0,
		    'margin-right'  => 0,
		    'margin-bottom' => 0,
		    'margin-left'   => 0,

		    // Default page options
		    'disable-smart-shrinking',
		    'binary' => __DIR__ . '/wkhtmltopdf'
		));
		$content_html = mb_convert_encoding($content_record->html, "UTF-8");
		
        if (isset($_GET['edit']) || isset($_GET['view'])) {
            $response = new StdClass();
            $response->html = $content_html;
            $response->name = mb_convert_encoding($content_record->name, "UTF-8");
            echo json_encode($response);;
        }
        else {
            /******************************************************\//
            **  to view as html: comment out the 2 header settings, //
            **  uncomment the echo after the headers,               //
            **  and comment out the if(!pdf->send) statement
            ******************************************************/
     		header('Content-Type: application/pdf; charset=utf-8');
     		header('Content-Disposition: attachment; filename="bpm_cert.pdf"');
    		
    		//echo $content_html;
    
    		file_put_contents('tempTemplate.html', $content_html);
    
    		// Add a page. To override above page defaults, you could add
    		// another $options array as second argument.
    		$pdf->addPage('tempTemplate.html');
    
    		if (!$pdf->send($content_record->name . '.pdf')) {
    		    throw new Exception('Could not create PDF: '.$pdf->getError());
    		}
		    unlink('tempTemplate.html');
        }
		
	}
} else if ((isset($_POST['html'])) && (isset($_POST['name']))) {
	$content_id;
	$content_html = $_POST['html'];
	$content_name = $_POST['name'];

	$content_record = new stdClass();
	$content_record->html = $content_html;
	$content_record->name = $content_name;

	$existing_record_sql = "SELECT *
							FROM mdl_bpm_pdf_engine
							WHERE name = \"$content_name\"";

	if ($existing_record = $DB->get_record_sql($existing_record_sql)) {
		$content_id = $existing_record->id;
		$content_record->id = $content_id;
		$content_record->name = $content_name;
		$DB->update_record('bpm_pdf_engine', $content_record);
	} else {
		$content_id = $DB->insert_record('bpm_pdf_engine', $content_record);
	}

    if (isset($_POST['cid'])) { //class schedule for syncing with salesforce
		$course_content = new StdClass();
		//$course_content->class_content__c = 'https://my.bpm-music.com/blocks/bpm_utils/pdf_engine/bpm_pdf_engine.php?id=' . $schedule_id;
		$course_schedule->class_schedule__c =  'https://my.bpm-music.com/Extra_content/class_schedule_form/view_existing_schedule.html?id=' . $content_id . 
		                                        '&view=1';
		update_sf_course_record($_POST['cid'], $course_schedule);
		echo json_encode('https://my.bpm-music.com/blocks/bpm_utils/pdf_engine/bpm_pdf_engine.php?id=' . $content_id);
    } else {
		echo json_encode('https://my.bpm-music.com/blocks/bpm_utils/pdf_engine/bpm_pdf_engine.php?id=' . $content_id);
    }
}


function update_sf_course_record($courseid, $incoming_data) {
    global $BPM_CFG;
    $sf_access_data = get_sf_auth_data();

	$url = $sf_access_data['instance_url'] . $BPM_CFG->SF_COURSE_URL . $courseid;
	$json_data = json_encode($incoming_data);
	$headers = array(
	 "Authorization: OAuth " . $sf_access_data['access_token'],
	   "Content-type: application/json"
	);
	$curl = curl_init($url);

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
	curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);

	$response = curl_exec($curl);
	$json_response = json_encode($response);
	//print_r($json_response);

}

/**
 * Retrieve Salesforce access token and instance (session) url
 *
 * @global stdClass $BPM_CFG Object containing config data
 *
 * @return stdClass Object containing the sf connection data
 */
function get_sf_auth_data() {
    global $BPM_CFG;
   
  $post_data = array(
		'grant_type'    => 'password',
		'client_id'     => $BPM_CFG->SF_OAUTH_KEY,
		'client_secret' => $BPM_CFG->SF_CLIENT_SECRET,
		'username'      => $BPM_CFG->SF_USER_NAME,
		'password'      => $BPM_CFG->SF_PASS
	);
	$headers = array(
	  'Content-type' => 'application/x-www-form-urlencoded;charset=UTF-8'
	);
    
	$curl = curl_init($BPM_CFG->SF_ACCESS_URL);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
	$response = curl_exec($curl);
	curl_close($curl);

  
    // Retrieve and parse response body
    $sf_response_data = json_decode($response, true);
    return $sf_response_data;
}
