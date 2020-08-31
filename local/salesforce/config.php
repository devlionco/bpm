<?php
require_once __DIR__ . '/../../config.php';
define('PLATFORM', preg_match('~/moodlegit/~', __FILE__) ? 'DEV' : 'PROD');
global $DB;
$sf_settings = $DB->get_records_sql("SELECT name, value FROM {config_plugins} WHERE plugin = 'local_salesforce'");

if (PLATFORM == 'DEV')
{
	$wsdl = __DIR__ . '/soapclient/test.partner.wsdl.xml';
} else {
	$wsdl = __DIR__ . '/soapclient/partner.wsdl.xml';
}

$userName = $sf_settings['sfusername']->value;
$password = $sf_settings['sfpassword']->value;
$token = $sf_settings['sftoken']->value;

/* BPM non-openapp additions - preparation for move to REST*/

//Sync configuration object
global $S_CFG;

$S_CFG = new stdClass();

// Moodle user id for system messages
$S_CFG->BPM_BOT_ID = '739'; // Production bot id
//$S_CFG->BPM_BOT_ID = '609'; // Test bot id

// Moodle user id for the student service department
$S_CFG->BPM_SSD_ID = '121'; // Same id in both test and production environments

//general forums for each branch
$S_CFG->branches = array();
$S_CFG->branches['Haifa'] = new StdClass();
$S_CFG->branches['Haifa']->NEWSFORUM_ID = 1825;
$S_CFG->branches['Tel_Aviv'] = new StdClass();
$S_CFG->branches['Tel_Aviv']->NEWSFORUM_ID = 1824;
$S_CFG->branches['Online'] = new StdClass();
$S_CFG->branches['Online']->NEWSFORUM_ID = 2627;


$S_CFG->BPM_SF_OAUTH_KEY   		= '3MVG9Rd3qC6oMalW9GpkCq9MrJzk0GBpCgI7tR9_rCh55bN9GYp_rh.Zt8U_QwWaLQKUcbRXNFk9bmchrOqK0';
$S_CFG->BPM_SF_USER_SECRET 		= '2097469368649063425';
$S_CFG->BPM_SF_USER_NAME   		= 'aviv@bpm-music.com';
$S_CFG->BPM_SF_USER_PASS   		= '1q2w3e4r';
$S_CFG->BPM_SF_ACCESS_URL  		= 'https://login.salesforce.com/services/oauth2/token';
$S_CFG->BPM_SF_QUERY_URL   		= '/services/data/v20.0/query/?q=';
$S_CFG->BPM_SF_REGISTRATION_URL = '/services/data/v20.0/sobjects/Registration__c/';
$S_CFG->BPM_SF_END_FEEDBACK_FLOW_URL = '/services/data/v40.0/actions/custom/flow/EndCourse_Semester_LinkToFeedbackSummary';
$S_CFG->BPM_SF_END_NOTIFICATION_FLOW_URL = '/services/data/v40.0/actions/custom/flow/EndCourse_and_Semester_Notification';
$S_CFG->BPM_SF_COURSE_START_MARKETING_SMS = '/services/data/v40.0/actions/custom/flow/CourseStart_MarketingSMSs';
$S_CFG->BPM_SF_COURSE_END_MARKETING_SMS = '/services/data/v40.0/actions/custom/flow/CourseEnd_MarketingSMSs';
$S_CFG->BPM_SF_SEMESTER_START_MARKETING_SMS = '/services/data/v40.0/actions/custom/flow/CourseSemesterStart_MarketingSMS';
$S_CFG->BPM_SF_STUDENT_ACCOUNT_STATUS_FLOW_URL = '/services/data/v40.0/actions/custom/flow/AccStatus_ActiveStudent_OR_InDebt';
$S_CFG->BPM_IMG_FLDR_URL_MDL = '/home/mybpmmusic/public_html/moodle31/Extra_content/profile_picture/pics/';
$S_CFG->BPM_SF_END_FEEDBACK_INSTRUCTOR_URL = '/services/data/v40.0/actions/custom/flow/EndCourse_Semester_Notification_InstructorSendsFBLink';
$S_CFG->BPM_SF_COURSE_START_TEAM_EMAIL = '/services/data/v40.0/actions/custom/flow/CourseStart_TeamEmail';
$S_CFG->BPM_SF_COURSE_START_INSTRUCTOR_EMAIL = '/services/data/v40.0/actions/custom/flow/course_1month_notification_instructor';
$S_CFG->BPM_SF_COURSE_END_SALES_EMAIL = '/services/data/v40.0/actions/custom/flow/courseEnd_SalesEmail';
$S_CFG->BPM_CREATE_CONTINUING_STUDIES_TASKS_FOR_ADVISORS = '/services/data/v40.0/actions/custom/flow/create_continuing_studies_tasks_for_advisors';


$S_CFG->ACUITY_PASS = '11342667';
$S_CFG->ACUITY_USER = '16411ec8ea6abdbbc3ceb875fd584914';

$S_CFG->COURSE_MANAGER_ROLE_ID = '2';
$S_CFG->COURSE_TYPES  = array("program" => 1, "standalone" => 2);   // Course types
$S_CFG->PROGRAM_NAMES = array("BSP", "EMP", "GFA", "זמר יוצר", "DMP"); // Course program names
$S_CFG->ELECTRIC_MANDATORY_STRING = array('BSP','EMP','DMP','DJ','רדיו', 'קיובייס');
$S_CFG->ELECTRIC_MANDATORY__ATTENDANCE_SESSION_STRING = array('קיובייס');
$S_CFG->MUSIC_COURSES_NAMES = array("תיאוריה","תולדות","הרמוניה","פיתוח שמיעה","מוסיקה א","עיבוד","מוסיקה ב","יסודות בהלחנה","קומפוזיציה","תיווי","תזמור","מקלדת");

define('BPM_EMAIL_FOOTER', '<div dir="rtl" style="text-align:right;"><br><span style="font-style:italic">אין להשיב לדוא"ל זה</span><br><br>בברכה,<br>מכללת BPM<p><a href="http://www.bpm-music.com/" style="color:rgb(17,85,204);font-size:12.8px;text-align:right" target="_blank">BPM Website</a><span style="color:rgb(136,136,136);text-align:right;font-size:small">&nbsp;|&nbsp;</span><font size="2" style="color:rgb(136,136,136);text-align:right;font-size:small"><a href="https://www.facebook.com/BPM.College" style="color:rgb(17,85,204);text-align:start" target="_blank">Facebook</a>&nbsp;<span style="color:rgb(0,0,0);text-align:start">|&nbsp;</span><a href="https://instagram.com/bpmcollege/" style="color:rgb(17,85,204);text-align:start" target="_blank">Instagram</a>&nbsp;|&nbsp;<a href="https://soundcloud.com/bpmsoundschool" style="color:rgb(17,85,204)" target="_blank">Soundcloud</a>&nbsp;|&nbsp;<a href="https://www.youtube.com/user/BPMcollege" style="color:rgb(17,85,204)" target="_blank">Youtube</a></font></p><img src="http://services.bpm-music.com/pix/mail_sign_heb.png" width="400"  style="border:none"></div>');