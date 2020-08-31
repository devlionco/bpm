<?php

defined('MOODLE_INTERNAL') || die;
if($ADMIN->fulltree) {
	$settings->add(new admin_setting_heading(
	    'block_bpm_utils/headerconfig',
	    get_string('headerconfig', 'block_bpm_utils'),
	    get_string('descconfig', 'block_bpm_utils')
	));
	 
	$settings->add(new admin_setting_configcheckbox(
	    'block_bpm_utils/Allow_HTML',
	    get_string('labelallowhtml', 'block_bpm_utils'),
	    get_string('descallowhtml', 'block_bpm_utils'),
	    '0'
	));
}