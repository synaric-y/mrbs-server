<?php
declare(strict_types=1);
namespace MRBS;

/*
 * useless file
 */

$room_id = intval(ApiHelper::value("id"));
$query_type = ApiHelper::value("query_type");

$interval_start = null;
$interval_end = null;

if ($query_type == "day") {
  $interval_start = strtotime("today");
  $interval_end = strtotime("tomorrow");
}

$entries = get_entries_by_room($room_id, $interval_start, $interval_end);

ApiHelper::success($entries);
