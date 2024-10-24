<?php

declare(strict_types=1);

namespace MRBS;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

// whether the user have the access to checking the device information
if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$result = db() -> query("SELECT * FROM " . _tbl("device"));
if ($result ->count() == 0){
  ApiHelper::success(null);
}

// by checking the zset of the redis, if a device do not send the heart beat in 30 seconds, then the
//    device will be considered as offline device.(Only sync_room will be record as connecting, other
//    operation will not be record into redis)
$devices = $result -> all_rows_keyed();
$down_set = RedisConnect::zRangeByScore(RedisKeys::$HEART_BEAT, time() - 30, time());
foreach ($devices as &$device){
  if (in_array($device['id'], $down_set)){
    $device['status'] = 1;
  }else{
    $device['status'] = 0;
  }
}

ApiHelper::success($devices);
