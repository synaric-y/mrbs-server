<?php

declare(strict_types=1);

namespace MRBS;
global $allow_registration_default, $registrant_limit_default, $registrant_limit_enabled_default, $registration_opens_default, $registration_opens_enabled_default, $registration_closes_default, $registration_closes_enabled_default;

require_once "../mrbs_sql.inc";
require "../defaultincludes.inc";
require_once "api_helper.php";


if (!checkAuth()){
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
  setcookie("session_id", "", time() - 3600, "/web/");
  return;
}

$data = $_POST;
$roomId = intval($data['room_id']);
$start_time = intval($data['start_time']);
$end_time = intval($data['end_time']);
$name = $data['name'];


$now = time();
if ($end_time <= $now) {
  ApiHelper::fail(get_vocab("expired_end_time"), ApiHelper::EXPIRED_END_TIME);
}

if (empty($roomId) || empty($start_time) || empty($end_time)|| empty($name)) {
  ApiHelper::fail(get_vocab("missing_parameters"), ApiHelper::MISSING_PARAMETERS);
  return;
}

$roomExist = db()->query("SELECT * FROM " . _tbl("room") . " WHERE id=?", array($roomId));
if ($roomExist->count() < 1) {
  ApiHelper::fail(get_vocab("room_not_exist"), ApiHelper::ROOM_NOT_EXIST);
  return;
}

$roomExist = $roomExist -> next_row_keyed();

if ($roomExist['disabled'] == 1){
  ApiHelper::fail(get_vocab("room_disabled"), ApiHelper::ROOM_DISABLED);
  return;
}

$area = get_area_details($roomExist['area_id']);
if (!$area || $area['disabled'] == 1 ){
  ApiHelper::fail(get_vocab("area_disabled"), ApiHelper::AREA_DISABLED);
  return;
}

$conflict = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE room_id = $roomId and (($start_time >= start_time and $start_time < end_time) or ($end_time > start_time and $end_time <= end_time) or ($start_time <= start_time and $end_time >= end_time))");

if ($conflict -> count() > 0){
  ApiHelper::fail(get_vocab("entry_conflict"), ApiHelper::ENTRY_CONFLICT);
  return;
}

$result = array();
$result["start_time"] = $start_time;
$result["end_time"] = $end_time;
$result["entry_type"] = 0;
$result["room_id"] = $roomId;
$result["create_by"] = $_SESSION['user'];
$result["name"] = $name;
$result["description"] = "";
$result["book_by"] = $_SESSION['user'];
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
$result["create_source"] = "wxwork";

DBHelper::insert(\MRBS\_tbl("entry"), $result);
//$insertId = DBHelper::insert_id(_tbl("entry"), "id");
//if (!empty($insertId)) {
//  CalendarServerManager::createMeeting($insertId);
//}

ApiHelper::success(null);
