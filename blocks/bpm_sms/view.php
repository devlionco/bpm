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

require_once('../../config.php');
require_once('bpm_sms_form.php');
require_once("lib.php");
// Global variable.
global $DB, $OUTPUT, $PAGE, $CFG, $USER;
require_login();
// Plugin variable.
$viewpage = required_param('viewpage', PARAM_INT);
$rem = optional_param('rem', null, PARAM_RAW);
$edit = optional_param('edit', null, PARAM_RAW);
$delete = optional_param('delete', null, PARAM_RAW);
$id = optional_param('id', null, PARAM_INT);
$c_id = optional_param('c_id', null, PARAM_INT);

// Page settings.
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string("pluginname", 'block_bpm_sms'));
$PAGE->set_heading('SMS Notification');
$pageurl = new moodle_url('/blocks/bpm_sms/view.php?viewpage=2');
$PAGE->set_url($pageurl);
// $PAGE->requires->js(new moodle_url('/blocks/bpm_sms/js/openapp.js'));
echo $OUTPUT->header();

// Conditions
if($viewpage == 1) {
    $form = new bpm_sms_form();
    if($table=$form->display_report()) {
        $a= html_writer::table($table);
        echo "<form action='#' method='GET' name='tests'>".$a."<input type='submit' name='submit' value='submit'/><input type='hidden'
             name='viewpage' id='viewpage' value='$viewpage'/></form>";
        if(isset($_GET['submit'])) {
            $user=$_GET['user'];
            if(empty($user)) {
                echo("You didn't select any user.");
            } else {
                $N = count($user);
            }
            for($i=0;$i<=$N;$i++) {
                send_bpm_sms($user[$i], "SMS sent successfully");
            }
        }
    }
}
else if($viewpage == 2) {
    $form = new bpm_sms_send();
    $form->display();
    $table=$form->display_report($c_id, 5); //roleid 5 = students, display all students in list by default
    $a= html_writer::table($table);
    echo "<form action='' method='post' name='tests'><div id='table-change'>".$a."</div><input type='submit' style='margin-left:700px;'
         name='submit' id='bpm_smssend' value='Send SMS'/><input type='hidden' name='viewpage' id='viewpage' value='$viewpage'/></form>";
    if(isset($_REQUEST['submit'])) {
    	if(isset($_POST['msg'])) {
        	$msg=$_POST['msg']; // SMS Meassage.
        $user = $_REQUEST['user']; // User ID.
        if(empty($user)) {
            echo("You didn't select any user.");
        }
        else {
            $N = count($user);
        }
       	 
	global $DB, $CFG;
        $table = new html_table();
        $table->head  = array(get_string('serial_no', 'block_bpm_sms'), get_string('moodleuser', 'block_bpm_sms'), get_string('usernumber', 'block_bpm_sms'), get_string('status', 'block_bpm_sms'));
        $table->size  = array('10%', '40%', '30%', '20%');
        $table->align  = array('center', 'left', 'center', 'center');
        $table->width = '100%';
        // Sendbpm_sms.pk API.
        if($CFG->block_bpm_sms_api == 1) {
            for($a=0; $a< $N;$a++) {
                 $id=$user[$a];
                 $sql='SELECT usr.firstname, usr.id, usr.lastname, usr.email,usr.phone2 FROM {user} usr WHERE usr.id =?';
                 $rs2 = $DB->get_record_sql($sql, array($id));
                 $no= $rs2->phone2;
                 if(!empty($no)) {
                     $status = send_bpm_sms($no,$msg);
                     if($status == get_string('sent', 'block_bpm_sms')) {
                         $status= "<img src=".$CFG->wwwroot.'/blocks/bpm_sms/pic/success.png'."></img>";
                     }
                }
                else {
                 $status= "<img src=".$CFG->wwwroot.'/blocks/bpm_sms/pic/error.png'."></img>";
                }

                $row = array();
                $row[] = $a+1;
                $row[] = $rs2->firstname;
                $row[] = $rs2->phone2;
                $row[] = $status;
                $table->data[] = $row;
            }
        }
// Clickatell API call.
        else if($CFG->block_bpm_sms_api == 0) {
            $number = array();
            for($a=0; $a< $N;$a++) {
                $id=$user[$a];
                $sql='SELECT usr.firstname, usr.id, usr.lastname, usr.email,usr.phone2 FROM {user} usr WHERE usr.id =?';
                $rs2 = $DB->get_record_sql($sql, array($id));
                $no= $rs2->phone2;
                if(!empty($no)) {
                    $number[] =$no;
                }
            }
            send_bpm_sms_clickatell($number, $msg);
//Cellact 
        } elseif ($CFG->block_bpm_sms_api == 2) {
            for($a=0; $a< $N;$a++) {
            	$id=$user[$a];
					$rs = $DB->get_field('user', 'phone2', array('id' => $id));
					if(!empty($rs)) {
						send_bpm_sms_cellact($rs, $msg);
					}
			}
			redirect($CFG->wwwroot."/course/view.php?id=$c_id", 'Messages sent succesfully');
        }}
         else {
        	echo "You didn't enter a text message.";
        }

        echo html_writer::table($table);
    }

}
else if($viewpage == 3) { 
    $form = new template_form();
    if($rem) {
        if($delete) {
            global  $DB;
            $DB->delete_records('block_bpm_sms_template', array('id'=>$delete));
            redirect($pageurl);
        }
        else {
              echo $OUTPUT->confirm(get_string('askfordelete', 'block_bpm_sms'), '/blocks/bpm_sms/view.php?viewpage=3&rem=rem&delete='.$id, '/blocks/bpm_sms/view.php?viewpage=3');
        }
   }
    // Edit Message Template.
    if($edit) {
        $get_template = $DB->get_record('block_bpm_sms_template', array('id'=>$id), '*');
        $form = new template_form();
        $form->set_data($get_template);
        
        
    }
    $toform['viewpage'] = $viewpage;
    $form->set_data($toform);
    $form->display();
    $table=$form->display_report();
    echo html_writer::table($table);
   
   
}


if($fromform = $form->get_data()) { 
        
    if($viewpage == 3) { 
        global $DB;
        $chk = ($fromform->id) ? $DB->update_record('block_bpm_sms_template', $fromform) : $DB->insert_record('block_bpm_sms_template', $fromform);
        redirect($pageurl);
    }
}

$params = array($viewpage);
$PAGE->requires->js_init_call('M.block_bpm_sms.init', $params);
echo $OUTPUT->footer();
