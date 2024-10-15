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

//判断用户是否登录
if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

//判断用户是否具有权限
if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$description = $_POST['description'] ?? null;
$capacity = $_POST['capacity'] ?? null;
if (empty(intval($capacity))){
  ApiHelper::fail(get_vocab("missing_parameters"), ApiHelper::MISSING_PARAMETERS);
}
$room_admin_email = $_POST['room_admin_email'] ?? null;
$type = $_POST['type'] ?? null;
$area = $_POST['area'] ?? null;
if ($type === 'area') {
  $area = false;
  $room = true;
} else if ($type !== 'room') {
  ApiHelper::fail(get_vocab("wrong_type"), ApiHelper::WRONG_TYPE);
} else {
  $room = false;
}

// First of all check that we've got an area or room name
if (!isset($name) || ($name === '')) {
  ApiHelper::fail(get_vocab("empty_name"), ApiHelper::EMPTY_NAME);
}

// we need to do different things depending on if it's a room
// or an area
elseif ($type == "area") {
  $area = mrbsAddArea($name, $error);
} elseif ($type == "room") {
  $room = mrbsAddRoom($name, $area, $error, $description, $capacity, $room_admin_email);
}

if ($area && isset($room) && $room) {
  ApiHelper::success(null);
} else {
  $response = array(
    "code" => -100,
    "message" => get_vocab($error)
  );
  echo json_encode($response);
}

