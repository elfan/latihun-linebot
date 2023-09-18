<?php
# Author: Elfan Nofiari (elfan@ungu.com)

class db {
	private $config = array();
	private $db_object = array();
	private $mode = "master";
	public $master_slave = false;	//master for RW and slave for R only
	public $data = array();
	private $config_file = "db_config.php";
	private $db_configs;
	private $current_config;
	private $last_sql = "";

	function db($db_type="", $db_host="", $db_user="", $db_pass="", $db_name="", $db_port="") {
		//db connection setting could be set from parameter, from a given config file name, or default config file.
		//If there is no param, setting will be loaded from default config file.
		//If param is only one, it is considered as a custom config file name.
		//If param is more than one, setting will be derived from the parameters.

		$use_custom_file = false;
		if (func_num_args()==1) {
			if (!file_exists($db_type)) {
				die("Database configuration file \"".$db_type."\" is not found.");
			}
			$this->config_file = $db_type;
			$use_custom_file = true;
			$db_type = "";
		}
		if (!$db_type) {
			//load database configuration file, and load it into session
			if (!$use_custom_file) {
				if (@constant("DB_CONFIG_DIR"))	//if it is defined, use the default config file from that directory
					$this->config_file = preg_replace("/\/$/", "", DB_CONFIG_DIR)."/".$this->config_file;
				else	//load from the same directory as this file
					$this->config_file = preg_replace("/".preg_quote(basename(__FILE__))."$/", $this->config_file, __FILE__);
			}
			$mtime = @filemtime($this->config_file);
			$this->config = array();
			if (isset($_SESSION["db_config".$mtime])) {
				$this->config = $_SESSION["db_config".$mtime];
			}
			else {
				$rewrite = $this->read_config();
				if (!@$this->config["DB_TYPE"]) {
					$this->config = array(
											"DB_TYPE" => "mysqli",
											"DB_HOST" => "localhost",
											"DB_PORT" => "",
											"DB_USER" => "",
											"DB_PASS" => "",
											"DB_NAME" => "",
									);
				}

				if ($rewrite) {
					$this->save_config();
					$mtime = filemtime($this->config_file);
				}
				$_SESSION["db_config".$mtime] = $this->config;
			}
		}
		else {
			//custom runtime configuration
			$this->config = array(	"DB_TYPE" => $db_type,
									"DB_HOST" => $db_host,
									"DB_PORT" => $db_port,
									"DB_USER" => $db_user,
									"DB_PASS" => $db_pass,
									"DB_NAME" => $db_name);
		}
		if (!@include_once(preg_replace("/".preg_quote(basename(__FILE__))."$/", "db_".$this->config["DB_TYPE"].".php", __FILE__))) {
			die("Database plugin for \"".$this->config["DB_TYPE"]."\" (db_".$this->config["DB_TYPE"].".php) is required.");
		}
	}

	private function read_config() {
		$config = array();
		$rewrite = true;
		if (@include($this->config_file)) {
			$host = preg_replace("/^www\./i", "", strtolower(@$_SERVER["SERVER_NAME"]));
			if (isset($config[$host])) {
				$this->config = $config[$host];
				$this->current_config = $host;
			}
			else if (isset($config["default"])) {
				$this->config = $config["default"];
				$this->current_config = "default";
			}
			else {
				$this->config = @$config;
				$this->current_config = "";
			}
			$this->db_config = @$config;
			if (@$this->config["DB_TYPE"]) {
				$rewrite = false;
				foreach ($this->config as $key=>$value) {
					if (($key == "DB_USER") || ($key == "DB_PASS")) {
						if (preg_match("/^\((.*?)\)$/", $value, $match)) {
							$this->config[$key] = $match[1];
							$rewrite = true;
						}
						else {
							if (function_exists("decrypt"))
								$this->config[$key] = decrypt($value, "", false, true);
							else
								$this->config[$key] = $this->endecrypt($value, true);
						}
					}
				}
			}
		}
		return $rewrite;
	}

	private function save_config() {
		$ini_file = $this->config_file;
		if ($fp = @fopen($ini_file, "w")) {
			$str = "<"."?php\n// The DB_USER and DB_PASS of this database configuration setting is encrypted for security purpose.\n";
			$str .= "// To manually change the value, replace the existing value with the new plain text value surrounded by parentheses.\n";
			$str .= "// For example, \"DB_USER\" => \"(admin)\",\n";
			$str .= "// Those plain text values (which surrounded by parentheses) will be automatically re-encrypted on the next database access.\n\n";

			$host = preg_replace("/^www\./i", "", strtolower($_SERVER["SERVER_NAME"]));
			if ($this->current_config) {
				$str .= "\$config = array();\n\n";
				foreach ($this->db_config as $k=>$v) {
					$str .= "\$config[\"".$k."\"] = array(\n";
					if ($k == $this->current_config) {
						foreach ($this->config as $key=>$value) {
							if (($key == "DB_USER") || ($key == "DB_PASS")) {
								if (function_exists("encrypt")) {
									$value = encrypt($value, "", false, true);
								}
								else {
									$value = $this->endecrypt($value);
								}
							}
							$str .= "\t\"".$key."\" => \"".$value."\",\n";
						}
					}
					else {
						foreach ($this->db_config[$k] as $key=>$value) {
							$str .= "\t\"".$key."\" => \"".$value."\",\n";
						}
					}
					$str .= ");\n";
				}
			}
			else {
				$str .= "\$config = array(\n";
				foreach ($this->config as $key=>$value) {
          if (($key == "DB_USER") || ($key == "DB_PASS")) {
            if (function_exists("encrypt")) {
              $value = encrypt($value, "", false, true);
            }
            else {
              $value = $this->endecrypt($value);
            }
          }
					$str .= "\t\"".$key."\" => \"".$value."\",\n";
				}
				$str .= ");\n";
			}
			fwrite($fp, $str);
			fclose($fp);
			return true;
		}
		return false;
	}

	function connect($db_type="", $db_host="", $db_user="", $db_pass="", $db_name="", $db_port="") {
		if (!$db_type) {
			$db_host = @$this->config["DB_HOST"];
			$db_port = @$this->config["DB_PORT"];
			$db_user = @$this->config["DB_USER"];
			$db_pass = @$this->config["DB_PASS"];
			$db_name = @$this->config["DB_NAME"];
			if ($this->mode == "slave") {
				if (@$this->config["DBS_HOST"]) $db_host = @$this->config["DBS_HOST"];
				if (@$this->config["DBS_PORT"]) $db_port = @$this->config["DBS_PORT"];
				if (@$this->config["DBS_USER"]) $db_user = @$this->config["DBS_USER"];
				if (@$this->config["DBS_PASS"]) $db_pass = @$this->config["DBS_PASS"];
				if (@$this->config["DBS_NAME"]) $db_name = @$this->config["DBS_NAME"];
			}
		}

		if ($type = @$this->config["DB_TYPE"]) {
			$obj = "db_".$type;
			if (class_exists($obj)) {
				$this->{$this->mode} = new $obj();
				$this->plugin =& $this->{$this->mode};
				if (!$db_port) {
					$db_port = $this->plugin->db_port();
				}
				if ($this->plugin->db_check()) {	//check whether PHP lib for the database is already installed
					if (!$this->plugin->db_connect($db_host, $db_user, $db_pass, $db_name, $db_port)) {
            $error = $this->plugin->db_error();
            if (preg_match("/denied/i", $error)) {
              $error = "Access denied";
            }
            else if (preg_match("/no such host/i", $error)) {
              $error = "Unknown host";
            }
            else if (preg_match("/refused/i", $error)) {
              $error = "Connection refused";
            }
						die("Cannot connect to \"".$type."\": " . $error);
					}
				}
				else {
					die("PHP library is required to connect to \"".$type."\" database");
				}
			}
			else {
				die("Database plugin for \"".$type."\" is required.");
			}
		}
		else {
			die("Database server type is not defined.");
		}
		return $this->plugin->db_link;
	}

	function close() {
		return $this->plugin->db_close();
	}

	function select_db($db_name) {
		return $this->plugin->db_select_db($db_name);
	}

	function check_connection() {
		if (!isset($this->{$this->mode}) || !$this->{$this->mode}->db_link)	{
			$this->connect();
		}
	}

	function query($query, &$data="") {
		$this->last_sql = $query;
		if (!$this->master_slave || preg_match("/^\s*(insert|update|delete)\s/i", $query)) {	//write to database
			$this->mode = "master";
		}
		else {
			$this->mode = "slave";
		}
		$this->check_connection();	//connect if it's not connected yet

		$this->check_sql_injection($query);

		if ($this->plugin->db_query($query)) {
			if (($data!=="") && preg_match("/^\s*(select|show)\s/i", $query)) {
				$data = $this->fetch_all();
			}
		}
		return $this;
	}

  function _construct_keys_values($record) {
		$fields = "";
		$values = "";
		foreach ($record as $key=>$value) {
			$fields .= ",\n  `".$key."`";
			if ($value===null) {
				$values .= ",\n  null";
			}
			else {
				$value = addcslashes($value, "'\\");
				$values .= ",\n  '".$value."'";
			}
		}
		$fields = substr($fields, 2);
		$values = substr($values, 2);
		return "(\n".$fields."\n)\nvalues (\n".$values."\n)";
  }

  function _construct_set_values($record) {
		$sets = "";
		foreach ($record as $key=>$value) {
			if ($value===null) {
				$sets .= ",\n  `".$key."`=null";
			}
			else {
				$value = addcslashes($value, "'\\");
				$sets .= ",\n  `".$key."`='".$value."'";
			}
		}
		$sets = substr($sets, 2);
    return $sets;
  }

  function insert_update($table, $record) {
		$sql = "insert into `".$table."` " . $this->_construct_keys_values($record) . "\non duplicate key update\n" . $this->_construct_set_values($record);
		$this->last_sql = $sql;
		return $this->query($sql);
  }

	function insert($table, $record) {
		$sql = "insert into `".$table."`\n" . $this->_construct_keys_values($record);
		$this->last_sql = $sql;
		return $this->query($sql);
	}

	function update($table, $record, $condition_sql="") {
    $sets = $this->_construct_set_values($record);
		if ($sets) {
			$sql = "update `".$table."` set\n".$sets.($condition_sql ? "\nwhere ".$condition_sql : "");
			$this->last_sql = $sql;
			return $this->query($sql);
		}
	}

	function delete($table, $condition_sql="") {
		$sql = "delete from `".$table."`".($condition_sql ? " where ".$condition_sql : "");
		$this->last_sql = $sql;
		return $this->query($sql);
	}

	function date($time="") {
		if (!$time) $time = time();
		return $this->plugin->db_date($time);
	}

	function fetch(&$data="") {
		if ($this->plugin->db_result) {
			$this->data = $this->plugin->db_fetch_object();
			$data = $this->data;
			return $this->data;
		}
	}

	function fetch_query($query, &$data="") {
		if ($this->query($query, $data)) {
			return $this->fetch_all();
		}
		return array();
	}

	function fetch_all() {
		$result = array();
		while ($fetch = $this->plugin->db_fetch_assoc()) {
			$result[] = $fetch;
		}
		if ($result)
			$this->data = $result[sizeof($result)-1];
		return $result;
	}

	function afetch(&$data="") {
		if ($this->plugin->db_result) {
			$this->data = $this->plugin->db_fetch_assoc();
			$data = $this->data;
			return $this->data;
		}
	}

	function afetch_query($query, &$data="") {
		if ($this->query($query, $data)) {
			return $this->afetch_all();
		}
		return array();
	}

	function afetch_all() {
		$result = array();
		while ($fetch = $this->plugin->db_fetch_assoc()) {
			$result[] = $fetch;
		}
		if ($result)
			$this->data = $result[sizeof($result)-1];
		return $result;
	}

	function insert_id() {
		return $this->plugin->db_insert_id();
	}

	function num_rows() {
		if ($this->plugin->db_result) {
			return $this->plugin->db_num_rows();
		}
	}

	function affected_rows() {
		if ($this->plugin->db_result) {
			return $this->plugin->db_affected_rows();
		}
	}

	function data_seek($n) {
		$this->plugin->db_data_seek($n);
	}

	function data($f="") {
		return ($f ? $this->data[$f] : $this->data);
	}

	function error() {
		if (isset($this->plugin) && $this->plugin->db_link) {
			return $this->plugin->db_error();
		}
	}

	function tables() {
		$this->check_connection();	//connect if it's not connected yet
		return $this->plugin->db_tables();
	}

	function describe($table) {
		$this->check_connection();	//connect if it's not connected yet
		return $this->plugin->db_describe($table);
	}

	function sql() {
		return $this->last_sql;
	}

	private function endecrypt($data, $decrypt=false) {
		$shift = 38;
		$from = "lf@zTa=28wXL&^hgS?cJBkj9#7!CKq.e,%Irtd3+VmYoypv:61n_WAsEbNHi0ZxPGUORQ5-Du4FM";
		$to = substr($from, $shift).substr($from, 0, $shift);
		return ($decrypt ? strtr($data, $to, $from) : strtr($data, $from, $to));
	}

	private function check_sql_injection($query) {
		static $params = null;
		$patt = "union|;|--|#|/\\*|0x[0-9a-f]+|drop|shutdown|schema";
		if (preg_match("{(".$patt.")}i", $query)) {
			if ($params === null) {
				foreach ($_GET as $k=>$v) {
					$params .= $v." ";
				}
				foreach ($_POST as $k=>$v) {
					$params .= $v." ";
				}
			}
			if (preg_match("{(".$patt.")}i", $params)) {
				echo "Possible SQL injection detected";
				exit;
			}
		}
	}

  function create($table, $structure) {
    if (!$structure) {
      echo 'Structure cannot be empty in creating table "' . $table .'"';
    }
    else if (is_string($structure)) {
      $structure = json_decode($structure, true);
    }
    $this->check_connection();
    return $this->plugin->db_create($table, $structure);
  }

  function table_exists($table) {
    $this->check_connection();
		return $this->plugin->db_table_exists($table);
	}

  function drop($table) {
    $this->check_connection();
    return $this->plugin->db_drop($table);
  }

}

?>
