<?php
require_once __DIR__ . '/../../config.php';
require_once("$CFG->dirroot/course/lib.php");
require_once 'config.php';
require_once 'sfsql.class.php';

$sfsql = new sfsql($wsdl, $userName, $password, $token);

$sObject = new sObject();
$sObject->type = 'Registration__c';
$sObject->fields =
	array(
		'Course__c' => 'a002400000AB1QVAA1',
	);

$sObjects = array(
	$sObject
);

$results = $sfsql->insert("Name", $sObjects);
echo '<pre dir=ltr style=text-align:left>' . print_r( $results , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>'; 

/////////////////////////////////////////////////////////////////////////////////////////////
/* $courses = $sfsql->query("SELECT Course__c.* FROM Course__c");

while ($course = $courses->fetch_object())
{
	echo '<pre dir=ltr style=text-align:left>' . print_r( $course , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';

	if (!$DB->record_exists_sql("SELECT 1 FROM {course} WHERE idnumber = ? OR shortname = ?", [$course->Id, $course->Name]))
	{
		// fields required by moodle are: category, shortname, format (at least "weeks")
		// fields required by salesforce are: idnumber, 
		// optional fields are: fullname, timecreated
		$result = create_course(
			(object)[
				'category' => $course_store_category,
				'shortname' => $course->Name,
				'fullname' => $course->Name,
				'idnumber' => $course->Id,
				'format' => 'weeks'
			]
		);
		echo '<pre dir=ltr style=text-align:left>' . print_r( $result , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
	}
}
die;
// // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // /
$sObject = new sObject();
$sObject->type = 'Course__c';
$sObject->fields =
	array(
		'Name' => 'Can I insert a course remotely?',
		'Moodle_Course_Id__c' => 1123145
	);

$sObjects = array(
	$sObject
);

$results = $sfsql->upsert("Moodle_Course_Id__c", $sObjects);
echo '<pre dir=ltr style=text-align:left>' . print_r( $results , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>'; */
