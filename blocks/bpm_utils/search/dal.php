<?php
//require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../../../config.php"); //moodle


if (!isset($_POST['mtd'])) {
    echo 'no mtd';
    //die();
}

switch ($_POST['mtd']) {
    case 'searchUser':
        echo bpm_search_users($_POST['input'], $_POST['inputType']);
        break;
     case 'getCoursesForUser':
        echo bpm_get_courses($_POST['userid']);
        break;
     case 'searchCourses':
        echo bpm_search_courses($_POST['input']);
        break;
    default:
        echo 'invalid mtd value';
}

function bpm_search_users($input, $input_type) {
    global $DB;
    $mysqli = get_db_connection();
    
    if ($input_type == 'array') {
        $input = implode("*, ", $input);
    } else {
        $input = $input . "*";
    }
   // return json_encode($input);
    /*$sql = "SELECT u.id, u.picture, u.firstname, 
            u.lastname, u.email, u.username, 
            u.suspended, u.emailstop, u.phone1, 
            u.phone2, COUNT(ue.id) as coursecount
            FROM mdl_user u, mdl_user_enrolments ue
            WHERE CONCAT(u.id, ' ', u.firstname, u.lastname, u.email, u.username, u.phone1, u.phone2) LIKE '%$input%'
            AND ue.userid = u.id
            AND ue.status != 1
            AND u.deleted = 0
            GROUP BY u.id
            ORDER BY id ASC";*/
    
    $sql = "SELECT u.id, u.picture, u.firstname, 
            u.lastname, u.email, u.username, 
            u.suspended, u.emailstop, u.phone1, 
            u.phone2, COUNT(ue.id) as coursecount
            FROM mdl_user u, mdl_user_enrolments ue
			WHERE MATCH(u.firstname, u.lastname, u.email, u.username, u.phone1, u.phone2)
			    AGAINST('$input' IN BOOLEAN MODE)
            AND ue.userid = u.id
            AND ue.status != 1
            AND u.deleted = 0
            GROUP BY  u.id
            ORDER BY (MATCH(u.firstname, u.lastname, u.email, u.username, u.phone1, u.phone2)
			    AGAINST('$input' IN BOOLEAN MODE)) DESC";
    
  if (!$result = $mysqli->query($sql)) {
        echo "Error: your query failed to execute and here is why: \n<br>";
        echo "Query: " . $sql . "\n<br>";
        echo "Errno: " . $mysqli->errno . "\n<br>";
        echo "Error: " . $mysqli->error . "\n<br>";
    }
    if ($result->num_rows > 0) {
    		while($row = $result->fetch_assoc()) {
    			$current_row = [];
    	        foreach ($row as $key => $value) {
                    if ($value === NULL) {
						$value = "";
                    }
					$current_row[$key] = $value;
				}
  				$rows[] = $current_row;
			}
			$encoded_rows = json_encode($rows, JSON_UNESCAPED_UNICODE);
			return($encoded_rows);
    } else {
        return 'no records!';
    }
    //$records = $DB->get_records_sql($sql);
    
    return json_encode($records);
}

function bpm_get_courses($user_id) {
    global $DB;
    $mysqli = get_db_connection();
    
    $sql = "SELECT c.id, c.shortname as coursename, 
                    c.startdate, c.enddate, 
                    CONCAT(u2.firstname, ' ', u2.lastname) as instructorname,
                    u2.id as instructorid,
                   sfe.grade,  sfe.attendance, sfe.account_sfid, sfe.completegrade,
                   ue.status, r.shortname as rolename
            FROM mdl_course c, mdl_context cx, 
                mdl_role_assignments ra, mdl_role r,
                mdl_enrol e, mdl_user usr,
                mdl_user_enrolments ue,
                mdl_sf_enrollments sfe,
                mdl_user u2
            WHERE c.id = cx.instanceid
                AND sfe.courseid = c.id
                AND sfe.userid = usr.id
                AND cx.contextlevel = '50'
                AND cx.id = ra.contextid
                AND ra.roleid = r.id
			    AND ra.userid = usr.id
                AND ue.userid = usr.id
                AND e.courseid = c.id
                AND e.id = ue.enrolid 
                AND ue.userid = $user_id
                AND u2.id = (SELECT u3.id
							FROM mdl_user u3, mdl_course c2, 
								mdl_role r2, mdl_role_assignments ra2,
								mdl_context cx2 
							WHERE r2.shortname = 'editingteacher'
							AND c2.id = c.id
							AND ra2.userid = u3.id
							AND cx2.contextlevel = '50'
							AND cx2.instanceid = c2.id
							AND ra2.contextid = cx2.id
							AND ra2.roleid = r2.id
							LIMIT 1
							)";
	

    //$records = $DB->get_records_sql($sql);
    if (!$result = $mysqli->query($sql)) {
        echo "Error: your query failed to execute and here is why: \n<br>";
        echo "Query: " . $sql . "\n<br>";
        echo "Errno: " . $mysqli->errno . "\n<br>";
        echo "Error: " . $mysqli->error . "\n<br>";
    }
    if ($result->num_rows > 0) {
    		while($row = $result->fetch_assoc()) {
    			$current_row = [];
    	        foreach ($row as $key => $value) {
                    if ($value === NULL) {
								$value = "";
					} else if ($key == 'activity_date' || $key == 'created_date') {
						$date_parts = explode('-', $value);	
  						$value = "$date_parts[2]/$date_parts[1]/$date_parts[0]";
					}
						$current_row[$key] = $value;
				}
  				$rows[] = $current_row;
			}
			$encoded_rows = json_encode($rows, JSON_UNESCAPED_UNICODE);
			return($encoded_rows);
    } else {
        return 'no records!';
    }
   // return var_dump($records);
    //return json_encode($records);
}
function bpm_search_courses($input) {
    global $DB;

    /*$sql = "SELECT c.id, c.shortname as coursename, 
                    c.startdate, c.enddate, 
                    CONCAT(u2.firstname, ' ', u2.lastname) as instructorname,
                    u2.id as instructorid
            FROM mdl_course c, 
                mdl_enrol e,
                mdl_user_enrolments ue,
                mdl_user u2
            WHERE ue.userid = u2.id
                AND e.courseid = c.id
                AND e.id = ue.enrolid 
                AND ue.userid = u2.id
                AND u2.id = (SELECT u3.id
							FROM mdl_user u3, mdl_course c2, 
								mdl_role r2, mdl_role_assignments ra2,
								mdl_context cx2 
							WHERE r2.shortname = 'editingteacher'
							AND c.shortname LIKE '%" . $input . "%'
							AND c2.id = c.id
							AND ra2.userid = u3.id
							AND cx2.contextlevel = '50'
							AND cx2.instanceid = c2.id
							AND ra2.contextid = cx2.id
							AND ra2.roleid = r2.id
							LIMIT 1)";*/ //AND MATCH(c.shortname) AGAINST('$input*' IN NATURAL LANGUAGE MODE)
    
    $sql = "SELECT c.id, c.shortname as coursename, 
                    c.startdate, c.enddate, 
                    CONCAT(u2.firstname, ' ', u2.lastname) as instructorname,
                    u2.id as instructorid
            FROM mdl_course c, 
                mdl_enrol e,
                mdl_user_enrolments ue,
                mdl_user u2
            WHERE ue.userid = u2.id
                AND e.courseid = c.id
                AND e.id = ue.enrolid
                AND c.shortname LIKE '%$input%'
                
                AND u2.id = (SELECT u3.id
							FROM mdl_user u3, mdl_course c2, 
								mdl_role r2, mdl_role_assignments ra2,
								mdl_context cx2 
							WHERE r2.shortname = 'editingteacher'
                            AND c2.id = c.id
							AND ra2.userid = u3.id
							AND cx2.contextlevel = '50'
							AND cx2.instanceid = c2.id
							AND ra2.contextid = cx2.id
							AND ra2.roleid = r2.id
							LIMIT 1)";
    //return 'sql is ' . $sql;
    $records = $DB->get_records_sql($sql);
    
    return json_encode($records);
}

function get_db_connection() {
    $mysqli = new mysqli('localhost','superuserben', 'Aa123456', 'mybpmmus_moodle_bpm');
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }
    mysqli_set_charset($mysqli,"utf8");
    return $mysqli;
}
