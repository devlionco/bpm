<?php
if (!isset($_GET['exec_mode']) || (isset($_GET['exec_mode']) && $_GET['exec_mode'] != 'user')) {
    define('CLI_SCRIPT', true);     
};

echo 'hello, i ran ';
require_once __DIR__ . '/../../config.php';
require_once 'config.php';

clearJobListingsTable();
try {
    getClassfieldsRecordsToMoodleDB();
} catch(Exception $e) {
    bpm_email_dev($e->getMessage(), 'getClassfieldsRecordsToMoodleDB');
    
}


function getClassfieldsRecordsToMoodleDB() {
    global $DB, $BPM_CFG;
    $sf_access_data = get_sf_auth_data();
    
    $sql = "SELECT Id, Project_Name__c,JobDescription__c, RequiredEquipment__c, 
            ActType__c, Location__c, StartDT__c, EndDT__c, PaymentAmount__c, Account__c 
            FROM Alumni_Promotion__c 
            WHERE 
			(RecordtypeId = '0121p000000oScmAAE' OR RecordtypeId = '0121p000000ThMEAA0')
            AND showUpInClassfieldsBoard__c = true AND (StartDT__c >= LAST_N_DAYS:60 OR CreatedDate >= LAST_N_DAYS:60)";
    
    $url = $sf_access_data['instance_url'] . $BPM_CFG->SF_QUERY_URL . urlencode($sql);
    
    $headers = array(
        "Authorization: OAuth " . $sf_access_data['access_token']
    );
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $json_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($json_response);
    $dataObject = array();
    foreach($response->records as $record) {
        $newRow = new stdClass();
        $newRow->sf_id = $record->Id;
        $newRow->name = $record->Project_Name__c;
        $newRow->job_description = $record->JobDescription__c;
        $newRow->required_equipment = $record->RequiredEquipment__c;
        $newRow->act_type = $record->ActType__c;
        $newRow->location = $record->Location__c;
        $newRow->start = strtotime($record->StartDT__c);
        $newRow->end = strtotime($record->EndDT__c);
        $newRow->payment_amount = $record->PaymentAmount__c;
        $newRow->account = $record->Account__c;
        array_push($dataObject, $newRow);
    }
    $DB->insert_records('bpm_job_listings', $dataObject);
}

function clearJobListingsTable() {
    global $DB;
    
    $sql = "DELETE FROM mdl_bpm_job_listings";
    $DB->execute($sql);
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


/*----------------------------------------------------------------------------*/
/*   Send email to dev                                                        */
/*----------------------------------------------------------------------------*/
function bpm_email_dev($error, $job_name) {
    global $BPM_CFG;
    $to =  'dev@bpm-music.com';
    $subject = "JOB FAILED - " . $job_name;

    $message = "<div dir='rtl'>";

    $message .= "execption - " . "<b>" . $error . "</b>" . "<br /><br />";
    $message .= "</div>";
    $message .= $BPM_CFG->BPM_EMAIL_FOOTER;
    
    // Additional header information, needed for file transfer and Hebrew support
    $headers  = "To: " . $to . " <" . $to . ">"     . "\r\n";
    $headers = "From: BPM SERVER <server@bpm-music.com>"     . "\r\n";
    $headers .= "MIME-Version: 1.0"                           . "\r\n";
    $headers .= "Content-Type: text/html; charset=iso-8859-1" . "\r\n";

    $response = mail($to, $subject, $message, $headers);
    
}

?>