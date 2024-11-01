<?php
declare(strict_types=1);

namespace MRBS;

/*
 * add a room or an area
 * @Param
 * name：room name or area name(not null)
 * description：room or area description, TODO temporarily to save the device(except tablet) in the room, and have not be used
 * capacity：room capacity(useful when add a room)
 * room_admin_email：email of the room manager
 * type：'room' means adding a room, 'area' means adding an area, other words are invalid
 * area：when adding a room, an area should be given to show which area the room belongs to
 * @Return
 * none
 */

// This file is for adding new areas/rooms
$error = '';
$name = $_POST['name'] ?? null;
$parent_id = empty($_POST['parent_id']) ? intval($_POST['parent_id']) : -1;
$group_ids = $_POST['group_ids'] ?? null;


//whether user is logged in
if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

//whether user have the access to add room or area

if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$description = $_POST['description'] ?? null;
$capacity = $_POST['capacity'] ?? null;

$room_admin_email = $_POST['room_admin_email'] ?? null;
$type = $_POST['type'] ?? null;
$area = $_POST['area'] ?? null;
if ($type !== 'room' && $type !== 'area') {
  ApiHelper::fail(get_vocab("wrong_type"), ApiHelper::WRONG_TYPE);
}
if ($type == 'room' && empty(intval($capacity))) {
  ApiHelper::fail(get_vocab("missing_parameters"), ApiHelper::MISSING_PARAMETERS);
}

$area_vars = [
  "disabled" => 0,
  "area_name" => '',
  "timezone" => $_ENV['MRBS_TIMEZONE'] ?? "Etc/UTC",
  "area_admin_email" => '',
  "resolution" => 1800,
  "default_duration" => 3600,
  "default_duration_all_day" => 0,
  "morningstarts" => 8,
  "morningstarts_minutes" => 0,
  "eveningends" => 21,
  "eveningends_minutes" => 00,
  "private_enabled" => 0,
  "private_default" => 0,
  "private_mandatory" => 0,
  "private_override" => "none",
  "min_create_ahead_enabled" => 0,
  "min_create_ahead_secs" => 0,
  "max_create_ahead_enabled" => 0,
  "max_create_ahead_secs" => 0,
  "min_delete_ahead_enabled" => 0,
  "min_delete_ahead_secs" => 0,
  "max_delete_ahead_enabled" => 0,
  "max_delete_ahead_secs" => 0,
  "max_per_day_enabled" => 0,
  "max_per_day" => 0,
  "max_per_week_enabled" => 0,
  "max_per_week" => 0,
  "max_per_month_enabled" => 0,
  "max_per_month" => 0,
  "max_per_year_enabled" => 0,
  "max_per_year" => 0,
  "max_per_future_enabled" => 0,
  "max_per_future" => 0,
  "max_secs_per_day_enabled" => 0,
  "max_secs_per_day" => 0,
  "max_secs_per_week_enabled" => 0,
  "max_secs_per_week" => 0,
  "max_secs_per_month_enabled" => 0,
  "max_secs_per_month" => 0,
  "max_secs_per_year_enabled" => 0,
  "max_secs_per_year" => 0,
  "max_secs_per_future_enabled" => 0,
  "max_secs_per_future" => 0,
  "max_duration_enabled" => 0,
  "max_duration_secs" => 0,
  "max_duration_periods" => 0,
  "approval_enabled" => 0,
  "reminders_enabled" => 0,
  "enable_periods" => 0,
  "periods" => null,
  "confirmation_enabled" => 0,
  "confirmed_default" => null,
  "times_along_top" => 0,
  "default_type" => 'E',
  "parent_id" => -1
];

$room_vars = array(
  "disabled" => 0,
  "area_id" => 0,
  "room_name" => '',
  "sort_key" => '',
  "description" => '',
  "capacity" => 0,
  "room_admin_email" => null,
  "invalid_types" => null,
  "exchange_username" => null,
  "exchange_password" => null,
  "exchange_sync_state" => null,
  "show_book" => 1,
  "show_meeting_name" => 1,
  "temporary_meeting" => 1
);


// First of all check that we've got an area or room name
if (!isset($name) || ($name === '')) {
  ApiHelper::fail(get_vocab("empty_name"), ApiHelper::EMPTY_NAME);
}
// we need to do different things depending on if it's a room
// or an area
elseif ($type == "area") {
  $one = db()->query1("SELECT COUNT(*) FROM " . _tbl("area") . " WHERE area_name = ?", array($name));
  if ($one > 0) {
    ApiHelper::fail(get_vocab("invalid_area"), ApiHelper::INVALID_AREA);
  }
  if (!empty($parent_id)) {
    $parent = db()->query1("SELECT COUNT(*) FROM " . _tbl("area") . " WHERE id = ?", array($parent_id));
    if ($parent < 1) {
      ApiHelper::fail(get_vocab("area_not_exist"), ApiHelper::AREA_NOT_EXIST);
    }
  }
  $area_vars['area_name'] = $name;
  $sql = "INSERT INTO " . _tbl("area") . "(";
  $params = array();
  foreach ($area_vars as $var => $default) {
    $v = null;
    if (isset($_POST[$var])) {
      $v = $_POST[$var];
    }
    $sql .= "$var,";
    $params[] = $v ?? $default;
  }
  $sql = substr($sql, 0, -1);
  $sql .= ") VALUES (";
  foreach ($params as $param) {
    $sql .= "?,";
  }
  $sql = substr($sql, 0, -1);
  $sql .= ")";
  db()->begin();
  db()->command($sql, $params);
  $area_id = db()->insert_id("", "");
  $sql = "INSERT INTO " . _tbl("area_group") . "(area_id, group_id) VALUES ";
  $params = array();
  foreach ($group_ids as $group_id) {
    $sql .= "(?, ?),";
    $params[] = $area_id;
    $params[] = $group_id;
  }
  $sql = substr($sql, 0, -1);
  db()->command($sql, $params);
  db()->commit();
  ApiHelper::success(null);
} elseif ($type == "room") {
  if (empty($area)) {
    ApiHelper::fail(get_vocab("missing_parameters"), ApiHelper::MISSING_PARAMETERS);
  }
  $one = db()->query1("SELECT COUNT(*) FROM " . _tbl("area") . " WHERE id = ?", array($area));
  if ($one < 1) {
    ApiHelper::fail(get_vocab("area_not_exist"), ApiHelper::AREA_NOT_EXIST);
  }
  $one = db()->query1("SELECT COUNT(*) FROM " . _tbl("room") . " WHERE room_name = ?", array($name));
  if ($one > 0) {
    ApiHelper::fail(get_vocab("invalid_room_name"), ApiHelper::INVALID_ROOM_NAME);
  }
  $room_vars['room_name'] = $name;
  $sql = "INSERT INTO " . _tbl("room") . "(";
  $params = array();
  foreach ($room_vars as $var => $default) {
    $v = null;
    if (isset($_POST[$var])) {
      $v = $_POST[$var];
    }
    $sql .= "$var,";
    $params[] = $v ?? $default;
  }
  $sql = substr($sql, 0, -1);
  $sql .= ") VALUES (";
  foreach ($params as $param) {
    $sql .= "?,";
  }
  $sql = substr($sql, 0, -1);
  $sql .= ")";
  db()->begin();
  db()->command($sql, $params);
  $room_id = db()->insert_id("", "");
  $sql = "INSERT INTO " . _tbl("room_group") . "(room_id, group_id) VALUES ";
  $params = array();
  foreach ($group_ids as $group_id) {
    $sql .= "(?, ?),";
    $params[] = $room_id;
    $params[] = $group_id;
  }
  $sql = substr($sql, 0, -1);
  db()->command($sql, $params);
  db()->commit();
  ApiHelper::success(null);
}


$response = array(
  "code" => -100,
  "message" => get_vocab($error)
);
echo json_encode($response);


