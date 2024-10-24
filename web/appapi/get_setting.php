<?php


declare(strict_types=1);

namespace MRBS;

/*
 * get the binding information of a device, and update the battery level
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

$device_id = $_POST['device_id'];
$battery_level = $_POST['battery_level'];
$is_charge = $_POST['is_charge'];

db()->command("UPDATE " . _tbl("device") . " SET battery_level = ?, is_charging = ? WHERE device_id = ?", array($battery_level, $is_charge, $device_id));

$result = db()->query("SELECT * FROM " . _tbl("device") . " WHERE device_id = ?", array($device_id));
if ($result -> count() == 0){
  ApiHelper::fail(get_vocab("device_not_exist"), ApiHelper::DEVICE_NOT_EXIST);
}

$row = $result -> next_row_keyed();
$result = db()->query("SELECT * FROM " . _tbl("room") . " R LEFT JOIN "  . _tbl("area") . " A ON R.area_id = A.id WHERE R.id = ?", array($row['room_id']));
if ($result -> count() == 0){
  ApiHelper::fail(get_vocab("room_not_exist"), ApiHelper::ROOM_NOT_EXIST);
}
$room = $result -> next_row_keyed();

ApiHelper::success(["room" => $room['room_name'], "area" => $room['area_name'], "is_set" => $row['is_set']]);
