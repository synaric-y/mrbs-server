<?php

declare(strict_types=1);

namespace MRBS;
use MRBS\CalendarServer\CalendarServerManager;

global $allow_registration_default, $registrant_limit_default, $registrant_limit_enabled_default;
global $registration_opens_default, $registration_opens_enabled_default, $registration_closes_default;
global $registration_closes_enabled_default, $temporary_meeting;


//according to the device, book fast meeting to the room
$device_id = $_POST['device_id'];
$begin_time = $_POST['begin_time'];
$end_time = $_POST['end_time'];
$is_charge = $_POST['is_charge'];
$battery_level = $_POST['battery_level'];
$booker = $_POST['booker'] ?? null;
$theme = $_POST['theme'] ?? null;

if (!empty($is_charge) || !empty($battery_level)) {
  if (!empty($device_id)) {
    $sql = "UPDATE " . _tbl("device") . " SET ";
    if (!empty($battery_level) && empty($is_charge)) {
      $sql .= "battery_level = ? WHERE device_id = ?";
      db()->command($sql, [$battery_level, $device_id]);
    }else if (empty($battery_level) && !empty($is_charge)) {
      $sql .= "is_charging = ? WHERE device_id = ?";
      db()->command($sql, [$is_charge, $device_id]);
    }else {
      $sql .= "battery_level = ?, is_charging = ? WHERE device_id = ?";
      db()->command($sql, [$battery_level, $is_charge, $device_id]);
    }
  }
}

//if ($now > $startTime) {
//  ApiHelper::fail();
//  return;
//}
//if ($startTime >= $endTime && (($endTime - $startTime) % (30 * 60) != 0)) {
//  ApiHelper::fail();
//  return;
//}

if ($end_time < $begin_time) {
  ApiHelper::fail(get_vocab(), ApiHelper::INVALID_END_TIME);
}

$result = db() -> query("SELECT * FROM " . _tbl("device") . " WHERE device_id = ?", array($device_id));
if($result -> count() == 0){
  ApiHelper::fail(get_vocab("device_not_exist"), ApiHelper::DEVICE_NOT_EXIST);
}
$row = $result -> next_row_keyed();
if ($row['is_set'] === 0 || empty($row['room_id'])){
  ApiHelper::fail(get_vocab("device_not_bind"), ApiHelper::DEVICE_NOT_BIND);
}
$roomId = $row['room_id'];
$qSQL = "room_id = $roomId and
    (($begin_time >= start_time and $begin_time < end_time)
    or ($end_time > start_time and $end_time <= end_time)
    or ($begin_time <= start_time and $end_time >= end_time))
    ";

$roomExist = DBHelper::one(_tbl("room"), "id = $roomId");
if (empty($roomExist)){
  ApiHelper::fail(get_vocab("room_not_exist"), ApiHelper::ROOM_NOT_EXIST);
}
if ($roomExist['disabled'] == 1) {
  ApiHelper::fail(get_vocab("room_disabled"));
  return;
}
$area = get_area_details($roomExist['area_id']);
if (!$area || $area['disabled'] == 1) {
  ApiHelper::fail(get_vocab("area_disabled"));
}
if((empty($roomExist['temporary_meeting']) && $temporary_meeting != 1) ||$roomExist['temporary_meeting'] != 1){
  ApiHelper::fail(get_vocab("unsupport_fast_meeting"));
}

$queryOne = DBHelper::one(_tbl("entry"), $qSQL);
if (!empty($queryOne)) {
  ApiHelper::fail(get_vocab("entry_conflict") , ApiHelper::ENTRY_CONFLICT);
  return;
}

$result = array();
$result["start_time"] = $begin_time;
$result["end_time"] = $end_time;
$result["entry_type"] = 99;
$result["room_id"] = $roomId;
$result["create_by"] = $booker ?? "admin";
$result["name"] = $theme ?? get_vocab("ic_tp_meeting");
$result["description"] = get_vocab("ic_tp_meeting");
$result["book_by"] = $booker ?: "/";
$result["type"] = "I";
$result["status"] = 0;
$result["ical_uid"] = generate_global_uid($result["name"]);
$result["allow_registration"] = $allow_registration_default ? 1 : 0;
$result["registrant_limit"] = $registrant_limit_default;
$result["registrant_limit_enabled"] = $registrant_limit_enabled_default  ? 1 : 0;
$result["registration_opens"] = $registration_opens_default;
$result["registration_opens_enabled"] = $registration_opens_enabled_default  ? 1 : 0;
$result["registration_closes"] = $registration_closes_default;
$result["registration_closes_enabled"] = $registration_closes_enabled_default  ? 1 : 0;
$result["create_source"] = "system";

DBHelper::insert(\MRBS\_tbl("entry"), $result);
$insertId = DBHelper::insert_id(_tbl("entry"), "id");
if (!empty($insertId)) {
  CalendarServerManager::createMeeting($insertId);
}

ApiHelper::success(array(
  "status" => 0
));
