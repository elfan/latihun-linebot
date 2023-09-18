<?php

include("db.php");
mysqldb();


function mysqldb() {
  db_drop('user');
  db_drop('system');
  db_create('user', table_structure('user'));
  db_create('system', table_structure('system'));
  print_r(db_describe('user'));
  print_r(db_describe('system'));
}

function table_structure($table_name) {
  if ($table_name == 'user') {
    $st = '{
      "user_id":        {"type": "text", "key": true},
      "key":            {"type": "text", "searchable": true, "key": true},
      "value":          {"type": "varchar(32767)"},
      "expiry":         {"type": "datetime"}
    }';
  }
  else if ($table_name == 'system') {
    $st = '{
      "system_id":      {"type": "text", "key": true},
      "key":            {"type": "text", "searchable": true, "key": true},
      "value":          {"type": "varchar(32767)"},
      "expiry":         {"type": "datetime"}
    }';
  }
  return json_decode($st, true);
}
