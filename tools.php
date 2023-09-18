<?php
function any($arr, $count=1) {
  if ($count <= 0) {  //invalid count
    return false;
  }

  $is_numeric = is_numeric($arr);
  $len = ($is_numeric ? $arr : @sizeof($arr));
  if ($len <= 0) {  //empty arr
    return ($count == 1 ? false : []);
  }

  if ($count == 1) {  //single output
    if ($len == 1) {
      $idx = 0;
    }
    else {
      $idx = mt_rand(0, $len - 1);
    }
    return ($is_numeric ? $idx : revex($arr[$idx]));
  }
  else {  //multi output
    $result = [];
    $map = [];
    $min = 0;
    $max = $len - 1;
    do {
      $idx = mt_rand($min, $max);
      if (isset($map[$idx])) {
        $idx = $map[$idx];
      }
      $result[] = ($is_numeric ? $idx : revex($arr[$idx]));
      $map[$idx] = $min;
      $min++;
      if ($min == $count || $min > $max) {
        break;
      }
    } while (true);
    return $result;
  }
}


function revex($pattern) {
  if (is_array($pattern) || !preg_match("/^([\/!#&^~]).*\\1$/", $pattern)) {  //regex pattern must be enclosed by a pair of delimiters
    return $pattern;
  }

  $pattern = substr($pattern, 1, -1); //remove the first and last characters

  do {
    $initial = $pattern;
    $pattern = preg_replace_callback("/\((([^()|]*)(?:\|(?2))*)\)(\??)/", function($matches) {
      if (!$matches[3] || mt_rand(0, 1)) {
        $arr = preg_split("/\|/", $matches[1]);
        return $arr[ mt_rand(0, count($arr) - 1) ];
      }
      return '';
    }, $pattern);
  } while ($pattern != $initial);

  $pattern = preg_replace_callback("/(\\\\?.)\?/", function($matches) {
    $char = $matches[1];
    if ($char == "\\") {  //escaped question mark
      return "?";
    }
    else if (strlen($char) > 1) {  //an escaped character
      return (mt_rand(0, 1) ? substr($char, -1) : '');
    }
    else {  //any other unescaped character
      if ($char == '?' || $char == '*' || $char == '+') { //lazy quantifier
        return '';  //ignore
      }
      else {
        return (mt_rand(0, 1) ? $char : '');
      }
    }
  }, $pattern);

  return $pattern;
}


function evalMath($str, $prePatt='', $postPatt='') {
  $str = preg_replace('/^'.$prePatt.'/i', '', $str);
  $str = preg_replace("/,/", ".", $str);

  $number = '(?:-?\d+(?:[,.]\d+)?|pi|π)'; // What is a number
  $functions = '(?:a?sinh?|a?cosh?|a?tanh?|abs|log|ln|deg2rad|rad2deg|sqrt|ceil|floor|round)'; // Allowed PHP functions
  $operators = '[+\/*xX×\^%-:]'; // Allowed math operators

  if (!preg_match('/'.$functions.'/', $str) && !preg_match('/'.$operators.'/', $str)) {
    return false;
  }

  $regexp = '/^( *('.$number.'|\b'.$functions.' *\((?1)\)|\b'.$functions.' *'.$number.'|\((?1)\))(?: *'.$operators.' *(?2))* *)'.$postPatt.'$/'; // Final regexp, heavily using recursive patterns

  if (preg_match($regexp, $str, $match)) {
      $str = $match[1];
      $str = preg_replace('/\b('.$functions.') *('.$number.') */', '$1($2)', $str); // Replace pi with pi function
      $str = preg_replace('/\b(a?sinh?|a?cosh?|a?tanh?) *\( *('.$number.') *\)/', '$1(deg2rad($2))', $str); // convert rad with deg
      $str = preg_replace('/pi|π/i', 'pi()', $str); // Replace pi with pi function
      $str = preg_replace('/log *\(/i', 'log10(', $str); // Replace log with log10
      $str = preg_replace('/ln *\(/i', 'log(', $str); // Replace ln with log
      $str = preg_replace('/[x|×]/i', '*', $str); // Replace x with *
      $str = preg_replace('/:/', '/', $str); // Replace : with /
      $str = preg_replace('/,/', '.', $str); // Replace comma with dot
      $str = preg_replace('/\^/', '**', $str); // Replace ^ with **
      error_log($str);
      $result = eval('return '.$str.';');
      if (strpos((string)$result, ".") !== false) {
        $result = round($result, 4);
        if (abs($result - round($result)) < 0.0001) {
          $result = (int)round($result);
        }
        $result = preg_replace('/\./', ',', $result); // Replace dot with comma (for indonesian)
      }
      else {
        $result = (int)$result;
      }
  }
  else {
      $result = false;
  }
  return $result;
}


function exportDb() {
  global $redis;
  $keys = $redis->keys('*');
  foreach ($keys as $k) {
    vset($k, vget($k));
  }
}

function cleanEmptyLevel() {
  global $redis;
  vdel('user/U*/count_answered_correct/level_/*');
}


function log_request() {
	$input = trim(file_get_contents('php://input'));
	$log =  ($input ? "INPUT = ".$input. "\n" : '');
	debug($log);
}

function log_conversation($userId, $message) {
  $file = preg_replace("/\W/", "", $userId);
  $file = 'userdata/user.'.$file.'.txt';
	debug($message, $file);
}


function testRandom() {
  $arr = ['a', 'b', 'c', 'd'];
  $count = [];
  for ($i = 1; $i < 10000; $i++) {
    @$count[ any($arr) ]++;
  }
  $str = implode(",", $count);


  $count = [];
  for ($i = 1; $i < 10000; $i++) {
    $set = any($arr, 2);
    for ($j = 0; $j < sizeof($set); $j++) {
      @$count[ $set[$j].$j ]++;
    }
  }
  ksort($count);
  foreach ($count as $k => $v) {
    $str .= "\n". $k . "=" . $v;
  }


  $count = [];
  for ($i = 1; $i < 10000; $i++) {
    @$count[ any(4) ]++;
  }
  $str .= "\n".implode(",", $count);

  
  $count = [];
  for ($i = 1; $i < 10000; $i++) {
    $set = any(4, 2);
    for ($j = 0; $j < sizeof($set); $j++) {
      @$count[ $set[$j].$j ]++;
    }
  }
  ksort($count);
  foreach ($count as $k => $v) {
    $str .= "\n". $k . "=" . $v;
  }
  return $str;
}


function vget($path) {
  global $redis;
  list($tab, $id, $key) = explode("/", $path, 3);
  if (preg_match("/\*/", $id) || preg_match("/\*/", $key)) {
    $val = [];
    $idFilter = (preg_match("/\*/", $id) ? " like '".preg_replace("/\*/", "%", $id)."'" : " = '".$id."'");
    $keyFilter = (preg_match("/\*/", $key) ? " like '".preg_replace("/\*/", "%", $key)."'" : " = '".$key."'");
    db_query("select * from `" . $tab ."` where `".$tab."_id`". $idFilter . " and `key` ". $keyFilter, $data);
    if (sizeof($data) > 0) {
      foreach ($data as $row) {
        $rowPath = $tab.'/'.$id.'/'.$row['key'];
        $val[ $rowPath ] = $row['value'];

        $redis->setEx($rowPath, REDIS_EXP, $row['value']);
      }
    }
  }
  else {
    $val = $redis->get($tab.'/'.$id.'/'.$key);
    if ($val === false) {
      db_query("select * from `" . $tab ."` where `".$tab."_id`='".$id."' and `key`='".$key."'", $data);
      if (sizeof($data) > 0) {
        $val = $data[0]['value'];
        $redis->setEx($path, REDIS_EXP, $val);
      }
      else {
        $val = false;
      }
    }
  }
  return $val;
}

function vset($path, $val) {
  global $redis;
  list($tab, $id, $key) = explode("/", $path, 3);
  db_insert_update($tab, [$tab."_id" => $id, 'key' => $key, 'value' => $val]);
  $redis->setEx($path, REDIS_EXP, $val);
}

function vinc($path, $inc=1) {
  global $redis;
  $val = vget($path);
  if (!$val) $val = 0;
  $val += $inc;
  vset($path, $val);
}

function vdec($path, $dec=1) {
  global $redis;
  $val = vget($path);
  if (!$val) $val = 0;
  $val -= $dec;
  vset($path, $val);
}

function vdel($path) {
  global $redis;
  list($tab, $id, $key) = explode("/", $path, 3);
  if (preg_match("/\*/", $id) || preg_match("/\*/", $key)) {
    $idFilter = (preg_match("/\*/", $id) ? " like '".preg_replace("/\*/", "%", $id)."'" : " = '".$id."'");
    $keyFilter = (preg_match("/\*/", $key) ? " like '".preg_replace("/\*/", "%", $key)."'" : " = '".$key."'");
    db_delete($tab, "`".$tab."_id`". $idFilter . " and `key`" . $keyFilter);
    $keys = $redis->keys($path);
    foreach ($keys as $k) {
      $redis->del($k);
    }
  }
  else {
    db_delete($tab, "`".$tab."_id`='".$id."' and `key`='".$key."'");
    $redis->del($path);
  }
}

function vdump() {
  global $redis;
  db_query("select * from `user`", $data);
  $result = [];
  if (sizeof($data) > 0) {
    foreach ($data as $row) {
      $result[] = $row['key']."=".$row['value'];
    }
  }
  $result[] = '----';

  $keys = $redis->keys("user/*");
  foreach ($keys as $key) {
    $nkey = preg_replace("{^(.*?\/).*?\/(.*)}", "\\2", $key);
    $result[] = $nkey."=".$redis->get($key);
  }
  return implode("\n", $result);
}
