<?php


declare(strict_types=1);

namespace MRBS;

global $now_version;

use function GuzzleHttp\describe_type;

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

if (empty($device_id)){

}

$sql = "INSERT INTO " . _tbl("device") . "(device_id, version, description, resolution, is_charge, battery_level, status, room_id) VALUES (?, ?, ?, ?, ?, ?)";
db() -> command($sql, array($device_id, $version, $description, $resolution, $is_charge, $battery_level, $status, $room_id));

ApiHelper::success(null);
