<?php

declare(strict_types=1);
namespace MRBS;

global $datetime_formats;

use DateTimeZone;

$room_id = intval(ApiHelper::value("room_id"));

$interval_start = strtotime("today");
$interval_end = strtotime("tomorrow");

$room = get_room_details($room_id);
$area = get_area_details($room["area_id"]);

$entries = get_entries_by_room($room_id, $interval_start, $interval_end);

$now = time();
$now_entry = null;

foreach ($entries as $entry) {
  if ($now >= $entry["start_time"] && $now <= $entry["end_time"]) {
    $now_entry = $entry;
    break;
  }
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



ApiHelper::success($result);
