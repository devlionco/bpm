<?php
defined('MOODLE_INTERNAL') || die;

global $PAGE;

// $ADMIN->add('root', new admin_externalpage('local_salesforce_manage', get_string('manage', 'local_salesforce'), new moodle_url('/local/salesforce/admin/manage.php'), 'local/salesforce:admin'));

$temp = new admin_settingpage('local_salesforce', get_string('pluginname', 'local_salesforce'));

$temp->add(
	new admin_setting_configtext(
		'local_salesforce/coursestorecategory',
		get_string('coursestorecategory', 'local_salesforce'),
		get_string('coursestorecategorydesc', 'local_salesforce'),
		null,
		PARAM_INT
	)
);

$temp->add(
	new admin_setting_configtext(
		'local_salesforce/sfusername',
		get_string('sfusername', 'local_salesforce'),
		get_string('sfusername', 'local_salesforce'),
		null,
		PARAM_RAW
	)
);

$temp->add(
	new admin_setting_configtext(
		'local_salesforce/sfpassword',
		get_string('sfpassword', 'local_salesforce'),
		get_string('sfpassword', 'local_salesforce'),
		null,
		PARAM_RAW
	)
);

$temp->add(
	new admin_setting_configtext(
		'local_salesforce/sftoken',
		get_string('sftoken', 'local_salesforce'),
		get_string('sftoken', 'local_salesforce'),
		null,
		PARAM_RAW
	)
);

$ADMIN->add('webservicesettings', $temp);