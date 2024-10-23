<?php
declare(strict_types=1);

namespace MRBS;

/*
 * 添加区域或房间接口
 * @Param
 * name：房间或区域名称（不可为空）
 * description：房间描述，TODO 目前用来存储房间内设备
 * capacity：房间的容纳量
 * room_admin_email：房间管理员的email
 * type：添加的是区域还是房间，如果是区域则该参数为area，如果是房间则该参数为room
 * area：如果添加的是房间，需要给出区域的id
 * @Return
 * 如果code为0，则代表操作成功，如果为-99，说明用户没有登录状态，如果为-98，说明用户没有该操作权限，如果-9代表
 * type参数无效，如果为-10说明name参数为空
 */

// This file is for adding new areas/rooms
$error = '';
$name = $_POST['name'] ?? null;
$parent_id = intval($_POST['parent_id']) ?? -1;
$group_ids = $_POST['group_ids'] ?? null;


//判断用户是否登录
if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

//判断用户是否具有权限
if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$description = $_POST['description'] ?? null;
$capacity = $_POST['capacity'] ?? null;
if (empty(intval($capacity))) {
  ApiHelper::fail(get_vocab("missing_parameters"), ApiHelper::MISSING_PARAMETERS);
}
$room_admin_email = $_POST['room_admin_email'] ?? null;
$type = $_POST['type'] ?? null;
$area = $_POST['area'] ?? null;
if ($type !== 'room' && $type !== 'area') {
  ApiHelper::fail(get_vocab("wrong_type"), ApiHelper::WRONG_TYPE);
}

$area_vars = [
  "disabled" => 0,
  "area_name" => '',
  "timezone" => $_ENV['MRBS_TIMEZONE'] ?? "Etc/UTC",
  "area_admin_email" => '',
  "resolution" => 1800,
  "default_duration" => 3600,
  "default_duration_all_day" => FALSE,
  "morningstarts" => 8,
  "morningstarts_minutes" => 0,
  "eveningends" => 21,
  "eveningends_minutes" => 00,
  "private_enable" => FALSE,
  "private_default" => FALSE,
  "private_mandatory" => FALSE,
  "private_override" => "none",
  "min_create_ahead_enabled" => FALSE,
  "min_create_ahead_secs" => 0,
  "max_create_ahead_enabled" => FALSE,
  "max_create_ahead_secs" => 0,
  "min_delete_ahead_enabled" => FALSE,
  "min_delete_ahead_secs" => 0,
  "max_delete_ahead_enabled" => FALSE,
  "max_delete_ahead_secs" => 0,
  "max_per_day_enabled" => FALSE,
  "max_per_day" => 0,
  "max_per_week_enabled" => FALSE,
  "max_per_week" => 0,
  "max_per_month_enabled" => FALSE,
  "max_per_month" => 0,
  "max_per_year_enabled" => FALSE,
  "max_per_year" => 0,
  "max_per_future_enabled" => FALSE,
  "max_per_future" => 0,
  "max_secs_per_day_enabled" => FALSE,
  "max_secs_per_day" => 0,
  "max_secs_per_week_enabled" => FALSE,
  "max_secs_per_week" => 0,
  "max_secs_per_month_enabled" => FALSE,
  "max_secs_per_month" => 0,
  "max_secs_per_year_enabled" => FALSE,
  "max_secs_per_year" => 0,
  "max_secs_per_future_enabled" => FALSE,
  "max_secs_per_future" => 0,
  "max_duration_enabled" => FALSE,
  "max_duration_secs" => 0,
  "max_duration_periods" => 0,
  "approval_enable" => FALSE,
  "reminders_enabled" => FALSE,
  "enable_periods" => FALSE,
  "periods" => null,
  "confirmation_enabled" => FALSE,
  "confirmation_default" => null,
  "times_along_top" => FALSE,
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
    $sql .= "$var,";
    $params[] = $$var ?? $default;
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
    $sql .= "$var,";
    $params[] = $$var ?? $default;
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


