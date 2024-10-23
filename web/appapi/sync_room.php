<?php

declare(strict_types=1);
namespace MRBS;


global $datetime_formats, $show_book, $show_meeting_name;


function getTimeZoneByRoom($roomId)
{
  $sql = "SELECT timezone FROM " .\MRBS\_tbl("room") . " E LEFT JOIN " . \MRBS\_tbl("area")
    . " F ON E.area_id = F.id WHERE E.id = ?";
  $result = db() -> query($sql, array($roomId));
  $result = $result->next_row_keyed();
  return $result['timezone'];
}

//$room_id = intval(ApiHelper::value("room_id"));
$device_id = $_POST['device_id'];
$is_charge = $_POST['is_charge'];
$battery_level = $_POST['battery_level'];
$result = db() -> query("SELECT * FROM " . _tbl("device") . " WHERE device_id = ?", array($device_id));
if($result -> count() == 0){
  ApiHelper::fail(get_vocab("not_activate"), ApiHelper::NOT_ACTIVATE);
}

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

$row = $result -> next_row_keyed();
if (empty($row['room_id'])){
  ApiHelper::fail(get_vocab("not_bind"), ApiHelper::NOT_BIND);
}
$timezone = getTimeZoneByRoom($row['room_id']);

RedisConnect::zADD(RedisKeys::$HEART_BEAT, $device_id, time());

if (!empty($timezone)) {
  date_default_timezone_set($timezone);
}

$interval_start = strtotime("today");
$interval_end = strtotime("tomorrow");

$room = get_room_details(intval($row['room_id']));
unset($room["exchange_server"]);
unset($room["exchange_username"]);
unset($room["exchange_password"]);

$area = get_area_details($room["area_id"]);

$entries = get_entries_by_room(intval($row['room_id']), $interval_start, $interval_end);

$now = time();
$now_entry = null;

foreach ($entries as $entry) {
  if ($now >= $entry["start_time"] && $now <= $entry["end_time"]) {
    $now_entry = $entry;
    break;
  }
}

if(!$show_meeting_name){
  foreach ($entries as $entry) {
    if ($entry['entry_type'] == 99)
      $entry['name'] = get_vocab('ic_tp_meeting');
  }
}else{
  foreach ($entries as $entry) {
    unset($entry['name']);
  }
}
if (isset($now_entry)) {
  if ($now_entry['entry_type'] == 99)
    $now_entry['name'] = get_vocab('ic_tp_meeting');
}
if (isset($now_entry) && !$show_meeting_name){
  unset($now_entry['name']);
}

if(!$show_book){
  unset($now_entry['create_by']);
  foreach ($entries as &$entry) {
    unset($entry['create_by']);
  }
}

$display_day = datetime_format($datetime_formats['view_day'], $now);
$now_time = date("h:iA", $now);
//$dateTime = new DateTime();
//$timeZone = new DateTimeZone($area["timezone"]);
//$dateTime->setTimeZone($timeZone);
//$now_time = $dateTime->format('h:iA');
//$display_day =  $dateTime->format('Y');



$result = array();
$result["now_time"] = $now_time;
$result["now_timestamp"] = $now;
$result["display_day"] = $display_day;
$result["area"] = $area;
$result["now_entry"] = $now_entry;
$result["entries"] = $entries;
$result["room"] = $room;

ApiHelper::success($result);
