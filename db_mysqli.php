<?php

class db_mysqli {
	public $db_link;
	public $db_name;
	public $db_result;

	function db_mysqli() {
	}

	function db_check() {
		return function_exists("mysqli_connect");
	}
	
	function db_port() {
		return "3306";	//1433 for mssql
	}

	function db_connect($db_host, $db_user, $db_pass, $db_name, $db_port) {
		$this->db_link = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
		return $this->db_link;
	}

	function db_select_db($db_name) {
		$this->db_name = $db_name;
		return mysqli_select_db($this->db_link, $db_name);
	}

	function db_close() {
		return mysqli_close($this->db_link);
	}

	function db_query($query) {
		$this->db_result = mysqli_query($this->db_link, $query);
		return $this->db_result;
	}

	function db_insert_id() {
		return mysqli_insert_id($this->db_link);
	}

	function db_num_rows() {
		return mysqli_num_rows($this->db_result);
	}

	function db_affected_rows() {
		return mysqli_affected_rows($this->db_result);
	}

	function db_data_seek($n) {
		return mysqli_data_seek($this->db_result, $n);
	}

	function db_fetch_assoc() {
		return mysqli_fetch_assoc($this->db_result);
	}

	function db_fetch_object() {
		return mysqli_fetch_object($this->db_result);
	}

	function db_date($time) {
		return date("Y-m-d H:i:s", $time);
	}

  function db_error() {
    if ($this->db_link === false) {
      return mysqli_connect_error();
    }
    return mysqli_error($this->db_link);
  }

	function db_tables() {
		$tables = array();
		$result = mysqli_query($this->db_link, "show tables");
		while ($row = mysqli_fetch_row($result)) {
			$tables[] = $row[0];
		}
		return $tables;
	}

	function db_describe($table) {
		$desc = array();
		$result = mysqli_query($this->db_link, "describe `".$table."`");
		while ($row = mysqli_fetch_assoc($result)) {
			$desc[] = array("field"		=> $row["Field"],
							"type"		=> $row["Type"],
							"null"		=> (preg_match("/^yes|true|1$/i", $row["Null"]) ? "YES" : ""),
							"key"		=> $row["Key"],
							"default"	=> $row["Default"],
							"autoincrement"	=> (strstr($row["Extra"], "auto_increment") ? "YES" : ""),
							"extra"		=> $row["Extra"]);
		}
		return $desc;
	}

  function db_create($table, $structure) {
    $str = '';
    $primary_key = [];
    foreach ($structure as $field => $info) {
      if ($info['type'] == 'text') {  //type text
        $type = 'varchar(255)';
      }
      else if ($info['type'] == 'int') {  //type int
        //https://dev.mysql.com/doc/refman/5.1/en/integer-types.html
        $ranges = array('tinyint' => 256, 'smallint' => 65536, 'mediumint' => 16777216, 'int' => 4294967296, 'bigint' => 18446744073709551615);
        $type = '';
        if (isset($info['range']) && is_array($info['range'])) {  //specified range
          if (isset($info['range']['min']) && $info['range']['min'] >= 0) {  //unsigned
            if (isset($info['range']['max'])) { //specified max
              foreach ($ranges as $k => $v) {
                if ($info['range']['max'] < $v) {
                  $type = $k . ' unsigned';
                  break;
                }
              }
              if (!$type) { //too large
                $type = $k . ' unsigned';  //use the largest type
                error_log('Field ' . $field . ' has too large max range: ' . $info['range']['max']);
              }
            }
            else {  //unspecified max
              $type = 'int unsigned';
            }
          }
          else {  //signed
            if (isset($info['range']['max'])) { //specified max
              foreach ($ranges as $k => $v) {
                if ($info['range']['max'] < $v / 2) {
                  $type = $k;
                  break;
                }
              }
              if (!$type) { //too large
                $type = $k;  //use the largest type
                error_log('Field ' . $field . ' has too large max range: ' . $info['range']['max']);
              }
            }
            else {  //unspecified max
              $type = 'int';
            }
          }
        }
        else {  //unspecified range
          $type = 'int';  //default
        }
      }
      else if ($info['type'] == 'autoid') {  //type autoid
        $type = 'int unsigned auto_increment';
        $primary_key[] = '`'.$field.'`';
      }
      else {
        $type = $info['type'];  //use the type as it is
      }

      if (@$info['key']) {
        $primary_key[] = '`'.$field.'`';
      }

      $str .= '`' . $field . '`';
      $str .= ' ' . $type;
      if (@$info['mandatory'] || $info['type'] == 'autoid') $str .= ' not null';
      $str .= ',';
    }
    if (sizeof($primary_key) > 1) {
      $str .= " constraint PK_" . $table . " primary key (".implode(",", $primary_key)."),";
    }
    else if (sizeof($primary_key) > 0) {
      $str .= " primary key (" . $primary_key[0] . "),";
    }

    $str = 'create table if not exists `' . $table . '` (' . substr($str, 0, -1) . ');';
    echo $str;
    $result = mysqli_query($this->db_link, $str);
    if (is_bool($result) && $result) {
      return true;
    }
    else {
      echo mysqli_error($this->db_link);
    }
  }

  function db_table_exists($table) {
    $result = mysqli_query($this->db_link, "show tables like '". $table . "';");
    return (mysqli_num_rows($result) > 0);
  }

  function db_drop($table) {
    $str = "drop table if exists `". $table . "`;";
    $result = mysqli_query($this->db_link, $str);
    if (is_bool($result) && $result) {
      return true;
    }
    else {
      echo mysqli_error($this->db_link);
    }
  }

}

?>
