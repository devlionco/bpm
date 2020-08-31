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

/* SMS Notifier Block
 * SMS notifier is a one way SMS messaging block that allows managers, teachers and administrators to
 * send text messages to their student and teacher.
 * @package blocks
 * @author: Azmat Ullah, Talha Noor
 * @date: 06-Jun-2013
*/

defined('MOODLE_INTERNAL') || die;
if($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(get_string('block_bpm_sms_apikey', 'block_bpm_sms'),
                                                        get_string('bpm_sms_api_key', 'block_bpm_sms'),
                                                        get_string('bpm_sms_api_key', 'block_bpm_sms'),
                                                        '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext(get_string('block_bpm_sms_api_username', 'block_bpm_sms'),
                                                        get_string('bpm_sms_api_username', 'block_bpm_sms'),
                                                        get_string('bpm_sms_api_username', 'block_bpm_sms'),
                                                        '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext(get_string('block_bpm_sms_api_password', 'block_bpm_sms'),
                                                        get_string('bpm_sms_api_password', 'block_bpm_sms'),
                                                        get_string('bpm_sms_api_password', 'block_bpm_sms'),
                                                        '', PARAM_TEXT));

    $settings->add(new admin_setting_configselect('block_bpm_sms_api','SMS API Name', 'Select Api which you are using', 'Sendbpm_sms.pk', array('Clickatell','Sendbpm_sms.pk','cellact')));
}