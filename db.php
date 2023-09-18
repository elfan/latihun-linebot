<?php
# Author: Elfan Nofiari (elfan@ungu.com)

include("db.class.php");
$CORE["DB"] = new db();

function db_query($query, &$data="") {
	global $CORE;
	return $CORE["DB"]->query($query, $data);
}
function db_fetch(&$data="") {
	global $CORE;
	return $CORE["DB"]->fetch($data);
}
function db_afetch(&$data="") {
	global $CORE;
	return $CORE["DB"]->afetch($data);
}
function db_data($f="") {
	global $CORE;
	return $CORE["DB"]->data($f);
}
function db_insert($table, $record) {
	global $CORE;
	return $CORE["DB"]->insert($table, $record);
}
function db_delete($table, $condition_sql="") {
	global $CORE;
	return $CORE["DB"]->delete($table, $condition_sql);
}
function db_update($table, $record, $condition_sql="") {
	global $CORE;
	return $CORE["DB"]->update($table, $record, $condition_sql);
}
function db_insert_update($table, $record) {
	global $CORE;
	return $CORE["DB"]->insert_update($table, $record);
}
function db_fetch_query($query, &$data="") {
	global $CORE;
	return $CORE["DB"]->fetch_query($query, $data);
}
function db_afetch_query($query, &$data="") {
	global $CORE;
	return $CORE["DB"]->afetch_query($query, $data);
}
function db_fetch_all() {
	global $CORE;
	return $CORE["DB"]->fetch_all();
}
function db_afetch_all() {
	global $CORE;
	return $CORE["DB"]->afetch_all();
}
function db_num_rows() {
	global $CORE;
	return $CORE["DB"]->num_rows();
}
function db_select_db($db_name) {
	global $CORE;
	return $CORE["DB"]->select_db($db_name);
}
function db_connect($db_type="", $db_host="", $db_user="", $db_pass="", $db_name="", $db_port="") {
	global $CORE;
	return $CORE["DB"]->connect($db_type, $db_host, $db_user, $db_pass, $db_name, $db_port);
}
function db_insert_id() {
	global $CORE;
	return $CORE["DB"]->insert_id();
}
function db_affected_rows() {
	global $CORE;
	return $CORE["DB"]->affected_rows();
}
function db_data_seek($n) {
	global $CORE;
	return $CORE["DB"]->data_seek($n);
}
function db_close() {
	global $CORE;
	return $CORE["DB"]->close();
}
function db_error() {
	global $CORE;
	return $CORE["DB"]->error();
}
function db_tables() {
	global $CORE;
	return $CORE["DB"]->tables();
}
function db_describe($table) {
	global $CORE;
	return $CORE["DB"]->describe($table);
}
function db_create($table, $structure) {
	global $CORE;
	return $CORE["DB"]->create($table, $structure);
}
function db_table_exists($table) {
	global $CORE;
	return $CORE["DB"]->table_exists($table);
}
function db_drop($table) {
	global $CORE;
	return $CORE["DB"]->drop($table);
}

?>
