<?php
set_error_handler("myErrorHandler");

function myErrorHandler($errno, $errstr, $errfile="", $errline="") {
  global $LINE, $redis;

  $err = "Error " . $errno . " " . $errstr . ($errfile ? " in ".preg_replace("{/var/.+?/line/}", "", $errfile) .":".$errline : "");
  debug($err);

  if (!$redis->get("ERR:".$err)) {
    notifyAdmin($err);
  }
  $redis->setEx("ERR:".$err, 1 * 60, true);
  return false;
}

function is_assoc($array) {
  return is_array($array) && (bool)count(array_filter(array_keys($array), 'is_string'));
}

function debug($str, $file="") {
  //return;
  if (is_assoc($str) || is_object($str)) $str = var_export($str, true);
  if (is_array($str)) $str = implode("\n", $str);
  if (trim($str)) $str = '['.date('Y-m-d H:i:s').'] ' . $str . "\n";
  if (!$file) {
    $file = 'log.txt';
  }
  if ($f = fopen($file, 'a')) {
    fwrite($f, $str);
  	fclose($f);
  }
  if (rand(0, 19) == 0) {
    if (filesize($file) > 1000000) {
      $archive = preg_replace("/\.txt$/", ".".date("YmdHis", time()).".bak", $file);
      rename($file, $archive);
    }
  }
}

function notifyAdmin($msg) {
    global $LINE, $adminId;
    $LINE->sendResponses([$msg], $adminId); //enof
}