<?php
ini_set( 'error_reporting', E_ALL );
ini_set( 'display_errors', true ); 
require_once('../../config.php');
//echo "<pre>";

if (!$userid = $_GET['userid']) {
    echo 'שגיאה.';
    die();
} else { 
    $student = bpm_get_student($_GET['userid']);
    
    
    $profile_page_url = new moodle_url('/user/profile.php', array(id=>$student->id));

// $PAGE->set_context(context_course::instance(1));
//$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('admin');
//$PAGE->set_pagelayout('standard');
$student_name = $student->firstname . ' ' . $student->lastname;
$PAGE->set_title("דוח נוכחות מפורט - " . $student_name);
$PAGE->set_heading("דוח נוכחות מפורט - " . $student_name);
//$PAGE->set_url($CFG->wwwroot.'/blank_page.php');
$page_parameters = array('userid' => $_GET['userid']);
$PAGE->set_button(bpm_print_btn());

// $settingsnode = $PAGE->settingsnav->add('asd');
// $settingsnode->add('דוח נוכחות מפורט', 'www.google.com');
// $settingsnode->make_active();
 $previewnode = $PAGE->navigation->add($student_name, $profile_page_url, navigation_node::TYPE_CONTAINER);
 $thingnode = $previewnode->add('דוח נוכחות מפורט');
 $thingnode->make_active();

require_login();

echo $OUTPUT->header();

    if (!$sessions = getAllSessions($userid)) {
        //echo 'error' . __LINE__;
    } else {
        if (!$logs = getAllLogs($userid)) {
            // echo 'error  ' . __LINE__;
        }
        //var_dump($sessions);
        // echo PHP_EOL;
        //var_dump($logs);
        foreach($sessions as $session) {
            $session->log = $logs[$session->id]->description;
            
        }
        
        $sessions = json_encode($sessions,JSON_UNESCAPED_UNICODE);
        //var_dump($sessions);
        echo "<script>var bob = " . $sessions . ";</script>";
    }
}

function getAllSessions($userid) {
    global $DB;
    $sql = "SELECT ass.id, c.id as courseid, c.shortname, 
            ass.description, ass.sessdate, 
            CONCAT(u.firstname, ' ', u.lastname) as student_name, ue.status as enrollment_status
            FROM mdl_user u, mdl_attendance_sessions ass, mdl_enrol e, mdl_user_enrolments ue, mdl_course c, mdl_attendance a, mdl_sf_enrollments sfe
            WHERE ass.attendanceid = a.id
            AND a.course = c.id
            AND e.courseid = c.id
            AND e.id = ue.enrolid
            AND ue.userid = u.id
            AND c.id = sfe.courseid
            AND u.id = sfe.userid
            AND ass.sessdate <= UNIX_TIMESTAMP()
            AND u.id = $userid";
    if (!$records = $DB->get_records_sql($sql)) {
        // echo 'error';
        return false;
    } else {
        return $records;
    }
}

function getAllLogs($userid) {
    global $DB;
    $sql = "SELECT  alogs.sessionid, alogs.id, astats.description
            FROM mdl_attendance_log alogs, mdl_attendance_statuses astats
            WHERE astats.id = alogs.statusid
            AND alogs.studentid = $userid";
        if (!$logs = $DB->get_records_sql($sql)) {
            echo 'error';
            return false;
        } else {
            return $logs;
        }
}
function bpm_print_btn() {
    $html = '<button type="buttton" class="bpm_ui_btn" id="bpm_print_btn" 
                onclick="bpm_print($(\'#bpm_main\'))"><i class="fas fa-print">
                </i></button>';
    return $html;
}
function bpm_get_student($userid) {
    global $DB;
    $user = $DB->get_record('user', ['id' => $userid]);
    
    return $user;
}
?>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1 user-scalable=1">
<link rel="icon" type="image/png" href="../../local/BPM_pix/bpm_favicon.ico">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css"/>
  
</head>
<body>
  <div id="bpm_main">
  <link rel="stylesheet" href="style.css?ver=2"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css">
  <script src="https://services.bpm-music.com/BPM_common.js"></script>
  <script src="script.js?ver= <?php echo time(); ?>"></script>
    <?php echo $logo = '<img id="logo" width="200" src="https://my.bpm-music.com/local/BPM_pix/logo_clean.png"/>'; ?>
    <h1 id="pageTitle">דוח נוכחות מפורט</h1>    
    <?php echo '<a  id="profilePic" class="dontPrint" href="' . $profile_page_url . '"><img width="100" src="' . new moodle_url('/user/pix.php/'.$student->id.'/f1.jpg') . '"/></a>'; ?>
<!-- TODO toggle between course & date-based groupings -->
<h2 id="subtitle"></h2>
<div id="bpm_ui_container">
    <div id="dateBox">
    <div class="switch-field">
        <span>מציג נתונים  </span>
        <input id="dateType_all" type="radio" name="dateType" value="all" onchange="applyDateFilter(this)" checked/>
            <label for="dateType_all">מכל הזמנים</label>
        <input id="dateType_choose" type="radio" name="dateType" value="choose" onchange="applyDateFilter(this)"/>
            <label for="dateType_choose">מבין התאריכים:</label>
                <input id="dateFrom" class="hasDatePicker" type="date" name="dateFrom" onchange="applyDateFilter(this)"/>
                <span id="dateBetween">עד</span>
                <input id="dateTo" class="hasDatePicker" type="date" name="dateTo" onchange="applyDateFilter(this)"/>
        </div>
    </div>
    <div id="showDescriptionsBox" class="dontPrint">
        <input type="checkbox" checked id="showSessDescriptions" onchange="toggleSessDescriptions(this)"/><label for="showSessDescriptions"></label></label><label for="showSessDescriptions" title="הצג תיאורי מפגש">הצג תיאורי מפגש</label>
    </div>
    <div id="descriptiontruncationBox" class="dontPrint">
        <input type="checkbox" id="truncateDescriptions" onchange="truncateDescriptions(this)"/><label for="truncateDescriptions"></label></label><label for="truncateDescriptions" title="קטום תיאורי מפגש ארוכים">קטום תיאורי מפגש ארוכים</label>
    </div>
</div>
      <table id="sessions">
        <thead>
          <tr class="headers">
            <th class="narrowColumnHeader">מס'</th>
            <th class="narrowColumnHeader" id="sessDateHeader">מועד</th>
            <th id="sessDescription">נושא השיעור</th>
            <th class="narrowColumnHeader">רישום</th>
            <th class="narrowColumnHeader" id="scoreColumnHeader">ניקוד</th>
            <th class="narrowColumnHeader"></th>
        </tr>
        </thead>

      </table>
    
  
  </div>
</body>
</html>
<?php
echo $OUTPUT->footer();
?>