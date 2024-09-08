<?php

declare(strict_types=1);

namespace MRBS;
use MRBS\CalendarServer\CalendarServerManager;

require_once "../mrbs_sql.inc";
require "../defaultincludes.inc";
require_once "api_helper.php";

global $allow_registration_default, $registrant_limit_default, $registrant_limit_enabled_default;
global $registration_opens_default, $registration_opens_enabled_default, $registration_closes_default;
global $registration_closes_enabled_default;

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$roomId = intval($data['room_id']);
$confirm = intval($data['confirm']);
//$roomId = intval(ApiHelper::value("room_id"));

$now = time();

//if ($now > $startTime) {
//  ApiHelper::fail();
//  return;
//}
//if ($startTime >= $endTime && (($endTime - $startTime) % (30 * 60) != 0)) {
//  ApiHelper::fail();
//  return;
//}

if (floor($now / 1800) * 1800 === floor(($now + 300) / 1800) * 1800){
  $startTime = floor($now / 1800) * 1800;
}else{
  $startTime = floor($now / 1800) * 1800 + 1800;
}

$endTime = $startTime + 1800;

if ($confirm == 0){
  $str1 = date("h:i A", intval($startTime));
  $str2 = date("h:i A", intval($endTime));
  $response = array(
    "code" => 0,
    "message" => "success",
    "data" => array()
  );
  $response['data']['time'] = get_vocab("fast_meeting_time", $str1, $str2);
  echo json_encode($response);
  return;
}else if (!isset($confirm) || $confirm != 1){
  ApiHelper::fail("confirm should not be empty and confirm should be 1 or 0", -3);
}

$qSQL = "room_id = $roomId and
    (($startTime >= start_time and $startTime < end_time)
    or ($endTime > start_time and $endTime <= end_time)
    or ($startTime <= start_time and $endTime >= end_time))
    ";

$roomExist = DBHelper::one(_tbl("room"), "id = $roomId");
if (empty($roomExist)){
  ApiHelper::fail("room not found" );
  return;
}
if ($roomExist['disabled'] == 1) {
  ApiHelper::fail("room disabled" );
  return;
}
$area = get_area_details($roomExist['area_id']);
if (!$area || $area['disabled'] == 1) {
  ApiHelper::fail("area disabled" );
  return;
}

$queryOne = DBHelper::one(_tbl("entry"), $qSQL);
if (!empty($queryOne)) {
  ApiHelper::fail("already exist entry" , -2);
  return;
}

$result = array();
$result["start_time"] = $startTime;
$result["end_time"] = $endTime;
$result["entry_type"] = 99;
$result["room_id"] = $roomId;
$result["create_by"] = "admin";
$result["name"] = get_vocab("ic_tp_meeting");
$result["description"] = get_vocab("ic_tp_meeting");
$result["book_by"] = "/";
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
