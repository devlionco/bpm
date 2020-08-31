<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once("$CFG->libdir/formslib.php");

class classroom_form extends moodleform {
	
	public function definition() {
		
		global $DB, $CFG, $COURSE;
		$classes = $DB->get_records('classrooms', null);
		$class_list = array();
		
		foreach($classes as $class)
		{
			if(isset($class->name) && !empty($class->name))
			{
				$class_list[$class->id] = get_string('room_name', 'block_course_details') . ' ' . $class->name . ', ' . get_string('capacity_classroom', 'block_course_details') . ' ' . $class->capacity;
			} else {
				$class_list[$class->id] = get_string('room_number', 'block_course_details') . ' ' . $class->number . ', ' . get_string('capacity_classroom', 'block_course_details') . ' ' . $class->capacity;
			}
		}
		
		$class_list = array('new_class' => get_string('new_class', 'block_course_details')) + $class_list;

		
		$mform = $this->_form; // Don't forget the underscore!
		$mform->addElement('html', '<div><h2>' . get_string('classrooms_list', 'block_course_details') . '</h2></div>');		
		$select = $mform->addElement('select', 'classes', get_string('classes', 'block_course_details'), $class_list, null);
		$mform->addElement('text', 'number', get_string('num_class', 'block_course_details'), null);
		$mform->setType('number', PARAM_RAW);
		$mform->addRule('number', get_string('required'), 'required', false, 'client');
		$mform->addRule('number', get_string('numeric', 'block_course_details'), 'numeric', false, 'client');
		$mform->addElement('text', 'name', get_string('class_name', 'block_course_details'), null);
		$mform->addRule('name', get_string('required'), 'required', false, 'client');
		$mform->setType('name', PARAM_RAW);
		$mform->addElement('text', 'place', get_string('class_place', 'block_course_details'), null);
		$mform->setType('place', PARAM_RAW);
		$mform->addElement('text', 'capacity', get_string('class_capacity', 'block_course_details'), null);
		$mform->setType('capacity', PARAM_RAW);
		$mform->addRule('capacity', get_string('required'), 'required', false, 'client');
		$mform->addRule('capacity', get_string('numeric', 'block_course_details'), 'numeric', false, 'client');
		$mform->addElement('button', 'delete', get_string('remove', 'block_course_details'));
		$mform->addElement('button', 'update', get_string('update_classroom', 'block_course_details'));
		$mform->addElement('button', 'send', get_string('add', 'block_course_details'));
	}
}