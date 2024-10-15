<?php


declare(strict_types=1);

namespace MRBS;

global $now_version;


$device_id = $_POST['device_id'];
$version = $now_version;
$description = $_POST['description'];
$resolution = $_POST['resolution'];
$is_charge = $_POST['is_charge'];
$battery_level = $_POST['battery_level'];
$room_id = $_POST['room_id'] ?? false;

if ($room_id === false){
  $status = 0;
}else
  $status = 1;

$result = db()->query1("SELECT COUNT(*) FROM " . _tbl("device") . " WHERE device_id = ?", array($device_id));
if ($result > 0){
  ApiHelper::fail(get_vocab("device_exists"), ApiHelper::DEVICE_EXISTS);
}

$sql = "INSERT INTO " . _tbl("device") . "(device_id, version, description, resolution, is_charge, battery_level, status, room_id) VALUES (?, ?, ?, ?, ?, ?)";
db() -> command($sql, array($device_id, $version, $description, $resolution, $is_charge, $battery_level, $status, $room_id === false ? null : $room_id));

ApiHelper::success(null);
