<?php
class block_course_details extends block_base {
    public function init() {
        $this->title = get_string('course_details', 'block_course_details');
    }
	
	function get_required_javascript() {
        parent::get_required_javascript();
 
        $this->page->requires->jquery();
        $this->page->requires->jquery_plugin('ui');
        $this->page->requires->jquery_plugin('ui-css');
    }
	
	function has_config() {
		return true;
	}
  
	
	public function get_content() {
	 	global $CFG, $DB, $USER, $PAGE, $COURSE, $_POST;
		require_once($CFG->dirlib . '/coursecatlib.php');
		
		if ($this->content !== null) {
		  return $this->content;
		}
		
		$context = context_course::instance($COURSE->id);
		$PAGE->requires->css('/blocks/course_details/css/course_details.css');
		
		if(!has_capability('block/course_details:view', $context)){
			return null;
		}
		
		$sql = "SELECT value 
				FROM {config_plugins} 
				WHERE plugin = 'local_salesforce' AND name = 'coursestorecategory'";
		$course_store_category = $DB->get_field_sql($sql);
		$teacher_role = has_capability('block/course_details:addinstance', $context);
		$categories = coursecat::get($COURSE->category, 'MUST_EXIST', true)->get_parents();
		$in_master_category = in_array($course_store_category, $categories) || $COURSE->category == $course_store_category;
		
		if ($COURSE->id  != 1)
		{ 
			if(isset($_POST['submit']))
			{
				$end_date = null;
				$class = $_POST['class_list'];
				
				if(isset($_POST['end_date']) && !empty($_POST['end_date']))
				{
					$end_date = strtotime($_POST['end_date']);
					$end_date = date('Y-m-d H:i:s', $end_date);
				}
				
				if(isset($_POST['class_list']) && ($_POST['class_list'] == 'choose_class' || $_POST['class_list'] == 'reset_class'))
				{
					$class = null;
				}
				
				$mysql = 'SELECT 1 FROM {course_details} WHERE courseid = ?';
				
				if($DB->record_exists_sql($mysql, array($COURSE->id)))
				{
					$mysql = 'UPDATE {course_details} 
							SET end_date = ?, classroom = ?
							WHERE courseid = ?';
				} else {
					$mysql = 'INSERT INTO {course_details} 
							SET end_date = ?, classroom = ?, courseid = ?';
				}
				
				$DB->execute($mysql, array($end_date, $class, $COURSE->id));
				header('Location: '.$_SERVER['REQUEST_URI']);
			}
			
			$this->content = new stdClass;
			$text = '';
			$mysql = 'SELECT c.id, c.category, c.shortname, c.startdate, cd.end_date, cd.meetings_amount, cd.several_days, cd.capacityclass, cd.classroom, cr.name, cr.capacity
						FROM {course} c
						LEFT JOIN {course_details} cd ON c.id = cd.courseid
						LEFT JOIN {classrooms} cr ON cd.classroom = cr.id
						WHERE c.id = ? LIMIT 1';
						
			if($course = $DB->get_record_sql($mysql, array($COURSE->id)))
			{
				if(!$in_master_category)
				{
					if(!isset($course->end_date) || $course->end_date == 0)
					{
						$end_date = '';
					} else {
						$end_date = strtotime($course->end_date);
						$end_date = date('d-m-Y', $end_date);
					}
				}
				
				$text .= '<form id="course_details" action="" method="post">';
				
				if(!$in_master_category)
				{
					if(!isset($course->end_date) || $course->end_date == 0)
					{
						$end_date = '';
					} else {
						$end_date = strtotime($course->end_date);
						$end_date = date('d-m-Y', $end_date);
					}
					
					$text .= '	<label for="end_date">' . get_string('end_date', 'block_course_details') . ':</label>';
					if($teacher_role) {
						$text .= '<input type="text" name="end_date" id="end_date" value="' . $end_date . '" ><br>';
					} else {
						$text .= '<label name="end_date" id="end_date">' . $end_date . '</label><br>';
					}
				} else {
					$text .= '	<label id="master_course"><b>' . get_string('master_course', 'block_course_details') . '</b></label><br>';
				}
							
				
				$text .= '	<label id="meetings_amount">' . get_string('meetings_amount', 'block_course_details') . ':</label>
							<label id="meetings_amount">' . $course->meetings_amount . '</label><br>
							<label id="several_days">' . get_string('several_days', 'block_course_details') . ':</label>
							<label id="several_days">' . $course->several_days . '</label><br>
							<label id="max_students">' . get_string('max_students', 'block_course_details') . ':</label>
							<label id="max_students">' . $course->capacityclass . '</label><br>';	
							
				if(!$in_master_category)
				{
					$class_list = array();
					$attribute = array("id"=>"class_list", "form"=>"course_details");
					$mysql = 'SELECT id, number, name, place, capacity
								FROM {classrooms}';
					$classes = $DB->get_records_sql($mysql);
					
					foreach($classes as $class)
					{
						if(isset($class->name) && !empty($class->name))
						{
							$class_list[$class->id] = get_string('room_name', 'block_course_details') . ' ' . $class->name . ', ' . get_string('capacity_classroom', 'block_course_details') . ' ' . $class->capacity;
						} else {
							$class_list[$class->id] = get_string('room_number', 'block_course_details') . ' ' . $class->number . ', ' . get_string('capacity_classroom', 'block_course_details') . ' ' . $class->capacity;
						}
					}
					
					if(count($class_list))
					{
						if(isset($course->classroom))
						{
							$class_list = array('reset_class' => get_string('reset_class', 'block_course_details')) + $class_list;
						} else {
							$class_list = array('choose_class' => get_string('choose_class', 'block_course_details')) + $class_list;
						}
						
						if($teacher_role) {
							$text .= ' ' . html_writer::select($class_list, "class_list", $course->classroom, false , $attribute) . '<br>';
						} else {
							if($course->classroom) {
								$text .= '<label id="class_list" name="class_list">' . $class_list[$course->classroom] . '</label><br>';
							}
						}
					}
					
					if($teacher_role) {
						$text .=	'<input type="submit" id="submit" name="submit" value="' . get_string('update', 'block_course_details') . '" >';
					}
				}
							
				$text .= '</form>';
				
				if(!$in_master_category && $teacher_role)
				{
					$url = $CFG->wwwroot . '/blocks/course_details/insert_classroom.php?courseid=' . $COURSE->id;
					$text .= '<br><a href="' . $url . '">' . get_string('edit_classroom', 'block_course_details') . '</a>';
				}
				
				$text .= '<script>
							$("#end_date").datepicker({
								dateFormat: "dd-mm-yy",	
								buttonImage: "img/datepicker.gif"
							});
							</script>';
			}
			
			$this->content->text = $text;
			return $this->content;
			
		} else {
			return null;
		}
	}
}
