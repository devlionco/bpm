<?php
defined('MOODLE_INTERNAL') || die;

class BPM_SF{
	
	const DUPLICATEUNIQID = 'duplicateuniqid';
	const MAILSENDFAIL = 'mailsendfail';
	const SYSTEMERROR = 'systemerror';
	const INVALIDEMAIL = 'invalidemail';
	const CREATECLASSFAIL = 'createclassfail';
	const UPDATECLASSFAIL = 'updateclassfail';
	const UPDATEORCREATECLASSDETAILSFAIL = 'updateorcreateclassdetailsfail';
	const UPDATEORCREATEOBJECTFAIL = 'updateorcreateobjectfail';
	const MISSINGEMAIL = 'missingemail';
	const CANCELNOTEXISTUSER = 'cancelnotexistuser';
	const CANCELENROLLEDFAIL = 'cancelenrolledfail';
	const SHOWCOURSEMODULESFAIL = 'showcoursemodulesfail';
	const SHOWCOURSEFAIL = 'showcoursefail';
	const NOTFORCEPASSWORDCHANGE = 'notforcepasswordchange';
	const ENROLLEDTONOTEXISTCOURSE = 'enrolledtonotexistcourse';
	const GRADUATE = 'Graduate';
	const PARTGRADUATE = 'Part Graduation';
	const NOTINSERT = 'notinsert';
	const EXCLUDED = 'Excluded';
	
	static function uniqidExists($uniqid) 
	{
		global $DB;
		return $DB->record_exists('user', array('username' => $uniqid));
	}
	
	static function sendMassage(array $array)
	{
	    
	    if( !function_exists('apache_request_headers') ) {
            ///
            function apache_request_headers() {
              $arh = array();
              $rx_http = '/\AHTTP_/';
              foreach($_SERVER as $key => $val) {
                if( preg_match($rx_http, $key) ) {
                  $arh_key = preg_replace($rx_http, '', $key);
                  $rx_matches = array();
                  // do some nasty string manipulations to restore the original letter case
                  // this should work in most cases
                  $rx_matches = explode('_', $arh_key);
                  if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                    foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                  }
                  $arh[$arh_key] = $val;
                }
              }
              return( $arh );
            }
            ///
            }
		$log_file = dirname(__FILE__) . '/BPM_SF_log.txt';
		file_put_contents($log_file, date('Y-m-d H:i:s') . "\n" . print_r(
			array_merge(php_sapi_name() == 'cli' ? array('CLI') : apache_request_headers(), array('POST' => $_POST), array('GET' => $_GET), $array)
		, 1) . "\n", FILE_APPEND);
		header('Content-Type: application/json');
		print_r(json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}
	
	static function sendError($errorconst, $data = null, $file = "?", $line = "?") 
	{
		$message_array = array("status" => "error", "exception" => $errorconst);
		$message_array['file'] = $file;
		$message_array['line'] = $line;
		
		if($data)
		{
			$message_array['data'] = $data;
		}
		
		self::sendMassage($message_array);
	}
	
	static function sendSuccess($errorconst, $data = null, $file = "?", $line = "?") 
	{
		$message_array = array("status" => "Success", "exception" => $errorconst);
		$message_array['file'] = $file;
		$message_array['line'] = $line;
		
		if($data)
		{
			$message_array['data'] = $data;
		}
		self::sendMassage($message_array);
	}
	
	static function generatePassword() {
		return substr(md5(uniqid('', true)), -5);
	}
	
	static function send_mail($user, $subject, $body) {
		global $CFG;
        $supportuser = core_user::get_support_user();
        $message = new stdClass();
        $message->component         = 'local_salesforce';
        $message->name              = 'user_enrol';
        $message->userfrom          = $supportuser;
        $message->userto            = $user;
        $message->subject           = $subject;
        $message->fullmessage       = strip_tags($body);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = nl2br($body);
        $message->smallmessage      = '';
        $message->notification      = 1;
        return(message_send($message));
	}

	static function enrol_to_course($course_id, $user_id, $role_id) {
		global $DB;
		if (!$enrol_manual = enrol_get_plugin('manual'))
		{
			return array("error" => "Enrollment failed - enrol_get_plugin");
		}
		
		$instances = enrol_get_instances($course_id, true);
		$contextcourse = context_course::instance($course_id);
		
		foreach($instances as $instance)
		{
			if($instance->enrol == 'manual') {		
				if($DB->record_exists('user_enrolments', array('userid' => $user_id, 'enrolid' => $instance->id)))
				{
					return true;
				}
				
				$enrol_manual->enrol_user($instance, $user_id, $role_id);
				return $DB->record_exists('user_enrolments', array('userid' => $user_id, 'enrolid' => $instance->id));
			}
		}
		return array("error" => "Enrollment failed - no manual instance for course\n" . print_r($course_id, 1));
		
		/* Deprecated {
			foreach($instances as $instance)
			{
				if($instance->enrol == 'manual') {
					$man_ins = $instance;
					
					if($DB->record_exists('role_assignments', array('userid' => $user_id, 'roleid' => $role_id, 'contextid' => $contextcourse->id)))
					{
						return false;
					}
					
					$enrol_manual->enrol_user($man_ins, $user_id, $role_id);
					return $DB->record_exists('role_assignments', array('userid' => $user_id, 'roleid' => $role_id, 'contextid' => $contextcourse->id));
				}
			}
			return array("error" => "Enrollment failed - no manual instance for course\n" . print_r($course_id, 1)); } */
	}
	
	static function unenrol_to_course($course_id, $user_id) {
		global $DB;
		if (!$enrol_manual = enrol_get_plugin('manual'))
		{
			return array("error" => "Unenrollment failed - enrol_get_plugin");
		}
		
		$instances = enrol_get_instances($course_id, true);
		
		foreach($instances as $instance)
		{
			if($instance->enrol == 'manual') {
				$man_ins = $instance;
				$enrol_manual->unenrol_user($man_ins, $user_id);
				return !$DB->record_exists('user_enrolments', array('userid' => $user_id));
			}
		}
		return array("error" => "Unenrollment failed - no manual instance for course\n" . print_r($course_id, 1));
	}
    
    static function add_salesforce_new_user($user_id, $password) {
        global $DB;
        
        $record = new stdClass();
        $record->userid = $user_id;
        $record->password = $password;
        $record->datecreated = time();
        $record->done = 0;
        $lastinsertid = $DB->insert_record('salesforce_new_user', $record, false);
        return $lastinsertid;
    }
    
    static function done_salesforce_new_user($user_id) {
        global $DB;
        $sql = "UPDATE {salesforce_new_user} SET password = '', done = 1 WHERE userid = ?";
        $DB->execute($sql, array($user_id));
    }
    
    
    static function get_salesforce_new_user($user_id) {
        global $DB;
        $new_user = $DB->get_record('salesforce_new_user', array('userid' => $user_id, 'done' => 0));
        return $new_user;
    }
    
    static function get_students($courseid) {
        global $CFG, $DB;
        $student=  $DB->get_record('role', array ('shortname' =>'student'));
        $context = context_course::instance($courseid);
        $allusers = get_enrolled_users($context);
        $users=array();
        foreach ($allusers as $value) {
                $roles = get_user_roles($context, $value->id)  ;
                $flag = 0;
                foreach ($roles as $role) {
                    if($role->roleid == $student->id) {
                        $flag=1;
                        break;
                    }
                }
                //if the user is student in the course
                if($flag == 1) {
                    $users[] = $value;
                }
        }
        return $users;
}
}