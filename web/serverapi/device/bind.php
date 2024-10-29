<?php

declare(strict_types=1);

namespace MRBS;

/*
 * bind device from room
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

// whether the user have the access to checking the device information
if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$device_id = $_POST['device_id'];
$room_id = $_POST['room_id'];
if (empty($device_id) || empty($room_id)){
  ApiHelper::fail(get_vocab('missing_parameters'), ApiHelper::MISSING_PARAMETERS);
}

//check if device exists
$device = db() -> query("SELECT COUNT(*) FROM " . _tbl("device") . " WHERE device_id = ?", array($device_id));
if ($device < 1){
  ApiHelper::fail(get_vocab('device_not_exist'), ApiHelper::DEVICE_NOT_EXIST);
}

//check if room exists
$room = db() -> query1("SELECT COUNT(*) FROM " . _tbl("room") . " WHERE room_id = ?", array($room_id));
if ($room < 1){
  ApiHelper::fail(get_vocab("room_not_exist"), ApiHelper::ROOM_NOT_EXIST);
}

//check if device is online
$down_set = RedisConnect::zRangeByScore("heart_beat", time() - 30, time());
if (!in_array($device, $down_set)){
  ApiHelper::fail(get_vocab('device_down'), ApiHelper::DEVICE_DOWN);
}

db() -> command("UPDATE " . _tbl("device") . " SET room_id = ? WHERE room_id = ?", array($room_id, $device_id));

ApiHelper::success(null);
