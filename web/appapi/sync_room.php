<?php

declare(strict_types=1);
namespace MRBS;

global $datetime_formats;

use DateTimeZone;

require_once "../defaultincludes.inc";
require_once "api_helper.php";

function getTimeZoneByRoom($roomId)
{
  $sql = "SELECT timezone FROM " .\MRBS\_tbl("room") . " E LEFT JOIN " . \MRBS\_tbl("area")
    . " F ON E.area_id = F.id WHERE E.id = ?";
  $result = db() -> query($sql, array($roomId));
  $result = $result->next_row_keyed();
  return $result['timezone'];
}

//$room_id = intval(ApiHelper::value("room_id"));
$json = file_get_contents('php://input');
$data = json_decode($json, true);
$roomId = intval($data['room_id']);
$timezone = getTimeZoneByRoom($roomId);
$device_info = $data["device_info"];
$device_id = $data["device_id"];
$battery_level = $data["battery_level"];
$battery_charge = $data["battery_charge"];

if (!empty($timezone)) {
  date_default_timezone_set($timezone);
}

$interval_start = strtotime("today");
$interval_end = strtotime("tomorrow");

$room = get_room_details($roomId);
unset($room["exchange_server"]);
unset($room["exchange_username"]);
unset($room["exchange_password"]);

$area = get_area_details($room["area_id"]);

$entries = get_entries_by_room($roomId, $interval_start, $interval_end);

$now = time();
$now_entry = null;

foreach ($entries as $entry) {
  if ($now >= $entry["start_time"] && $now <= $entry["end_time"]) {
    $now_entry = $entry;
    break;
  }
}

foreach ($entries as $entry){
  if ($entry['entry_type'] == 99)
    $entry['name'] = get_vocab('ic_tp_meeting');
}
if (isset($now_entry)) {
  if ($now_entry['entry_type'] == 99)
    $now_entry['name'] = get_vocab('ic_tp_meeting');
}

$display_day = datetime_format($datetime_formats['view_day'], $now);
$now_time = date("h:iA");
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


db() -> query("UPDATE " . _tbl("room") . " SET battery_level = ? WHERE id = ?", array($battery_level, $roomId));


ApiHelper::success($result);
