<?php
class block_study_validation extends block_base {
    public function init() {
        $this->title = get_string('study_validation', 'block_study_validation');
    }
	
	function get_required_javascript() {
        parent::get_required_javascript();
 
        $this->page->requires->jquery();
        $this->page->requires->jquery_plugin('ui');
        $this->page->requires->jquery_plugin('ui-css');
    }
 
	
	public function get_content() {
    if ($this->content !== null) {
      return $this->content;
    }
 
	global $CFG, $DB, $USER, $PAGE, $COURSE;
	$and = "";
	
	if($COURSE->id != 1) {
		$and = "AND c.id = " . $COURSE->id;
	} 
	
	$mysql = "SELECT c.id, c.shortname
				FROM {course} c 
				JOIN {context} cx ON c.id = cx.instanceid AND cx.contextlevel = '50'
				JOIN {role_assignments} ra ON cx.id = ra.contextid
				JOIN {role} r ON ra.roleid = r.id
				JOIN {user} usr ON ra.userid = usr.id
				WHERE usr.id = ? AND r.shortname = 'student' AND c.visible = 1 $and
				GROUP BY c.id";
	
	if($student_course = $DB->get_records_sql($mysql, array($USER->id)))
	{
		$text = '';
		$courses = array();
		$this->content =  new stdClass;
	
		foreach($student_course as $value)
		{
			$courses[$value->shortname] = $value->shortname;
		}
		
		$PAGE->requires->js('/blocks/study_validation/js/jquery.multi-select.js');
		$PAGE->requires->js('/blocks/study_validation/js/study_validation.js');
		$PAGE->requires->css('/blocks/study_validation/style/multi-select.css');
		$PAGE->requires->strings_for_js(array(	'choose_courses',
												'courses_selected',
												'choose_by_clicking',
												'cancellation_by_pressing',
												'not_selected_courses'), 'block_study_validation');
		
		$attribute = array("id"=>"courses", "class"=>"multiselect", "multiple"=>"multiple");
		$text .= ' ' . html_writer::select($courses, "courses", "", false , $attribute);
		$url_to_tcpdf = $CFG->wwwroot . '/blocks/study_validation/tcpdf.php';
		$text .= '<button id="print">' . get_string('print','block_study_validation') . '</button>';
		$text .= '<form action="' . $CFG->wwwroot . '/blocks/study_validation/tcpdf.php" method="post" id="formcourses" style="display:none"></form>';
		$this->content->text   = $text ;
		return $this->content;
	
	} else {
		//$this->content->text = null;
		return null;
	}
  }
}