<?php
//hey 29.7.20
require_once __DIR__ . '/../../config.php';
require_once 'config.php';
require_once 'sfsql.class.php';
require_once 'bpm_sf_lib.php';
require_once($CFG->dirroot . '/lib/classes/user.php');
require_once($CFG->dirroot . "/course/lib.php");
require_once($CFG->dirroot . "/lib/enrollib.php");
require_once($CFG->dirroot . "/grade/querylib.php");
require_once($CFG->dirroot . "/lib/grade/grade_item.php");
require_once($CFG->dirroot . "/lib/grade/grade_grade.php");
require_once($CFG->dirroot . "/lib/gradelib.php");
require_once($CFG->dirroot . "/lib/datalib.php");
require_once($CFG->dirroot . "/mod/quiz/lib.php");
require_once($CFG->dirroot . "/mod/assign/lib.php");
require_once('branch_subscription.php');

$sfsql = new sfsql($wsdl, $userName, $password, $token);
echo "<pre>";
//Sending emails to users about their registered and cancellations courses
function send_mail_to_users(array $users) {
    global $DB;
    $strmanager = get_string_manager();
    
    foreach($users as $userid => $user_courses) {
            $maildata = $DB->get_record('user', array('id'=> $userid));
            $body = $strmanager->get_string('bpm_subscription_course_email_message_title', 'local_salesforce', $maildata, 'he');
            //Add registration
            if(isset($user_courses['register']))
            {
                    $body .= $strmanager->get_string('bpm_subscription_course_enrolled_email_message', 'local_salesforce', null, 'he');
                    $registers = $user_courses['register'];
                    foreach($registers as $key => $courseid)
                    {
                            $course = $DB->get_record('course', array('id' => $courseid));
                            $date = date('d M Y', $course->startdate);
                            $body .= $strmanager->get_string('course', 'local_salesforce', null, 'he') . ': ' . $course->shortname . ', ' . $strmanager->get_string('startdate', 'local_salesforce', null, 'he') . ': ' . $date . '<br>';
                    }
            }

            //Add cancellation
            if(isset($user_courses['cancel']))
            {
                    $body .= '<br>' . $strmanager->get_string('bpm_subscription_course_unenrolled_email_message', 'local_salesforce', null, 'he');
                    $cancels = $user_courses['cancel'];
                    foreach($cancels as $key => $courseid)
                    {
                            $course = $DB->get_record('course', array('id' => $courseid));
                            $body .= $strmanager->get_string('course', 'local_salesforce', null, 'he') . ': ' . $course->shortname . '<br>';
                    }
            }

            $body .= $strmanager->get_string('bpm_subscription_course_email_message_footer', 'local_salesforce', null, 'he');
            BPM_SF::send_mail($maildata,
                              $strmanager->get_string('bpm_subscription_course_email_subject', 'local_salesforce', null, 'he'),
                              $body);  
    }
}

//Insert and update master courses from SF to moodle
function upsert_classes_to_moodle()
{
    global $sfsql, $DB;

    $course_store_category = $DB->get_field_sql("SELECT value FROM {config_plugins} WHERE plugin = 'local_salesforce' AND name = 'coursestorecategory'");

    $courses = $sfsql->query("SELECT Product2.Id, 
                                     Product2.Name, 
                                     Product2.ProductCode, 
                                     Product2.Duration_in_hours__c, 
                                     Product2.Max_students__c, 
                                     Product2.Number_of_Meetings__c,
                                     Product2.CertificateName_En__c,
                                     Product2.CertificateName_He__c,
                                    Product2.Semester__c,
                                     Product2.certificate_eligibility_offset__c,
                                     Product2.course_type__c
                              FROM Product2
                              WHERE Product2.Parent_Product__c != null AND Product2.IsActive = true");

    while ($course = $courses->fetch_object())
    {
        $course_details = new stdClass();
        $course_details->coursefather = -1;
        $course_details->sfcodeproduct = $course->ProductCode;
        $course_details->several_days = $course->Duration_in_hours__c;

        $semester = ($course->Semester__c) ? $course->Semester__c : '';
		
		// Populate course_sf_data
        $course_sf_data = new stdClass();
        $course_sf_data->sf_id = $course->Id;
        $course_sf_data->english_name = $course->CertificateName_En__c;
        $course_sf_data->hebrew_name = $course->CertificateName_He__c;
        $course_sf_data->certificate_eligibility_offset = $course->certificate_eligibility_offset__c;
        $course_sf_data->course_type = $course->course_type__c;
		$course_sf_data->semester = $semester;
        
		
		//New master course
        if (!$DB->record_exists_sql("SELECT 1 FROM {course} WHERE idnumber = ?", [$course->Id]))
        {
            $result = create_course(
                (object)[
                        'category' => $course_store_category,
                        'shortname' => $course->Name,
                        'fullname' => $course->Name,
                        'idnumber' => $course->Id
                ]
            );

            if($result) {   

                // Populate course_sf_data course_id field and update the Moodle database
                $course_sf_data->course_id = $result->id;

                $DB->insert_record('sf_course_parent_data', $course_sf_data);

                $course_details->courseid = $result->id;

                $DB->insert_record('course_details', $course_details);
                
                $context = context_course::instance($result->id);
                $page = new moodle_page();
                $page->set_context($context);
                $bm = new block_manager($page);
                $bm->add_region('side-post');
                $bm->add_block('course_details', 'side-post', 1, null, 'course-view-*');
            } else {
                    BPM_SF::sendError(BPM_SF::CREATECLASSFAIL, $course->Id, __FILE__, __LINE__);
            }
        } else { //Exist  master course
            $course->Id = $DB->get_field_sql("SELECT idnumber FROM {course} WHERE idnumber = ? LIMIT 1", array($course->Id));

            $sql = 'UPDATE {course} set shortname = ?, fullname = ? WHERE idnumber = ?';

            if($DB->execute($sql, array($course->Name, $course->Name, $course->Id)))
            {
                $sql = 'SELECT cd.id cd_id , c.id
                        FROM {course} c 
                        LEFT JOIN {course_details} cd ON c.id = cd.courseid
                        WHERE c.idnumber = ? LIMIT 1';
                $course_id = $DB->get_record_sql($sql, array($course->Id));

                $course_details->courseid = $course_id->id;

                if(empty($course_id->cd_id))
                {
                    $update_or_create = $DB->insert_record('course_details', $course_details); 
                } else {
                    $course_details->id = $course_id->cd_id;
                    $update_or_create = $DB->update_record('course_details', $course_details);
                }
                
                // Populate course_sf_data course_id field
                $course_sf_data->course_id = $course_id->id;
                bpm_update_sf_course_parent_data($course_sf_data);

                if(!$update_or_create)
                {
                    BPM_SF::sendError(BPM_SF::UPDATEORCREATECLASSDETAILSFAIL, $course->Id, __FILE__, __LINE__);
                } else { // Update all object course the new details from master course
                    $sql = 'UPDATE {course_details} SET several_days = ? WHERE coursefather = ?';

                    if(!$DB->execute($sql, array($course_details->several_days, $course->Id)))
                    {
                            BPM_SF::sendError(BPM_SF::UPDATEORCREATECLASSDETAILSFAIL, $course_details->sfcodeproduct, __FILE__, __LINE__);
                    }
                }
            } else {
                BPM_SF::sendError(BPM_SF::UPDATECLASSFAIL, $course->Id, __FILE__, __LINE__);
            }
        }
    }
}

//Insert object course that create in moodle (by restor) to SF 
function insert_objects_to_sf() {
    global $sfsql, $DB;
    $sObjects = array();
    $moodleCourseIdsStr = "";
    $mdlCourseDataSql = 'SELECT c.id, c.shortname, c.startdate, cd.coursefather, c.enddate, c.timemodified
                         FROM {course} c, {course_details} cd
                         WHERE c.id = cd.courseid
                         AND (ISNULL(c.idnumber) OR c.idnumber = "") 
                         AND c.enddate >= CURRENT_DATE
                         AND ((c.timemodified <> cd.timemodified) OR (DATE_FORMAT(FROM_UNIXTIME(c.startdate), \'%Y-%m-%d\') = CURRENT_DATE))
                         LIMIT 100';

    $course_to_insert = $DB->get_records_sql($mdlCourseDataSql);
    
    foreach($course_to_insert as $course)
    {
        $moodleCourseIdsStr = $moodleCourseIdsStr . "'" . strval($course->id) . "', "; 
        if($course->shortname == '')
        {
            $course->shortname = ' ';
        }
        
        // Build the SalesForce "Course__c" object
        $sObject = new sObject();
        $sObject->type = 'Course__c';
        $startdate = date('c', $course->startdate);
        $sObject->fields =
        array(
            'Moodle_Course_Id__c' => $course->id,
            'Product__c' => $course->coursefather,              
            'Name' => $course->shortname,
            'Starts_On__c' => $startdate,
            'Course_Name__c' => $course->shortname
        );

        if(isset($course->enddate) && $course->enddate != 0)
        {
            $enddate_c = date('c', $course->enddate);
            $sObject->fields['End_Date__c'] = $enddate_c;
        }
        array_push($sObjects, $sObject);

        // Set the flag for future sync
        $time_modified = $course->timemodified;
        $id = $course->id;
        $time_modified_sql = "UPDATE {course_details}
                              SET    timemodified = $time_modified
                              WHERE  courseid = $id";
        $DB->execute($time_modified_sql);
    }
    
    // Remove ", " from end of string
    $moodleCourseIdsStr = substr($moodleCourseIdsStr, 0, -2);

    if(count($sObjects))
    {
        $upsertByCourseMoodleId = array();
        $upsertByCourseName = array();
        $moodleCourseList = fetch_course_moodle_ids($moodleCourseIdsStr);
        $upsertByCourseName = split_sObjects_array($sObjects, $upsertByCourseMoodleId, $moodleCourseList);

        // Fix unordered array keys
        $upsertByCourseMoodleId = remap_array_keys($upsertByCourseMoodleId);
        $upsertByCourseName = remap_array_keys($upsertByCourseName);
        
        if (count($upsertByCourseMoodleId)) {
            $updateResults = $sfsql->upsert("Moodle_Course_Id__c", $upsertByCourseMoodleId);
            
            foreach($updateResults as $result) {
				//BPM get sf fields from course
				$sql ="SELECT Moodle_Course_Id__c, class_schedule__c, Branch__c, recordings_folder_url__c FROM Course__c WHERE Id = '$result->id'";
				
				$sf_course = $sfsql->query($sql);
                
                $obj = $sf_course->fetch_object();
                
                if (isset($obj->class_schedule__c ) && count($obj->class_schedule__c) > 0 && $obj->class_schedule__c != NULL) {
                    bpm_update_schedule_url_in_db($obj->Moodle_Course_Id__c, $obj->class_schedule__c);
                }
                if (isset($obj->recordings_folder_url__c ) && count($obj->recordings_folder_url__c) > 0 && $obj->recordings_folder_url__c != NULL) {
                    bpm_update_class_recordings_folder_url_in_db($obj->Moodle_Course_Id__c, $obj->recordings_folder_url__c);
                }
				echo 'updating course details. MID: ' . $obj->Moodle_Course_Id__c . ', sfid: ' .  $result->id . PHP_EOL;
				if ($result->id) {
				    bpm_update_course_details_from_sf($obj->Moodle_Course_Id__c, $obj->Branch__c, $result->id);
				} else {
				    echo 'course ' . $obj->Moodle_Course_Id__c . 'no sf_id';
				}
				
                if(array_key_exists('errors', $result))
                {
                    BPM_SF::sendError(BPM_SF::UPDATEOBJECTFAIL, 'bpm_1: ' . $result, __FILE__, __LINE__);
                }
            }
        }

        if (count($upsertByCourseName)) {
            $upsertResults = $sfsql->upsert("Course_Name__c", $upsertByCourseName);
            foreach($upsertResults as $result)
            {
				//BPM get sf fields from course
				$sf_course = $sfsql->query("SELECT Moodle_Course_Id__c, class_schedule__c, Branch__c, recordings_folder_url__c FROM Course__c WHERE Id = '$result->id'");
                
                $obj = $sf_course->fetch_object();

                if (isset($obj->class_schedule__c ) && count($obj->class_schedule__c) > 0 && $obj->class_schedule__c != NULL) {
                    bpm_update_schedule_url_in_db($obj->Moodle_Course_Id__c, $obj->class_schedule__c);
				}
				if (isset($obj->recordings_folder_url__c ) && count($obj->recordings_folder_url__c) > 0 && $obj->recordings_folder_url__c != NULL) {
                    bpm_update_class_recordings_folder_url_in_db($obj->Moodle_Course_Id__c, $obj->recordings_folder_url__c);
                }
                echo 'updating course details. MID: ' . $obj->Moodle_Course_Id__c . ', sfid: ' .  $result->id . PHP_EOL;
				if ($result->id) {
				    bpm_update_course_details_from_sf($obj->Moodle_Course_Id__c, $obj->Branch__c, $result->id);
				} else {
				    echo 'course ' . $obj->Moodle_Course_Id__c . 'no sf_id';
				}
                if(array_key_exists('errors', $result))
                {
                    BPM_SF::sendError(BPM_SF::UPDATEORCREATEOBJECTFAIL, 'bpm_2:'  . $result, __FILE__, __LINE__);
                }
            }
        }
    } 
    return false;
}

/* Desc:
    Remap arrays with keys out of order. 
    Ex: array{4 => "value", 6 => "value2"} ---> array{0 => "value", 1 => "values2"} 
    */
function remap_array_keys($array)
{
    $newKey = 0;
    $newArr = [];
    foreach ($array as $key => $value){
        $newArr[$newKey] = $array[$key];
        $newKey++;
    }
    return $newArr;
}

function bpm_update_schedule_url_in_db($courseid, $url) {
	global $DB;

	$check_existing_sql = "SELECT COUNT(id) FROM mdl_bpm_course_schedule WHERE courseid = '$courseid' AND schedule_url != ''";
	
	$existing_record = $DB->count_records_sql($check_existing_sql);
	
	if ($existing_record == 0) { //insert
	    $new_row = new StdClass();
	    $new_row->courseid = $courseid;
	    $new_row->schedule_url = $url;
		//$insert_sql = "INSERT INTO mdl_bpm_course_schedule (courseid, schedule_url) VALUES ('$courseid', '$url')";
		
		$DB->insert_record('bpm_course_schedule', $new_row);
	} else { //update
		//$update_sql = "UPDATE mdl_bpm_course_schedule SET schedule_url = '$url' WHERE courseid = '$courseid'";
		$dataobject = new StdClass();
		$dataobject->id = $courseid;
		$dataobject->schedule_url = $url;
		
		$DB->update_record('bpm_course_schedule', $dataobject, $bulk=false);
	
	}
}
function bpm_update_class_recordings_folder_url_in_db($courseid, $url) {
	global $DB;

	$check_existing_sql = "SELECT id FROM mdl_bpm_course_schedule WHERE courseid = '$courseid' AND recordings_folder != ''";
	$existing_record = $DB->get_records_sql($check_existing_sql);
	var_dump($existing_record);
	
	if (!$existing_record) { //insert
	    $new_row = new StdClass();
	    $new_row->courseid = $courseid;
	    $new_row->recordings_folder = $url;
		$insert_sql = "INSERT INTO mdl_bpm_course_schedule (courseid, recordings_folder) VALUES ('$courseid', '$url')";
		
		$DB->insert_record('bpm_course_schedule', $new_row);
	} else { //update
	    echo 'updating recordings_folder_url in course ' . $courseid . PHP_EOL;
	    echo PHP_EOL . 'new url = ' . $url . PHP_EOL;
		//$update_sql = "UPDATE mdl_bpm_course_schedule SET recordings_folder = '$url' WHERE courseid = '$courseid'";
	    //echo $update_sql;
		foreach ($existing_record as $existing_record_fe) {
		    $recordid = $existing_record_fe->id;
		}
		$dataobject = new StdClass();
		$dataobject->id = $recordid;
		$dataobject->recordings_folder = $url;
		echo 'dataobject: ' .PHP_EOL;
		var_dump($dataobject);
		$DB->update_record('bpm_course_schedule', $dataobject, $bulk=false);
	
	}
}

function bpm_update_course_details_from_sf($courseid, $branch, $sf_id) {
	global $DB;
	// echo $courseid;
	// echo $branch;
	
	$update_sql = "UPDATE mdl_course_details SET branch = '$branch', sf_id = '$sf_id' WHERE courseid = $courseid";
	
	$DB->execute($update_sql);
}

/* Desc:
    Split the sObjects array into two arrays,
    one to be upserted using "moodle course id" and one to be upserted using "moodle course name" 
    */
function split_sObjects_array($objectsArray, &$upsertByCourseMoodleId, $filterArray)
{
    $upsertByCourseName = $objectsArray;

    foreach ($objectsArray as $key => $sObject) {
        foreach ($filterArray->queryResult->records as $course) {

            // Convert into an "sObject" and extract courseId
            $mdlCourseObject = new sObject($course);
            $mdlCourseObject->type = 'Course__c';
            $courseId = $mdlCourseObject->Moodle_Course_Id__c;

            // Remove from original array and push to a new array
            if ($sObject->fields['Moodle_Course_Id__c'] == $courseId) {
                unset($upsertByCourseName[$key]);
                array_push($upsertByCourseMoodleId, $sObject);
            }
        }
    }

    return $upsertByCourseName;
}

/* Desc:
    Get all courses that have moodle course id in SF using a comma seperated string of ids 
    */
function fetch_course_moodle_ids($courseIdsString)
{
    global $sfsql;
    $sfCourseDataSql = "SELECT Moodle_Course_Id__c FROM Course__c WHERE Moodle_Course_Id__c IN (" . $courseIdsString . ")";
    $test = $sfsql->query($sfCourseDataSql);
    return $test;
}

//enroll to courses in moodle from SF with creating users and sending emails
function upsert_enrollments_to_moodle() 
{
    global $sfsql, $DB, $CFG, $S_CFG;
    
    echo "<pre>";

    $user_send_mails = array();
    $person = 'Registration__c.Person_Account_Name__r';
    $course = 'Registration__c.Course__r';
    //$lastruntime = $DB->get_field('task_scheduled', 'lastruntime', array('classname' => '\local_salesforce\task\synchronise_data_salesforce'));
/*    $lastruntime = strtotime('-2 days', time());
    $lastruntime = date('c', $lastruntime);*/
    $student_role = $DB->get_field('role', 'id', array('shortname' => 'student'));
    $guest_student_role = $DB->get_field('role', 'id', array('shortname' => 'guest_student'));

    $enrollments  = $sfsql->query("SELECT Id,
                                          Course__c, 
                                          Person_Account_Name__c, 
                                          Status__c, 
                                          $person.LastName, 
                                          $person.FirstName,
                                          $person.EngName__c,
                                          $person.PersonEmail,  
                                          $person.ID__c, 
                                          $person.Work_Phone__c, 
                                          $person.Phone, 
                                          $person.BillingStreet, 
                                          $person.BillingCity, 
                                          $course.Id, 
                                          $course.Moodle_Course_Id__c, 
                                          Entitlement__c,
                                          $course.Name,
                                          delete_from_mdl__c,
                                          accessrule_override_cutoff_date__c,
										  $person.Balance__c
                                   FROM   Registration__c 
                                   WHERE  Registration__c.Sync_MDL__c = true
                                   AND    $course.Moodle_Course_Id__c != null");

    while ($enrollment = $enrollments->fetch_object())
    {
        
        $person = $enrollment->Person_Account_Name__r->fields;
        $courseid = $enrollment->Course__r->fields->Moodle_Course_Id__c;
        
        // echo 'courseid: ' . $courseid . PHP_EOL;
        if($course = get_course($courseid))
        {
            $contextcourse = context_course::instance($courseid);
        } else {
            BPM_SF::sendError(BPM_SF::ENROLLEDTONOTEXISTCOURSE);
        }
            // echo '$contextcourse: ' . $contextcourse->id . PHP_EOL;
        if(isset($person->PersonEmail) && isset($person->ID__c)) {
                
                $userid = $DB->get_field('user', 'id', array('username' => $person->ID__c));
                
				if($enrollment->Status__c == 'נרשם' || 
					$enrollment->Status__c == 'Registered' || 
					$enrollment->Entitlement__c == 'פטור') {   
                        //create new user
                        if(!$userid) {
                            $user = new stdClass();
                            $user->username = $person->ID__c;

                            if(PLATFORM == 'DEV') 
                            {
                                    $user->email = 'dev@bpm-music.com';
                            } else {
                                    $user->email = $person->PersonEmail;
                            }

                            if (BPM_SF::uniqidExists($user->username)) // we don't want moodle to jump if username already exists so we preempt it
                            {
                                    BPM_SF::sendError(BPM_SF::DUPLICATEUNIQID);
                                    continue;
                            }

                            if (!validate_email($user->email))
                            {
                                    BPM_SF::sendError(BPM_SF::INVALIDEMAIL);
                                    continue;
                            } 

                            $user->idnumber = $person->ID__c;
                            $user->firstname = $person->FirstName;
                            $user->lastname = $person->LastName;
                            $user->password = '1234567';
                            $user->country = 'IL';
                            $user->city = isset($person->BillingCity) ? $person->BillingCity : '';
                            $user->address = isset($person->BillingStreet) ? $person->BillingStreet : '';
                            $user->phone2 = isset($person->Phone) ? $person->Phone : '';
                            $user->phone1 = isset($person->Work_Phone__c) ? $person->Work_Phone__c : '';
                            $user->lang = 'he';
                            $newuser = create_user_record($user->username, $user->password, 'manual');

                            // Populate user_sf_data
                            $user_sf_data = new stdClass();
                            $user_sf_data->sf_id = $enrollment->Person_Account_Name__c;
                            $user_sf_data->english_name = $person->EngName__c;
                            $user_sf_data->hebrew_name = $person->FirstName . ' ' . $person->LastName;
							
							//sf_debt - used for certificate_eligibility
							if($person->Balance__c > 0) {
								$user_sf_data->debt = $person->Balance__c;
							}
							
                            $password = $user->password;

                            if($newuser) {
                                // Populate sf_data user_id and insert into the Moodle database
                                echo 'userid: ' . $user_sf_data->user_id . PHP_EOL;
                                $user_sf_data->user_id = $newuser->id;
                                if ($DB->record_exists('sf_user_data', array('user_id' => $newuser->id)) == false) {
                                    $DB->insert_record('sf_user_data', $user_sf_data);
                                    bpm_update_sf_account_user_id($newuser->id, $enrollment->Person_Account_Name__c);
                                }

                                //Forcing change password
                                $user->id = $userid = $newuser->id;
                                unset($user->password);
                                $userpreference = new stdClass();
                                $userpreference->userid = $userid;
                                $userpreference->name = 'auth_forcepasswordchange';
                                $userpreference->value = '1';

                                if(!$DB->insert_record('user_preferences', $userpreference))
                                {
                                        BPM_SF::sendError(BPM_SF::NOTFORCEPASSWORDCHANGE, null, __FILE__, __LINE__);
                                }

                                if(!$DB->update_record('user', $user))
                                {
                                        BPM_SF::sendError(BPM_SF::SYSTEMERROR, null, __FILE__, __LINE__);
                                        continue;
                                }

                                //Profile picture patch - BPM
                                require_once($CFG->dirlib . '/gdlib.php');
                                $file_url = $S_CFG->BPM_IMG_FLDR_URL_MDL . $user->email . '.jpg';
                                $user_icon_id = process_new_icon(context_user::instance($userid, MUST_EXIST), 'user', 'icon', 0, $file_url);
                                
                                if ($user_icon_id) {
                                    $DB->set_field('user', 'picture', $user_icon_id, array('id' => $userid));
                                }

                            } else {
                                BPM_SF::sendError(BPM_SF::SYSTEMERROR, null, __FILE__, __LINE__);
                                continue;
                            }

                            if(!BPM_SF::add_salesforce_new_user($userid, $password)) {
                                BPM_SF::sendError('fail add_salesforce_new_user', $userid, __FILE__, __LINE__);
                            } else {
                                BPM_SF::sendSuccess('moodleuserid', $newuser->id);
                            }
                            bpm_add_branch_forum_subscription($userid, $courseid);
                        } else {
                            echo 'userid: ' . $userid . PHP_EOL;
                            $user = new stdClass();
                            $user->id = $userid;
                            $user->firstname = $person->FirstName;
                            $user->lastname = $person->LastName;
                            $user->city = isset($person->BillingCity) ? $person->BillingCity : '';
                            $user->address = isset($person->BillingStreet) ? $person->BillingStreet : '';
                            $user->phone2 = isset($person->Phone) ? $person->Phone : '';
                            $user->phone1 = isset($person->Work_Phone__c) ? $person->Work_Phone__c : '';

                            // Populate user_sf_data fields
                            $user_sf_data = new stdClass();
                            $user_sf_data->user_id = $userid;
                            $user_sf_data->sf_id = $enrollment->Person_Account_Name__c;
                            $user_sf_data->english_name = $person->EngName__c;
                            $user_sf_data->hebrew_name = $person->FirstName . ' ' . $person->LastName;
                            
							//sf_debt - used for certificate_eligibility
							if($person->Balance__c > 0) {
								$user_sf_data->debt = $person->Balance__c;
							}
							
                            if (validate_email($person->PersonEmail))
                            {
                                if(PLATFORM == 'DEV') 
                                {
                                    $user->email = 'dev@bpm-music.com';
                                } else {
                                    $user->email = $person->PersonEmail;
                                }
                            } 
                            $DB->update_record('user', $user);
                            bpm_update_sf_user_data($user_sf_data);
                        }
                        bpm_add_branch_forum_subscription($userid, $courseid);
                        if(isset($courseid)) {
                            
                            if ($enrollment->Entitlement__c == 'פטור') {
								echo 'enroling ' . $userid . ' as guest to course ' . $courseid . PHP_EOL;
                                $enrolled = BPM_SF::enrol_to_course($courseid, $userid, $guest_student_role);
                            } else {
								echo 'enroling ' . $userid . ' as student to course ' . $courseid . PHP_EOL;
                                $enrolled = BPM_SF::enrol_to_course($courseid, $userid, $student_role);    
                            }
                            
                            if ($enrolled == true) {
								echo 'activating enrollment for user ' . $userid . ' in course ' . $courseid . PHP_EOL;
                                bpm_update_enrollment_to_active($userid, $courseid);
                            } else if (is_array($enrolled) && isset($enrolled['error'])) {
                                BPM_SF::sendError(BPM_SF::SYSTEMERROR, $enrolled['error'], __FILE__, __LINE__);
                            }  
                        }

                    //if (strpos($course->shortname, 'כללי') == false) {
                    bpm_insert_sf_enrollment_id_to_moodle($enrollment->Id, $userid, $courseid, $enrollment->Person_Account_Name__c, $enrollment->Entitlement__c, $enrollment->accessrule_override_cutoff_date__c);
                    bpm_update_sf_account_link($userid, $enrollment->Person_Account_Name__c);
                    //}

                } else if ($enrollment->delete_from_mdl__c == 'true') {
					echo 'removing user ' . $userid . ' from course ' . $courseid . PHP_EOL;
					 $unenrolled = BPM_SF::unenrol_to_course($courseid, $userid);

                    if ($unenrolled !== true && is_array($unenrolled) && isset($unenrolled['error']))
                    {
						BPM_SF::sendError(BPM_SF::SYSTEMERROR, $unenrolled['error'], __FILE__, __LINE__);
					}
				} else if($enrollment->Status__c == 'Cancelled') {
                    if(!$userid)
                    {
                            BPM_SF::sendError(BPM_SF::CANCELNOTEXISTUSER, $person->PersonEmail, __FILE__, __LINE__);
                            continue;
                    }

                    if(is_enrolled($contextcourse, $userid)) {
						
						//TODO determine wether to unenrol or suspend
                        $user_enrol_id_sql = "SELECT ue.id
                                              FROM {enrol} e
                                              JOIN {user_enrolments} ue ON e.id = ue.enrolid
                                              WHERE ue.userid = $userid
                                              AND   e.courseid = $courseid";

                        $user_enrol_id = $DB->get_field_sql($user_enrol_id_sql);
                        $time_modified = time();
                        $status_update_sql = "UPDATE {user_enrolments}
                                            SET status = 1, timemodified = $time_modified
                                            WHERE id = $user_enrol_id";
						echo 'suspending user ' . $userid . ' from course ' . $courseid . PHP_EOL;
                        $DB->execute($status_update_sql);

                        // $unenrolled = BPM_SF::unenrol_to_course($courseid, $userid);

                        // if ($unenrolled !== true && is_array($unenrolled) && isset($unenrolled['error']))
                        // {
                        //         BPM_SF::sendError(BPM_SF::SYSTEMERROR, $unenrolled['error'], __FILE__, __LINE__);
                        // } else {
                        //         if(!isset($user_send_mails[$userid]['cancel']))
                        //         {
                        //                 $user_send_mails[$userid]['cancel'] = array();
                        //         }

                        //         array_push($user_send_mails[$userid]['cancel'], $courseid);
                        // }
                    }
                }
        } else {
                BPM_SF::sendError(BPM_SF::MISSINGEMAIL, $enrollment->Person_Account_Name__c, __FILE__, __LINE__);
        }

        $sObjects = array();
        $sObject = new sObject();
        $sObject->type = 'Registration__c';
        $sObject->fields = array('Sync_MDL__c' => 0,
                                 'Id' => $enrollment->Id
                                ); 
        array_push($sObjects, $sObject);
        $result = $sfsql->update($sObjects);
    }

    if(count($user_send_mails))
    {
            send_mail_to_users($user_send_mails);
    }
}

//show course and activities in course startdate
function show_all_modules_course() {
    global $DB;
    
    $sql = 'SELECT id, startdate FROM {course}
            WHERE idnumber = ""';
    $courses = $DB->get_records_sql($sql);
    $today = strtotime('today');

    foreach($courses as $course)
    {
        if($course->startdate == $today)
        {
            // Deprecated
            // $sql = 'UPDATE {course_modules} SET visible = 1 WHERE course = ?';
            // $execute = $DB->execute($sql, array($course->id));
            
            // if(!$execute)
            // {
            //     BPM_SF::sendError(BPM_SF::SHOWCOURSEMODULESFAIL, $course->id, __FILE__, __LINE__);
            // }
            
            $execute = $DB->set_field('course', 'visible', 1, array('id' => $course->id));
            rebuild_course_cache($course->id);
            
            if(!$execute)
            {
                BPM_SF::sendError(BPM_SF::SHOWCOURSEFAIL, $course->id, __FILE__, __LINE__);
            }
        }
    }
}

/* function delete_all_courses()
{
    global $DB;
    
    $courses = $DB->get_records_sql("SELECT id FROM {course} WHERE category != 0");
    echo '<pre dir=ltr style=text-align:left>' . print_r( count($courses) , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
    foreach($courses as $course)
    {
        //echo '<pre dir=ltr style=text-align:left>' . print_r( $course->id , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
        delete_course($course->id);
    }
}
 */
 
function insert_status_to_sf($graduate_status) {
    global $DB, $sfsql;
    $user = core_user::get_user($graduate_status->userid, 'idnumber');
    $user = $user->idnumber;
    
    //insert to SF
    if(PLATFORM == 'DEV') {
        $sql = "SELECT Id FROM Registration__c WHERE Registration__c.Person_Account_Name__r.ID__c = '$user' AND Registration__c.Course__r.Moodle_Course_Id__c = " . $graduate_status->courseid;
    } else {
        $sql = "SELECT Id FROM Registration__c WHERE Registration__c.Person_Account_Name__r.ID__c = '$user' AND Registration__c.Course__r.Moodle_Course_Id__c = '" . strval($graduate_status->courseid) . "'";
    }
    
    $user_register = $sfsql->query($sql);
    
    if(isset($user_register->queryResult->records[0]->Id[0]))
    {
        $sObjects = array();
        $sObject = new sObject();
        $sObject->type = 'Registration__c';
        $sObject->fields =
        array(
            'Entitlement__c' => $graduate_status->status,
            'Id' => $user_register->queryResult->records[0]->Id[0]
        ); 
        array_push($sObjects, $sObject);
        $result = $sfsql->update($sObjects);
        
        if(array_key_exists('errors', $result[0]))
        {
            $graduate_status->status = BPM_SF::NOTINSERT;
        }
    } else {
        $graduate_status->status = BPM_SF::NOTINSERT;
    }

    //update status in DB
    if($id = $DB->get_field('graduate_status_in_sf', 'id', array('userid' => $graduate_status->userid, 'courseid' => $graduate_status->courseid)))
    {
        $graduate_status->id = $id;
        $DB->update_record('graduate_status_in_sf', $graduate_status);
        
    } else {
        $DB->insert_record('graduate_status_in_sf', $graduate_status);
    }
}
 
function check_student_status($courseid, $userid, $status) {
    
    global $DB, $sfsql, $USER, $PAGE;
    $USER = get_admin();
    $newstatus = BPM_SF::GRADUATE;
    
    if($attendance = get_coursemodules_in_course('attendance', $courseid))
    {
        $context = context_course::instance($courseid);
        $PAGE->set_context($context);
        $grades = grade_get_grades($courseid, 'mod', 'attendance', reset($attendance)->instance, $userid);

        if($grades->items[0]->grades[$userid]->grade < 70)
        {
            $newstatus = BPM_SF::EXCLUDED;
        }
    }
    
    if($newstatus == BPM_SF::GRADUATE)
    {
        $course_grades = grade_get_course_grades($courseid, $userid);

        if($course_grades->grades[$userid]->grade < $course_grades->gradepass )
        {
            $newstatus = BPM_SF::PARTGRADUATE;

            // Redundant
/*            if($results = core_completion_external::get_activities_completion_status($courseid, $userid))
            {
                $results = $results['statuses'];
                foreach($results as $result)
                {
                    if($result['state'] == 0 || $result['state'] == 3)
                    {
                        $newstatus = BPM_SF::PARTGRADUATE;
                    }                       
                }
            } else {
                $newstatus = BPM_SF::PARTGRADUATE;
            }
        } else {
            $newstatus = BPM_SF::PARTGRADUATE;*/
        }
    }
    
    //if have change
    if($newstatus != $status)
    {
        $graduate_status = new stdClass();
        $graduate_status->userid = $userid;
        $graduate_status->courseid = $courseid;
        $graduate_status->status = $newstatus;
        insert_status_to_sf($graduate_status);
    }
}

//check student status completion on end of course
function check_student_status_endcourse() {
    global $DB, $PAGE;
    $lastruntime = $DB->get_field('task_scheduled', 'lastruntime', array('classname' => '\local_salesforce\task\synchronise_data_salesforce'));
    $sql = "SELECT c.id
            FROM {course} c
            WHERE c.enddate = CURDATE()";
    $courses = $DB->get_records_sql($sql);
        
    foreach($courses as $course)
    {
        $context = context_course::instance($course->id);
        $students = get_role_users(5, $context);

        if($attendance = get_coursemodules_in_course('attendance', $course->id))
        {
            foreach($students as $student)
            {
                check_student_status($course->id, $student->id, BPM_SF::NOTINSERT);
            }
        }
    }
}

//check changes in student status completion course
function check_changes_in_student_status() {
    global $DB;
    
    $sql = 'SELECT gs.*
            FROM {graduate_status_in_sf} gs
            JOIN {course} c 
            ON gs.courseid = c.id
            WHERE gs.status != ? AND (c.enddate + INTERVAL 1 YEAR) >= CURDATE()';
    $students = $DB->get_records_sql($sql, array(BPM_SF::GRADUATE));
    
    foreach($students as $student)
    {
        check_student_status($student->courseid, $student->userid, $student->status);
    }
}

//send mails before course
function send_mails_before_start_course() {
    global $DB, $CFG;
    $sql = 'SELECT id FROM mdl_course WHERE idnumber = "" AND DATE(FROM_UNIXTIME(startdate)) > CURDATE() AND DATEDIFF(DATE(FROM_UNIXTIME(startdate)), CURDATE()) = 2';
    $courses = $DB->get_records_sql($sql);
    $strmanager = get_string_manager();
    
    foreach($courses as $course) {
        $user_send_mails = array();
        $students = BPM_SF::get_students($course->id);
        foreach ($students as $student) {
            if($new_user = BPM_SF::get_salesforce_new_user($student->id)) {
                $maildata = $student;
                $maildata->password = $new_user->password;
                $maildata->loginurl = $CFG->wwwroot . '/login/index.php';
                $mail_send = BPM_SF::send_mail($maildata,
                                               $strmanager->get_string('bpm_subscription_email_subject', 'local_salesforce', null, 'he'), 
                                               $strmanager->get_string('bpm_subscription_email_message', 'local_salesforce', $maildata, 'he')); 
               BPM_SF::done_salesforce_new_user($student->id);
            }
            
            if(!isset($user_send_mails[$student->id]['register'])){
                $user_send_mails[$student->id]['register'] = array();
            }
            array_push($user_send_mails[$student->id]['register'], $course->id);
        }
    }
    
    if(count($user_send_mails)) {
        send_mail_to_users($user_send_mails);
    }
}

if(isset($_GET['func']))
{
    if($_GET['func'] == '1')
    {
            upsert_classes_to_moodle();
    } else if($_GET['func'] == '2') {
            insert_objects_to_sf();
    } else if($_GET['func'] == '3') {
            upsert_enrollments_to_moodle();
    } else if($_GET['func'] == '4') {
            show_all_modules_course();
    } else if($_GET['func'] == '5') {
            delete_all_courses();
    } else if($_GET['func'] == '6') {
            check_changes_in_student_status();
    } else if($_GET['func'] == '7') {
            check_student_status_endcourse();
    } else if($_GET['func'] == '8') {
        send_mails_before_start_course();
    }
}

function bpm_update_sf_account_user_id($user_id, $account_sf_id) {
    global $sfsql;
    
    $sObjects = array();
    $sObject = new sObject();
    $sObject->type = 'Account';
    $sObject->fields =
    array('Id' => $account_sf_id,
          'Moodle_User_Id__c' => $user_id
    ); 
    array_push($sObjects, $sObject);
    $result = $sfsql->update($sObjects);
}

// Update enrollment status to active in the Moodle DB
function bpm_update_enrollment_to_active($user_id, $course_id) {
    global $DB;

    $sql = "UPDATE mdl_user_enrolments ue
            JOIN mdl_enrol e ON e.id = ue.enrolid
            SET ue.status = 0
            WHERE e.courseid = $course_id
            AND   ue.userid = $user_id";

    $DB->execute($sql);
}

// Update sf_enrollments table with a sf registraton id by user id and course id
function bpm_insert_sf_enrollment_id_to_moodle($enroll_sf_id, $userid, $courseid, $account_sf_id, $entitlement = NULL, $cutoff_date = NULL) {
    global $DB;

    echo PHP_EOL . 'adding registration id: ' . $enroll_sf_id . ' of user: ' . $userid . ' in course: ' . $courseid . ' to the DB' . PHP_EOL;

    $cutoff = ($cutoff_date != NULL) ? strtotime("tomorrow", strtotime($cutoff_date)) : $cutoff_date;
    // echo 'cutoff_date: ' . PHP_EOL;
    // var_dump($cutoff_date);
    // echo 'cutoff: ' . $cutoff . PHP_EOL;
    // var_dump($cutoff);
    $sql = "SELECT COUNT(id)
            FROM   mdl_sf_enrollments
            WHERE  sfid LIKE ?";

    $count_result = $DB->get_field_sql($sql, array($enroll_sf_id));
    $exemption = ($entitlement == 'פטור') ? 1 : 0;
    
    if ($count_result == 0) {
        $sf_enrollment_record = array(
            'courseid'         => $courseid,
            'userid'           => $userid,
            'sfid'             => $enroll_sf_id,
            'account_sfid'     => $account_sf_id,
            'absent_case_open' => 0,
            'completegrade'    => 0,
            'exempt'        => $exemption,
            'access_rule_override_cutoff' => $cutoff);
    
        $insert_result = $DB->insert_record('sf_enrollments', $sf_enrollment_record, false);

        if ($insert_result) {
            echo '<br>added ' . $enroll_sf_id . ' successfully<br>';
        }
    } else {
        // echo PHP_EOL . ' updating exemption status for reg ' . $enroll_sf_id . ' to ' . $exemption;
        $sql = "UPDATE mdl_sf_enrollments SET 
                exempt = $exemption";
                if ($cutoff != NULL) {
                    $sql .= ", access_rule_override_cutoff = $cutoff ";
                }
        $sql .= " WHERE sfid = '$enroll_sf_id'";
        echo $sql . PHP_EOL;
        $DB->execute($sql);
        echo '<br>enrol' . $enroll_sf_id . ' exists.';
		$contextid = context_course::instance($courseid);
		echo PHP_EOL . 'contextid: ' . PHP_EOL;
		
		$contextarr = (array) $contextid;
		$contextid = $contextarr[array_keys($contextarr)[0]];
// 		var_dump($contextid);
		$newrole = ($exemption == 0) ? 5 : 20; //student_role = 5, guest_student_role = 20
		bpm_change_role_in_course($userid, $courseid, $contextid, $newrole);
    }
}

function bpm_update_sf_account_link($userid, $account_sf_id) {
    global $DB;

    $sql = "SELECT COUNT(id)
            FROM   mdl_user_info_data
            WHERE  userid = ?
            AND    fieldid = 7";

    $count_result = $DB->get_field_sql($sql, array($userid));    

    if ($count_result == 0) {
        $sf_url = "<p><a target=\"_blank\" href=\"https://eu15.salesforce.com/" . $account_sf_id . "\"><img src=\"https://eu15.salesforce.com/favicon.ico\"></a></p>";
        $user_info_data_record = array(
            'fieldid' => 7,
            'userid'  => $userid,
            'data'    => $sf_url);

        $insert_result = $DB->insert_record('user_info_data', $user_info_data_record, false);
    }
}

function bpm_update_grades_and_attendance_in_sf() {
    bpm_update_complete_grade_statuses();

    $course_sObjects = bpm_get_attendance_for_course();
    
    $registration_sObjects = bpm_get_registration_objects_for_sf();

    bpm_update_array_to_sf($course_sObjects, true);
    bpm_update_array_to_sf($registration_sObjects);
}

function bpm_update_complete_grade_statuses() {
    global $DB;

    $sql = "SELECT se.id 
            FROM mdl_sf_enrollments se
            WHERE se.completegrade = 0
            AND   se.id NOT IN (SELECT se1.id
                                 FROM (mdl_grade_items gi, 
                                    mdl_sf_enrollments se1)
                                    LEFT JOIN mdl_grade_grades gg ON gi.id = gg.itemid AND gg.userid = se1.userid
                               		 WHERE gi.aggregationcoef > 0
                                    AND   se1.courseid = gi.courseid
                                    AND   gg.finalgrade IS NULL)";
    
    //was previously:
    /*SELECT se.id 
            FROM mdl_sf_enrollments se
            WHERE se.completegrade = 0
            AND   se.id NOT IN (SELECT se1.id
                                FROM mdl_grade_grades gg, 
                                     mdl_grade_items gi, 
                                     mdl_sf_enrollments se1
                                WHERE gi.id = gg.itemid
                                AND   gi.aggregationcoef > 0
                                AND   se1.courseid = gi.courseid
                                AND   se1.userid = gg.userid
                                AND   gg.finalgrade IS NULL)";*/

    $grade_records = $DB->get_records_sql($sql);
    
     echo 'grade_records: <br>';
     var_dump($grade_records);

    foreach ($grade_records as $current_record) {
        $update_sql = "UPDATE mdl_sf_enrollments se
                       SET se.completegrade = 1
                       WHERE se.id = $current_record->id";   

        $DB->execute($update_sql);               
    }
}

function bpm_get_attendance_for_course() {
    global $DB;

    $course_control_date = strtotime("-1 month");

    $sql = "SELECT gi.courseid, AVG(gg.finalgrade) as avg_grade
            FROM mdl_grade_items gi
            JOIN mdl_grade_grades gg
            ON   gg.itemid = gi.id
            JOIN mdl_course c
            ON   c.id = gi.courseid
            WHERE gi.itemmodule = 'attendance'
            AND   c.enddate >= $course_control_date
            GROUP BY gi.courseid
            HAVING avg_grade IS NOT NULL
            ORDER BY courseid ASC";
        
    $attendance_rows = $DB->get_records_sql($sql);

    //indexing by course id - gonna incorporate it in the following foreach based on $attendance_rows
    $attendance_sessions_sql = "SELECT c.id, count(asess.id) as sessions
                                FROM mdl_attendance_sessions asess, mdl_attendance a, mdl_course c 
                                WHERE asess.attendanceid = a.id
                                AND a.course = c.id
                                AND c.enddate >= $course_control_date
                                AND asess.sessdate < UNIX_TIMESTAMP(CURDATE())
                                GROUP BY c.id
                                ORDER BY c.id ASC";
    $session_counts = $DB->get_records_sql($attendance_sessions_sql);
    
    $course_sObjects = array();

    foreach ($attendance_rows as $row) {
        $course_sObject = new sObject();
        $course_sObject->type = 'Course__c';
        $course_sObject->fields = array(
            'Moodle_Course_Id__c' => $row->courseid,
            'attendance__c' => $row->avg_grade,
            'passed_sessions_amt__c' => $session_counts[$row->courseid]->sessions
        );

        array_push($course_sObjects, $course_sObject);
    }

    return $course_sObjects;
}

// Merge all grades and attendances that need to be updated in sf together
function bpm_get_registration_objects_for_sf() {
    $merged_objects = array();
    $attendance_objects = bpm_get_attendance_updatable_enrollments();
    $grade_objects = bpm_get_grade_updatable_enrollments();

    foreach ($attendance_objects as $attendance_key => $attendance_data) {
        foreach ($grade_objects as $grade_key => $grade_data) {
            if ($grade_data->fields['Id'] == $attendance_data->fields['Id']) {
                $merged_object = new sObject();
                $merged_object->type = 'Registration__c';
                $merged_object->fields = array_merge($attendance_data->fields, $grade_data->fields);
                unset($attendance_objects[$attendance_key]);
                unset($grade_objects[$grade_key]);
                array_push($merged_objects, $merged_object);
            }
        }
    }
    
    $merged_objects = array_merge($merged_objects, $attendance_objects);
    $merged_objects = array_merge($merged_objects,$grade_objects);
//   echo 'merged objects: ';
//   var_dump($merged_objects);
    return $merged_objects;
}

// Get all sf_enrollemtns_data records data where the grade has changed in the moodle grade_grades table
// and update the grade in the sf_enrollments table
function bpm_get_grade_updatable_enrollments() {
    global $DB;
    $grades_control_date = strtotime('-2 day');
    
    $grades_sql = "SELECT se.id, gg.finalgrade AS grade, se.sfid
                   FROM mdl_grade_grades gg, mdl_grade_items gi, mdl_sf_enrollments se
                   WHERE gi.id = gg.itemid
                   AND   se.courseid = gi.courseid
                   AND   se.userid = gg.userid
                   AND   gi.itemtype='course'
                   AND   gg.finalgrade IS NOT NULL
                   AND   (gg.finalgrade <> se.grade OR gg.timemodified > $grades_control_date)"; //OR se.courseid = 1134)";
    // echo $grades_sql;
    $grade_records = $DB->get_recordset_sql($grades_sql);
    // var_dump($grade_records);
    $enrollments_grades = array();

    foreach ($grade_records as $record) {
        $grade_object = new sObject();
        $grade_object->type = 'Registration__c';
        $grade_object->fields = array(
            'Id'       => $record->sfid,
            'Grade__c' => $record->grade,
        );

        $update_sql = "UPDATE {sf_enrollments}
                       SET    grade = $record->grade
                       WHERE  sfid = '$record->sfid'";
        // echo $update_sql;
        $DB->execute($update_sql);

        array_push($enrollments_grades, $grade_object);
    }

    return $enrollments_grades;
}

// Get all sf_enrollemtns_data records data where the attendance score has changed in the moodle grade_grades table
function bpm_get_attendance_updatable_enrollments() {
    global $DB;

    $attendance_sql = "SELECT DISTINCT se.id, gg.finalgrade AS attendance_score, se.sfid
                       FROM mdl_grade_items gi, mdl_grade_grades gg, mdl_sf_enrollments se, mdl_course c, mdl_attendance att, mdl_attendance_sessions asess
                       WHERE gi.itemmodule = 'attendance'
                       AND c.id = se.courseid
                       AND att.course = se.courseid
                       AND asess.attendanceid = att.id
                       AND (from_unixtime(asess.lasttaken) >= DATE_SUB(now(), INTERVAL 2 DAY) 
                       OR gg.finalgrade <> se.attendance)
                       AND gi.id = gg.itemid 
                       AND gi.courseid = se.courseid 
                       AND gg.userid = se.userid";
    $attendance_records = $DB->get_records_sql($attendance_sql);
    // echo '$attendance_records_count: ' . count($attendance_records) . PHP_EOL;
    $attendance_count_logs_sql = "SELECT se.id, se.sfid, se.userid, se.courseid, count(al.id) AS logscount
                                    FROM mdl_sf_enrollments se, mdl_course c, mdl_attendance att, mdl_attendance_sessions asess, mdl_attendance_log al
                                    WHERE c.id = se.courseid
                                    AND courseid IN (SELECT c2.id 
                                                     FROM mdl_course c2, mdl_attendance a2, mdl_attendance_sessions asess2
                                                     WHERE a2.course = c2.id
                                                     AND asess2.attendanceid = a2.id
                                                     AND from_unixtime(asess2.lasttaken) >= DATE_SUB(now(), INTERVAL 2 DAY)
                                                    )
                                    AND att.course = se.courseid
                                    AND asess.attendanceid = att.id
                                    AND al.studentid = se.userid
                                    AND al.sessionid = asess.id
                                    GROUP BY se.id";
    $attendance_logs_count = $DB->get_records_sql($attendance_count_logs_sql);
    
    //  echo '$attendance_logs_count: ' . count($attendance_logs_count) . PHP_EOL;
    // var_dump($attendance_logs_count);
    
    $enrollments_attendance = array();
    
    foreach ($attendance_records as $record) {
        // echo '$attendance_records:' . PHP_EOL;
        // var_dump($record);
        //  echo '$attendance_logs_count[$record->id]:' . PHP_EOL;
        //  var_dump($attendance_logs_count[$record->id]);
        // echo PHP_EOL;
        
        $attendance_object = new sObject();
        $attendance_object->type = 'Registration__c';
        $attendance_object->fields = array(
            'Id'            => $record->sfid,
            'attendance__c' => $record->attendance_score
            //'attendance_logs_count__c' => $attendance_logs_count[$record->id]->logscount
        );
        if ($attendance_logs_count[$record->id]) {
            $attendance_object->fields['attendance_logs_count__c'] = $attendance_logs_count[$record->id]->logscount;
        }
        
        if (!$record->attendance_score) {
            $record->attendance_score = 'NULL';
        } else {
            array_push($enrollments_attendance, $attendance_object);
        }
        $update_sql = "UPDATE {sf_enrollments}
                       SET    attendance = $record->attendance_score
                       WHERE  sfid = '$record->sfid'";
        //  echo $update_sql . PHP_EOL;
        $DB->execute($update_sql);

    }

    return $enrollments_attendance;
}

// Updates an array of sObjects to sf (splits if array larger than 200 records)
function bpm_update_array_to_sf($array, $is_course = false){
    global $sfsql;

    if (count($array) > 200) {
        $split_array = array_chunk($array, 150);
        foreach ($split_array as $small_array) {
            if ($is_course) {
                echo 'upserting stats to course ';
                $sfsql->upsert("Moodle_Course_Id__c", $small_array);
            } else {
                $sfsql->update($small_array);
            }
        }
    } else {
        if ($is_course) {
            $sfsql->upsert("Moodle_Course_Id__c", $array);
        } else {
            echo 'upserting reg ' . json_encode($array) . PHP_EOL;
            $sfsql->update($array);
        }
    }
}

// Update course parent sf data in the Moodle database
function bpm_update_sf_course_parent_data($data) {
    global $DB;

    $sql = "UPDATE mdl_sf_course_parent_data
            SET sf_id = \"$data->sf_id\", 
                english_name = \"$data->english_name\",
                hebrew_name = \"$data->hebrew_name\",
                semester = \"$data->semester\"
            WHERE course_id = $data->course_id";

    $DB->execute($sql);

}

// Update user sf data in the Moodle database
function bpm_update_sf_user_data($data) {
    global $DB;

    $sql = "UPDATE mdl_sf_user_data
            SET sf_id = \"$data->sf_id\", 
                english_name = \"$data->english_name\",
                hebrew_name = \"$data->hebrew_name\"
            WHERE user_id = $data->user_id";

    $DB->execute($sql);
}

// Fetch course details to sync from sf
function bpm_sync_courses_from_sf() {
    global $sfsql, $DB;

    $courses = $sfsql->query("SELECT Course__c.Id,
                                     Course__c.Moodle_Course_Id__c, 
                                     Course__c.Course_Duration__c,
                                     Course__c.recordings_folder_url__c
                              FROM Course__c
                              WHERE Course__c.Sync_MDL__c = true
                              AND   Course__c.Moodle_Course_Id__c != null");
                              
    while ($current_course = $courses->fetch_object()) {
        $course_hours = substr($current_course->Course_Duration__c, 0, -2);
        $sql = "UPDATE mdl_course_details
                SET several_days = $course_hours 
                WHERE courseid = $current_course->Moodle_Course_Id__c";
        // echo 'sql: ' . $sql . PHP_EOL;
        $DB->execute($sql);
        
        $course_m_id = $current_course->Moodle_Course_Id__c;
        $rec_folder = urlencode($current_course->recordings_folder_url__c);
        // echo '$current_course:';
        // var_dump($current_course);
        
        if ($rec_folder != '') {
            bpm_update_class_recordings_folder_url_in_db($current_course->Moodle_Course_Id__c, $rec_folder);
            /*$rec_sql = "UPDATE mdl_bpm_course_schedule
                SET recordings_folder = \"$rec_folder\"
                WHERE courseid = $course_m_id";*/
            // echo PHP_EOL . 'rec_folder_sql: '. PHP_EOL . $rec_sql . PHP_EOL;
            //$DB->execute($rec_sql);
            // echo 'hey' . PHP_EOL;
        }
        
      $sObjects = array();
        $sObject = new sObject();
        $sObject->type = 'Course__c';
        echo 'sObject: ' . PHP_EOL;
        var_dump($sObject);
        
        $sObject->fields = array('Id' => $current_course->Id,
                                 'Moodle_Course_Id__c' => $current_course->Moodle_Course_Id__c,
                                 'Sync_MDL__c' => 0); 
        array_push($sObjects, $sObject);
        
        $result = $sfsql->update($sObjects);
    }
}


function bpm_change_role_in_course($userid, $courseid, $contextid, $new_role_id) {
	global $DB;
	
	$ctx_lvl = 50; //course

	$existing_row = "SELECT id, roleid FROM mdl_role_assignments WHERE contextid = $contextid AND userid = $userid";
	echo 'existing_row_sql = ' . $existing_row;
	if ($record_to_update = $DB->get_records_sql($existing_row)) {
		echo '$record_to_update: ' . PHP_EOL;
		var_dump($record_to_update);
		
		$row_id = array_keys($record_to_update)[0];
	
		
		$update_sql = "UPDATE mdl_role_assignments SET roleid = $new_role_id WHERE id = $row_id";
		echo $update_sql . PHP_EOL;
		if (!$result = $DB->execute($update_sql)) {
			echo 'problem with switching roles in course ' . $courseid . ' - user ' . $userid . '. ' . PHP_EOL;
		} else {
			echo 'done it.';
		}
		
	} else {
		echo 'whoops';
	}
}

// // Get current grade for a user in a course
// function get_avg_grade_for_user($user_id, $course_id) {
//     global $DB;

//     $grade_sql = "SELECT gg.id, gg.finalgrade AS grade, se.sfid, se.attendance
//                   FROM mdl_grade_grades gg 
//                   JOIN mdl_grade_items gi 
//                   ON   gi.id=gg.itemid
//                   JOIN mdl_sf_enrollments se 
//                   ON   se.userid = gg.userid
//                   WHERE gi.itemtype='course'
//                   AND  gi.courseid = $course_id
//                   AND  gg.userid = $user_id";

//     $grade_records = $DB->get_record_sql($grade_sql);

//     $grade_object = new stdClass();
//     $grade_object->fields = array(
//         'sf_id' => $record->sfid,
//         'grade' => $record->grade,
//         'attendance' => $record->attendance
//     );

//     return $grade_object;
// }