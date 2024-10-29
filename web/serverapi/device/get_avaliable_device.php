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

$result = db() -> query("SELECT * FROM " . _tbl("device") . " WHERE is_set = 0");

if ($result -> count() < 1){
  ApiHelper::success(null);
}

$down_set = RedisConnect::zRangeByScore("heart_beat", time() - 30, time());

$data = [];
while($row = $result -> next_row_keyed()){
  if (in_array($row['device_id'], $down_set)){
    $data[] = $row;
  }
}

ApiHelper::success(empty($data) ? null : $data);
