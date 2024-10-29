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

$vars = array(
  "device_id",
  'is_charging',
  'battery_start',
  'battery_end',
  'status',
  'is_set',
  'room_name',
  'set_time_start',
  'set_time_end'
);
foreach ($vars as $key => $var){
  if (isset($_POST[$var])){
    $$var = $_POST[$var];
    if ($var === 'status'){
      unset($vars[$key]);
    }
  }else{
    unset($vars[$key]);
  }
}

$pagesize = intval($_POST['pagesize']);
$pagenum = intval($_POST['pagenum']);

$sql = "SELECT COUNT(*) FROM " . _tbl("device") . " D LEFT JOIN " . _tbl("room") . " R ON D.room_id = R.id";
$params = [];
$vars = array_values($vars);
if (!empty($vars) && !(count($vars) == 1 && $vars[0] == 'status')){
  $sql .= " WHERE ";
  for ($i = 0; $i < count($vars); $i++){
    $var = $vars[$i];
    if ($var === 'status' || $$var === ''){
      continue;
    }
    if ($var !== 'battery_start' && $var !== 'battery_end' && $var !== 'set_time_start' && $var !== 'set_time_end'){
      $sql .= $var . " = ?";
    }else if ($var === 'battery_start'){
      $sql .= 'battery_level' . ' >= ?';
    }else if ($var === 'battery_end'){
      $sql .= 'battery_level' . " <= ?";
    }else if ($var === 'set_time_start'){
      $sql .= 'set_time' . ' >= ?';
    }else{
      $sql .= 'set_time' . ' <= ?';
    }
    if ($i < count($vars) - 1){
      $sql .= " AND ";
    }
    $params[] = $$var;
  }
}
$result = db() -> query1($sql, $params);
if ($result == 0){
  ApiHelper::success(["total_num" => 0]);
}

// by checking the zset of the redis, if a device do not send the heart beat in 30 seconds, then the
//    device will be considered as offline device.(Only sync_room will be record as connecting, other
//    operation will not be record into redis)
$down_set = RedisConnect::zRangeByScore(RedisKeys::$HEART_BEAT, time() - 30, time());
$total_num = $result;
$sql = str_replace("COUNT(*)", "D.*, R.room_name as room_name", $sql);
$offset = ($pagenum - 1) * $pagesize;
$sql .= " LIMIT {$offset}, {$pagesize}";
$result = db() -> query($sql, $params);
$devices = $result -> all_rows_keyed();


foreach ($devices as $key => &$device){
  if (in_array($device['device_id'], $down_set)){
    $device['status'] = 1;
  }else{
    $device['status'] = 0;
  }
  if (isset($status) && $device['status'] != $status){
    unset($devices[$key]);
  }
}

ApiHelper::success(["devices" => $devices, "total_num" => $total_num]);
