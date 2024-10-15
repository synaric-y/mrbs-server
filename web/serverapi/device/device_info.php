<?php

declare(strict_types=1);

namespace MRBS;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

//判断用户是否具有权限
if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$result = db() -> query("SELECT * FROM " . _tbl("device"));
if ($result ->count() == 0){
  ApiHelper::success(null);
}

$devices = $result -> all_rows_keyed();
$down_set = RedisConnect::zRangeByScore("heart_beat", 0, time() - 30);
foreach ($devices as &$device){
  if (in_array($device['id'], $down_set)){
    $device['status'] = 0;
  }else{
    $device['status'] = 1;
  }
}

ApiHelper::success($devices);
