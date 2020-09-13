<?php
//gets called by local/salesforce/run_once_per_day.php
require_once 'config.php';
require_once __DIR__ . '/../config.php';


function course_start_end_messages_exec() {
    ob_flush();
    ob_start();
    echo "<pre>";
    $current_log = file_get_contents("log.txt");
    $current_log .= "***************************";
    $current_log .= "\n" . date("d/m/Y") . "\n";
    $current_log .= ob_get_flush();
    $current_log .= "\n";
    file_put_contents("log.txt", $current_log);

    ob_clean();
    $courses = bpm_get_courses_start_end_today();
    foreach($courses as $course) {
        if ($message = bpm_get_message_template($course)) {
            bpm_create_forum_post($course->id, $course->shortname, $message);
        } else {
            echo "\n" . $course->shortname . ' - no message';
        }
    }
}


/**
 * get courses that start or end today
 *
 * @global stdClass  $DB  Moodle DataBase API.
 *
 */
function bpm_get_courses_start_end_today() {
    global $DB;

    $sql = "SELECT id, startdate, enddate, shortname
			FROM mdl_course
			WHERE ((shortname NOT LIKE \"שנה ב' - כללי\" 
			AND from_unixtime(startdate, '%Y-%m-%d') = CURDATE() + INTERVAL 2 DAY)
			OR (shortname NOT LIKE \"שנה א' - כללי\" 
			AND from_unixtime(enddate, '%Y-%m-%d') = CURDATE()
			)) AND shortname NOT LIKE \"%בטיחות בחשמל%\"";

    return $DB->get_records_sql($sql);
}

function bpm_get_message_template($course) {
    $course_type = bpm_course_type($course->shortname);
    $end_date = date('Y-m-d', $course->enddate);
    $today = date("Y-m-d");
    $message_group = ($end_date == $today) ? "end" : "start";
    $subject = ($end_date == $today) ? "סיום הקורס" : "ברוכים הבאים ללימודים!";
    $branch = determine_branch($course->shortname);


    /*echo "\n" . 'name: ' . $course->shortname . "\n";
    echo "\n" . '(!strpos($course->shortname, "כללי")): ';
    echo (!strpos($course->shortname, "כללי"));
    echo "\n" . 'course type: ' . $course_type . "\n";*/
    echo "\n" . 'branch: ' . $branch . "\n";
    if ($message_group == "start") {
        switch ($branch) {
            case "Tel Aviv":
                switch (true) {
                    case (strpos($course->shortname, "DJ") > -1):
                        $message_selector = "DJ";
                        break;
                    case (strpos($course->shortname, "PA") > -1):
                        $message_selector = "PA";
                        break;
                    case (strpos($course->shortname, "רדיו") > -1) ||
                        (strpos($course->shortname, "קריינות") > -1) ||
                        (strpos($course->shortname, "לוג'יק") > -1) ||
                        (strpos($course->shortname, "סקראץ'") > -1):
                        $message_selector = 'radio_voiceover_logic_scratch';
                        break;
                    case ((!strpos($course->shortname, "כללי")) &&
                        $course_type == 1):
                        return false;
                        break;
                    default:
                        $message_selector = 'other';
                }
                break;
            case "Haifa":
                switch (true) {
                    case (strpos($course->shortname, "DJ") > -1):
                        $message_selector = "DJ_Haifa";
                        break;
                    case (strpos($course->shortname, "רדיו") > -1) ||
                        (strpos($course->shortname, "קריינות") > -1) ||
                        (strpos($course->shortname, "לוג'יק") > -1) ||
                        (strpos($course->shortname, "סקראץ'") > -1):
                        $message_selector = 'radio_voiceover_logic_scratch_Haifa';
                        break;
                    case ((!strpos($course->shortname, "כללי")) &&
                        $course_type == 1):
                        return false;
                        break;
                    default:
                        $message_selector = 'other_Haifa';
                }
                break;
            case "Online":
                switch(true) {
                    case (strpos($course->shortname, "DJ") > -1) ||
                        (strpos($course->shortname, "קיובייס") > -1) ||
                        (strpos($course->shortname, "אבלטון") > -1) ||
                        (strpos($course->shortname, "יסודות בהלחנה ונגינה") > -1):
                        $message_selector = 'online_DJ_cubase_ableton_theory';
                        break;
                    default:
                        $message_selector = "online";
                }
                break;
        }

    } else { //end
        switch($branch) {
            case "Online":
                switch(true) {
                    case (strpos($course->shortname, "DJ") > -1) ||
                        (strpos($course->shortname, "קיובייס") > -1) ||
                        (strpos($course->shortname, "אבלטון") > -1) ||
                        (strpos($course->shortname, "יסודות בהלחנה ונגינה") > -1):
                        $message_selector = 'online_DJ_cubase_ableton_theory';
                        break;
                    default:
                        $message_selector = "online";
                }
                break;
            default:
                switch (true) {
                    case (strpos($course->shortname, "כללי") > -1 &&
                        strpos($course->shortname, "BSP") > -1):
                        $message_selector = "BSP";
                        break;

                    case (strpos($course->shortname, "כללי") > -1 &&
                        strpos($course->shortname, "EMP") > -1):
                        $message_selector = "EMP";
                        break;
                    case ((!strpos($course->shortname, "כללי")) &&
                        $course_type == 1):
                        return false;
                        break;
                    default:
                        $message_selector = 'other';
                }
        }
    }
    echo 'message_group: ' . $message_group . "\n";
    echo '$message_selector: ' . $message_selector . "\n";

    $templates = json_decode(file_get_contents(__DIR__ . '/templates.json'));
    $message = new Stdclass();
    $message->subject = $subject;
    $message->body = $templates->$message_group->$message_selector;
    echo 'returning message' . "\n";
    return $message;
}

function determine_branch($str) {
    if (strpos($str, "חיפה") > -1) {
        $branch = "Haifa";
    } else if (strpos($str, "אונליין") > -1) {
        $branch = "Online";
    } else {
        $branch = "Tel Aviv";
    }
    return $branch;
}

//returns 1 for standalone, 2 for program
function bpm_course_type($course_name, &$program_name = NULL) {
    global $BPM_CFG;

    $program_names = $BPM_CFG->PROGRAM_NAMES;
    for ($name_index=0; $name_index < count($program_names); $name_index++) {
        if (strpos($course_name, $program_names[$name_index]) > -1) {
            $program_name = $program_names[$name_index];
            return $BPM_CFG->COURSE_TYPES['program'];
        }
    }
    return $BPM_CFG->COURSE_TYPES['standalone'];
}

function bpm_create_forum_post($course_id, $course_name, $message) {
    global $DB;
    if (!$message->body) {
        echo 'error: no message body!';
        return false;
    }
    echo "posting in course: " . $course_name . "\n";

    $forum_id = $DB->get_field('forum', 'id', array('course' => $course_id, 'name' => 'הודעות מערכת'));

    $discussion_record = new stdClass();
    $discussion_record->course = $course_id;
    $discussion_record->forum = $forum_id;
    $discussion_record->name = $message->subject;
    $discussion_record->userid = 121;
    $discussion_record->timemodified = time();
    $discussion_record->usermodified = 121;

    $discussion_id = $DB->insert_record('forum_discussions', $discussion_record, true);

    if (isset($discussion_id)) {
        $post_record = new stdClass();
        $post_record->discussion = $discussion_id;
        $post_record->userid = 121;
        $post_record->created = time();
        $post_record->modified = time();
        $post_record->subject = $message->subject;
        $post_record->message = $message->body;
        $post_record->messageformat = 1;
        $post_record->messagetrust = 1;
        $post_record->mailnow = 1;

        $post_id = $DB->insert_record('forum_posts', $post_record, true);
        if ($post_id) {
            echo ("\n" . 'post ' .  $post_id . ' (' . $message->subject . ') ' . ' created in course ' . $course_name . ' (' . $course_id . ')' . "\n");
        }

        if (isset($post_id)) {
            $discussion_update = new stdClass();
            $discussion_update->id = $discussion_id;
            $discussion_update->firstpost = $post_id;
            $DB->update_record('forum_discussions' , $discussion_update);
        }
    }

}