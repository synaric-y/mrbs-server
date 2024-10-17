<?php

declare(strict_types=1);

namespace MRBS;


$device_id = $_POST["device_id"];
$room_id = $_POST["room_id"] ?? false;
$is_charging = $_POST["is_charging"];
$battery_level = $_POST["battery_level"];


if ($room_id === false){
  ApiHelper::fail(get_vocab("invalid_room_id"), ApiHelper::INVALID_ROOM_ID);
}

$result = db()->query1("SELECT COUNT(*) FROM " . _tbl("device") . " WHERE device_id = ?", array($device_id));
if ($result < 1){
  ApiHelper::fail(get_vocab("device_not_exist"), ApiHelper::DEVICE_NOT_EXIST);
}

db()->command("UPDATE " . _tbl("device") . " SET room_id = ?, is_charging = ?, battery_level = ?, is_set = ? WHERE device_id = ?", array($room_id, $is_charging, $battery_level, empty($room_id) ? 0 : 1, $device_id));

ApiHelper::success(null);
