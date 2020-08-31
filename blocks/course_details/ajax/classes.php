<?php
require_once(dirname(__FILE__) . '/../../../config.php');

function find($id) {
	global $DB;
	$class = $DB->get_records('classrooms', array('id' => $id));
	echo json_encode(end($class));
}

function remove($id) {
	global $DB;
	$DB->execute("UPDATE {course_details} SET classroom = NULL WHERE classroom = ?", array($id));
	die($DB->delete_records('classrooms', array('id' => $id)));
}

function update($id) {
	global $DB;
	$sql = 'UPDATE {classrooms} SET number = ?, name = ?, place = ?, capacity = ? WHERE id = ?';
	die($DB->execute($sql, array($_POST['number'], empty($_POST['name'])?null:$_POST['name'], empty($_POST['place'])?null:$_POST['place'], $_POST['capacity'], $id)));
}

function add() {
	global $DB;
	$sql = 'INSERT INTO {classrooms} SET number = ?, name = ?, place = ?, capacity = ?';
	die($DB->execute($sql, array($_POST['number'], empty($_POST['name'])?'':$_POST['name'], empty($_POST['place'])?'':$_POST['place'], $_POST['capacity'])));
}

if(isset($_POST['id']))
{
	$id = $_POST['id'];
}

if($_POST['func'] == 'find')
{
	find($id);
} else if($_POST['func'] == 'remove') {
	remove($id);
} else if($_POST['func'] == 'update') {
	update($id);
} else if($_POST['func'] == 'add') {
	add();
}
