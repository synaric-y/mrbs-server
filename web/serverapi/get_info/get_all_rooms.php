<?php
declare(strict_types=1);

namespace MRBS;

/*
 * get all rooms
 * @Params
 * type：'all' means all rooms, 'area' means the rooms in the area, 'room' means the room
 * id：only be used when type is 'room' or 'area', means the id of the room or area
 * @Return
 * room information
 */

$area = null;

if (isset($_POST['type'])) {
  $type = $_POST['type'];
}
if (isset($_POST['id'])) {
  $id = $_POST['id'];
}

$user = null;
if (!empty($_SESSION['user'])) {
  $user = db()->query("SELECT * FROM " . _tbl("users") . " WHERE name = ?", array($_SESSION['user']))->next_row_keyed();
}


if ($type == 'all') {
  $result = get_room_and_area_by_user($user);
  if ($result->count() < 1) {
    ApiHelper::fail(get_vocab("room_not_exist"), ApiHelper::ROOM_NOT_EXIST);
  }
  $rows = $result->all_rows_keyed();
  foreach ($rows as $row) {
    $areaId = $row['area_id'];
    $roomId = $row['room_id'];
    $roomName = $row['room_name'];
    $areaName = $row['area_name'];
    if (!isset($tmp[$areaId])) {
      $tmp[$areaId] = array(
        'area_id' => $areaId,
        'area_name' => $areaName,
        'disabled' => $row['area_disabled'],
        'start_time' => sprintf("%02d", $row['morningstarts'] > 12 ? $row['morningstarts'] - 12 : $row['morningstarts']) . ":" . sprintf("%02d", $row['morningstarts_minutes']) . ($row['morningstarts'] > 12 ? " PM" : " AM"),
        'end_time' => sprintf("%02d", $row['eveningends'] > 12 ? $row['eveningends'] - 12 : $row['eveningends']) . ":" . sprintf("%02d", $row['eveningends_minutes']) . ($row['eveningends'] > 12 ? " PM" : " AM"),
        'resolution' => $row['resolution'],
        'rooms' => array()
      );
    }

    if (!isset($tmp[$areaId]['rooms'][$roomId])) {
      $tmp[$areaId]['rooms'][$roomId] = array(
        'room_id' => $roomId,
        'room_name' => $roomName,
        'description' => $row['description'],
        'status' => "可预约",
        'capacity' => $row['capacity'],
        'disabled' => $row['area_disabled'] == 1 ? 1 : $row['room_disabled']
      );
    }
  }
  $result = array(
    'areas' => array_values($tmp)
  );
  foreach ($result['areas'] as &$area) {
    $area['rooms'] = array_values($area['rooms']);
  }
  ApiHelper::success($result);
} else if ($type == 'area') {
  $result = db()->query("SELECT R.id as room_id,R.disabled as room_disabled, A.disabled as area_disabled, R.*, A.* FROM "
    . _tbl("room") . " R LEFT JOIN " . _tbl("area") . " A ON R.area_id = A.id WHERE A.id = ?", array($id));
  if ($result->count() < 1) {
    ApiHelper::fail(get_vocab("room_not_exist"), ApiHelper::ROOM_NOT_EXIST);
  }
  $rows = $result->all_rows_keyed();
  foreach ($rows as $row) {
    if (!isset($area)) {
      $area = array(
        'area_id' => $row['area_id'],
        'area_name' => $row['area_name'],
        'disabled' => $row['area_disabled'],
        'start_time' => sprintf("%02d", $row['morningstarts'] > 12 ? $row['morningstarts'] - 12 : $row['morningstarts']) . ":" . sprintf("%02d", $row['morningstarts_minutes']) . ($row['morningstarts'] > 12 ? " PM" : " AM"),
        'end_time' => sprintf("%02d", $row['eveningends'] > 12 ? $row['eveningends'] - 12 : $row['eveningends']) . ":" . sprintf("%02d", $row['eveningends_minutes']) . ($row['eveningends'] > 12 ? " PM" : " AM"),
        'resolution' => $row['resolution'],
        'rooms' => array()
      );
    }
    $area['rooms'][] = array(
      'room_id' => $row['room_id'],
      'room_name' => $row['room_name'],
      'description' => $row['description'],
      'status' => "可预约",
      'capacity' => $row['capacity'],
      'disabled' => $row['area_disabled'] == 1 ? 1 : $row['room_disabled']
    );
  }
  $data1['areas'] = $area;
  ApiHelper::success($data1);
} else if ($type == 'room') {
  $result = db()->query("SELECT R.id as room_id, R.disabled as room_disabled, A.disabled as area_disabled, R.*, A.* FROM "
    . _tbl("room") . " R LEFT JOIN " . _tbl("area") . " A ON R.area_id = A.id WHERE R.id = ?", array($id));
  if ($result->count() != 1) {
    ApiHelper::fail(get_vocab("room_not_exist"), ApiHelper::ROOM_NOT_EXIST);
  }
  $row = $result->next_row_keyed();
  $area = array(
    'area_id' => $row['area_id'],
    'area_name' => $row['area_name'],
    'disabled' => $row['area_disabled'],
    'start_time' => sprintf("%02d", $row['morningstarts'] > 12 ? $row['morningstarts'] - 12 : $row['morningstarts']) . ":" . sprintf("%02d", $row['morningstarts_minutes']) . ($row['morningstarts'] > 12 ? " PM" : " AM"),
    'end_time' => sprintf("%02d", $row['eveningends'] > 12 ? $row['eveningends'] - 12 : $row['eveningends']) . ":" . sprintf("%02d", $row['eveningends_minutes']) . ($row['eveningends'] > 12 ? " PM" : " AM"),
    'resolution' => $row['resolution'],
    'rooms' => array()
  );
  $area['rooms'][] = array(
    'room_id' => $row['room_id'],
    'room_name' => $row['room_name'],
    'description' => $row['description'],
    'status' => "可预约",
    'capacity' => $row['capacity'],
    'disabled' => $row['area_disabled'] == 1 ? 1 : $row['room_disabled']
  );
  ApiHelper::success($area);
} else {
  ApiHelper::fail(get_vocab("invalid_types"), ApiHelper::INVALID_TYPES);
}
