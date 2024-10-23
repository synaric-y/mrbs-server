<?php
declare(strict_types=1);
namespace MRBS;


global $datetime_formats;

/*
 * 用于首页显示，查询某一个时间段内的所有会议
 * @Params
 * type：判断根据什么条件进行查询，根据区域时为area，根据房间时为room，无限定条件时为all
 * id：如果是根据区域或者房间进行查询，则需要传入该区域或房间的id
 * start_time：查询范围的开始时间，为秒级时间戳
 * end_time：查询范围的结束时间，为秒级时间戳
 * timezone：前端的时区，TODO 该参数是目前暂定的防止由于时区导致的显示问题的解决方案，后期将尝试改为多时区
 * @Return
 * data中areas包含所有的会议信息，max_time表示当前查询的区域中最晚的可预定时间，min_time表示当前查询区域中最早
 * 的可预订时间，time表示后端时间转换到前端时区后的时间，timestamp表示当前后端时间戳
 */

function buildTree($data, $parent_id = -1)
{
  $tree = [];
  foreach ($data as $row) {
    if ($row['parent_id'] == $parent_id) {
      $children = buildTree($data, $row['id']);
      if (!empty($children) || !empty($row['rooms'])){
        if (!empty($children))
          $row['children'] = $children;
        $tree[] = $row;
      }else{
        unset($row['children']);
      }
    }
  }
  return !empty($tree) ? $tree : null;
}


if(isset($_POST['type'])) {
  $type = $_POST['type'];
}
if (isset($_POST['id'])) {
  $id = $_POST['id'];
}
if (isset($_POST['start_time'])) {
  $start_time = $_POST['start_time'];
}else{
  $start_time = strtotime("today midnight");
}
if (isset($_POST['end_time'])) {
  $end_time = $_POST['end_time'];
}else{
  $end_time = strtotime("tomorrow midnight");
}
if (isset($_POST['timezone'])) {
  $timezone = $_POST['timezone'];
}

if(empty($type) || ($type != "all" && $type != "area" && $type != "room")){
  ApiHelper::fail(get_vocab("invalid_types"), ApiHelper::INVALID_TYPES);
}

if (empty($start_time)) {
  $start_time = strtotime("today midnight");
}
if (empty($end_time)) {
  $end_time = strtotime("tomorrow midnight");
}

if ($type != 'all') {
  $roomExist = db()->query1("SELECT COUNT(*) FROM " . _tbl($type) . " WHERE id = ?", array($id));
  if ($roomExist <= 0) {
    if ($type == 'room')
      ApiHelper::fail(get_vocab($type . "_not_exist"), ApiHelper::ROOM_NOT_EXIST);
    else
      ApiHelper::fail(get_vocab($type . "_not_exist"), ApiHelper::AREA_NOT_EXIST);
  }
}

$sql = "SELECT E.id AS id, area_id, room_id, start_time, end_time, E.name AS name, book_by, morningstarts, morningstarts_minutes, eveningends, eveningends_minutes, R.room_name, area_name, A.disabled as area_disabled, R.disabled as room_disabled, timezone, R.description as description, resolution, capacity FROM " . _tbl("entry") . " E LEFT JOIN " . _tbl("room") .
" R ON E.room_id = R.id " . "LEFT JOIN " . _tbl("area") . " A ON R.area_id = A.id";
if ($type == 'area'){
  $sql .= " WHERE A.id = ? AND start_time >= ? AND end_time <= ?";
}else if ($type == 'room') {
  $sql .= " WHERE R.id = ? AND start_time >= ? AND end_time <= ?";
} else if($type != 'all'){
  ApiHelper::fail(get_vocab("invalid_types"), ApiHelper::INVALID_TYPES);
}else{
  $sql .= " WHERE start_time >= ? AND end_time <= ?";
}

if ($type != 'all')
  $result = db() -> query($sql, array($id, $start_time, $end_time));
else
  $result = db() -> query($sql, array($start_time, $end_time));

if ($type == 'all'){
  $entries = $result -> all_rows_keyed();
  $areas = db() -> query("SELECT id, area_name, disabled, morningstarts, morningstarts_minutes, eveningends, eveningends_minutes, resolution, parent_id FROM " . _tbl("area")) -> all_rows_keyed();
  $rooms = db() -> query("SELECT id, disabled, description, capacity, area_id FROM " . _tbl("room")) -> all_rows_keyed();
  $data = [];
  if (empty($rooms) || empty($areas)){
    $data["min_time"] = "08:00 AM";
    $data["max_time"] = "09:00 PM";
    $data["time"] = datetime_format($datetime_formats['date_and_time'], time());
    $data["timestamp"] = time();
    ApiHelper::success($data);
  }
  foreach ($entries as $entry) {
    foreach ($rooms as &$room) {
      if ($entry['room_id'] == $room['id']){
        $room['entries'][]['entry_id'] = $entry['id'];
        $room['entries'][]['start_time'] = $entry['start_time'];
        $room['entries'][]['end_time'] = $entry['end_time'];
        $room['entries'][]['entry_name'] = $entry['name'];
        $room['entries'][]['book_by'] = $entry['book_by'];
        $room['entries'][]['status'] = $entry['start_time'] > time() ? 0 : ($entry['end_time'] < time() ? 2 : 1);
        $room['entries'][]['duration'] = date("h:iA", intval($entry['start_time'])) . "-" . date("h:iA", intval($entry['end_time']));
        $room['entries'][]['room_name'] = $room['room_name'];
      }
    }
    unset($room);
  }
  foreach ($rooms as &$room) {
    foreach ($areas as &$area) {
      if ($room['area_id'] == $area['id']) {
        $room['entries'] = [];
        $area['rooms'][] = $room;
        break;
      }
    }
    unset($room['area_id']);
    unset($area);
  }
  unset($room);
  foreach ($areas as &$area){
      $area['start_time'] = sprintf("%02d", $area['morningstarts'] > 12 ? $area['morningstarts'] - 12 : $area['morningstarts']) . ":" . sprintf("%02d", $area['morningstarts_minutes']) . ($area['morningstarts'] > 12 ? " PM" : " AM");
      $area['end_time'] = sprintf("%02d", $area['eveningends'] > 12 ? $area['eveningends'] - 12 : $area['eveningends']) . ":" . sprintf("%02d", $area['eveningends_minutes']) . ($area['eveningends'] > 12 ? " PM" : " AM");
  }
  unset($area);
  $min_time = 10000000;
  $max_time = -1;
  foreach ($areas as $area) {
    $min_time = min($min_time, $area['morningstarts'] * 60 + $area['morningstarts_minutes']);
    $max_time = max($max_time, $area['eveningends'] * 60 + $area['eveningends_minutes']);
  }

  $min_time = date("h:i A", $min_time * 60);
  $max_time = date("h:i A", $max_time * 60);


  $areas = buildTree($areas);

  $data['areas'] = $areas;
  $data['min_time'] = $min_time;
  $data['max_time'] = $max_time;
  $data['time'] = datetime_format($datetime_formats['date_and_time'], time());
  $data['timestamp'] = time();
  ApiHelper::success($data);


}else{
  $entries = $result -> all_rows_keyed();
  $area = db() -> query("SELECT id as area_id, area_name, disabled, morningstarts, morningstarts_minutes, eveningends, eveningends_minutes, resolution, parent_id FROM " . _tbl("area") . " WHERE id = ?", array($id)) -> next_row_keyed();
  $root = $area['parent_id'];
  $rooms = db() -> query("SELECT id, disabled, description, capacity, area_id FROM " . _tbl("room") . " WHERE area_id = ?", array($id)) -> all_rows_keyed();
  $areas[] = $area;
  $parent_ids[] = $area['area_id'];
  while(1){
    $parent_string = "(";
    foreach ($parent_ids as $parent_id) {
      $parent_string .= $parent_id . ",";
    }
    $parent_string = substr($parent_string, 0, -1);
    $parent_string .= ")";
    $tmp_a = db() -> query("SELECT id, area_name, disabled, morningstarts, morningstarts_minutes, eveningends, eveningends_minutes, resolution, parent_id FROM " . _tbl("area") . " WHERE parent_id in " . $parent_string);
    $count = $tmp_a -> count();
    $tmp_a = $tmp_a -> all_rows_keyed();
    $areas = array_merge($areas, $tmp_a);
    $tmp_r = db() -> query("SELECT id, disabled, description, capacity, area_id FROM " . _tbl("room") . " WHERE area_id in " . $parent_string) -> all_rows_keyed();
    $rooms = array_merge($rooms, $tmp_r);

    if ($count == 0){
      break;
    }
    $parent_ids = [];
    foreach ($tmp_a as $item) {
      $parent_ids[] = $item['id'];
    }

  }
  $data = [];
  if (empty($rooms) || empty($areas)){
    $data["min_time"] = "08:00 AM";
    $data["max_time"] = "09:00 PM";
    $data["time"] = datetime_format($datetime_formats['date_and_time'], time());
    $data["timestamp"] = time();
    ApiHelper::success($data);
  }
  foreach ($entries as $entry) {
    foreach ($rooms as &$room) {
      if ($entry['room_id'] == $room['id']){
        $room['entries'][]['entry_id'] = $entry['id'];
        $room['entries'][]['start_time'] = $entry['start_time'];
        $room['entries'][]['end_time'] = $entry['end_time'];
        $room['entries'][]['entry_name'] = $entry['name'];
        $room['entries'][]['book_by'] = $entry['book_by'];
        $room['entries'][]['status'] = $entry['start_time'] > time() ? 0 : ($entry['end_time'] < time() ? 2 : 1);
        $room['entries'][]['duration'] = date("h:iA", intval($entry['start_time'])) . "-" . date("h:iA", intval($entry['end_time']));
        $room['entries'][]['room_name'] = $room['room_name'];
      }
    }
  }
  unset($room);
  foreach ($rooms as $room) {
    foreach ($areas as &$area) {
      if ($room['area_id'] == $area['id']) {
        $room['entries'] = [];
        $area['rooms'][] = $room;
        break;
      }
    }
    unset($area);
  }

  $min_time = 10000000;
  $max_time = -1;
  foreach ($areas as $area) {
    $min_time = min($min_time, $area['morningstarts'] * 60 + $area['morningstarts_minutes']);
    $max_time = max($max_time, $area['eveningends'] * 60 + $area['eveningends_minutes']);
  }

  $min_time = date("h:i A", $min_time * 60);
  $max_time = date("h:i A", $max_time * 60);


  $areas = buildTree($areas, $root);

  if(!empty($areas))
    $data['areas'] = $areas;
  $data['min_time'] = $min_time;
  $data['max_time'] = $max_time;
  $data['time'] = datetime_format($datetime_formats['date_and_time'], time());
  $data['timestamp'] = time();
  ApiHelper::success($data);

}



















//if ($result -> count() < 1){
//  $result = db() -> query("SELECT * FROM " . _tbl("area"));
//  $rows = $result -> all_rows_keyed();
//  usort($rows, function ($a, $b) {
//    if ($a['morningstarts'] == $b['morningstarts']) {
//      return $a['morningstarts_minutes'] <=> $b['morningstarts_minutes'];
//    }
//    return $a['morningstarts'] <=> $b['morningstarts'];
//  });
//  if (empty($rows[0]['morningstarts']))
//    $rows[0]['morningstarts'] = 8;
//  if (empty($rows[0]['morningstarts_minutes']))
//    $rows[0]['morningstarts_minutes'] = 0;
//  $min_time = sprintf("%02d", $rows[0]['morningstarts'] > 12 ? $rows[0]['morningstarts'] - 12 : $rows[0]['morningstarts']) . ":" . sprintf("%02d", $rows[0]['morningstarts_minutes']) . ($rows[0]['morningstarts'] > 12 ? " PM" : " AM");
//  usort($rows, function ($a, $b) {
//    if ($a['eveningends'] == $b['eveningends']) {
//      return $a['eveningends_minutes'] <=> $b['eveningends_minutes'];
//    }
//    return $a['eveningends'] <=> $b['eveningends'];
//  });
//  if (empty($rows[count($rows) - 1]['eveningends']))
//    $rows[count($rows) - 1]['eveningends'] = 21;
//  if (empty($rows[count($rows) - 1]['eveningends_minutes']))
//    $rows[count($rows) - 1]['eveningends_minutes'] = 0;
//  $max_time = sprintf("%02d", $rows[count($rows) - 1]['eveningends'] > 12 ? $rows[count($rows) - 1]['eveningends'] - 12 : $rows[count($rows) - 1]['eveningends']) . ":" . sprintf("%02d", $rows[count($rows) - 1]['eveningends_minutes']) . ($rows[count($rows) - 1]['eveningends'] > 12 ? " PM" : " AM");
//  $data = array();
//  $data['min_time'] = $min_time;
//  $data['max_time'] = $max_time;
//  $date = datetime_format($datetime_formats['date_and_time'], time());
//  $data['time'] = $date;
//  $data['timestamp'] = time();
//  $data['areas'] = array();
//  ApiHelper::success($data);
//}
//$rows = $result -> all_rows_keyed();
//$default_timezone = date_default_timezone_get();
//foreach ($rows as $row) {
//  if (!empty($timezone))
//    date_default_timezone_set($timezone);
//  else
//    date_default_timezone_set($default_timezone);
//  $areaId = $row['area_id'];
//  $roomId = $row['room_id'];
//  if (!isset($tmp[$areaId])){
//    $tmp[$areaId] = array(
//      'area_id' => $areaId,
//      'area_name' => $row['area_name'],
//      'disabled' => $row['area_disabled'],
//      'start_time' => sprintf("%02d", $row['morningstarts'] > 12 ? $row['morningstarts'] - 12 : $row['morningstarts']) . ":" . sprintf("%02d", $row['morningstarts_minutes']) . ($row['morningstarts'] > 12 ? " PM" : " AM"),
//      'end_time' => sprintf("%02d", $row['eveningends'] > 12 ? $row['eveningends'] - 12 : $row['eveningends']) . ":" . sprintf("%02d", $row['eveningends_minutes']) . ($row['eveningends'] > 12 ? " PM" : " AM"),
//      'status' => 'string',
//      'morningstarts' => $row['morningstarts'],
//      'morningstarts_minutes' => $row['morningstarts_minutes'],
//      'eveningends' => $row['eveningends'],
//      'eveningends_minutes' => $row['eveningends_minutes'],
//      'resolution' => $row['resolution'],
//      'rooms' => array()
//    );
//  }
//
//  if (!isset($tmp[$areaId]['rooms'][$roomId])){
//    $tmp[$areaId]['rooms'][$roomId] = array(
//      'room_id' => $roomId,
//      'disabled' => $row['area_disabled'] == 1 ? 1 : $row['room_disabled'],
//      'description' => $row['description'],
//      'capacity' => $row['capacity'],
//      'entries' => array()
//    );
//  }
//
//  if (time() < $row['start_time'])
//    $status = 0;
//  else if (time() > $row['end_time'])
//    $status = 2;
//  else
//    $status = 1;
//
//  $tmp[$areaId]['rooms'][$roomId]['entries'][] = array(
//    "entry_id" => $row['id'],
//    "start_time" => $row['start_time'],
//    "end_time" => $row['end_time'],
//    "entry_name" => $row['name'],
//    "book_by" => $row['book_by'],
//    "status" => $status,
//    "duration" => date("h:iA", intval($row['start_time'])) . "-" . date("h:iA", intval($row['end_time'])),
//    "room_name" => $row['room_name']
//  );
//}
//
//date_default_timezone_set($default_timezone);
//$result = array(
//  'areas' => array_values($tmp)
//);
//
//foreach ($result['areas'] as &$area) {
//  $area['rooms'] = array_values($area['rooms']);
//}
//
//
//for ($i = 0; $i < count($result['areas']); $i++) {
//  $flag = false;
//  $todayMidnight = strtotime('today midnight');
//  $begin = $todayMidnight + $result['areas'][$i]['morningstarts'] * 3600 + $result['areas'][$i]['morningstarts_minutes'] * 60;
//  $end = $todayMidnight + $result['areas'][$i]['eveningends'] * 3600 + $result['areas'][$i]['eveningends_minutes'] * 60;
//  for ($j = 0; $j < count($result['areas'][$i]['rooms']); $j++) {
//    usort($result['areas'][$i]['rooms'][$j]['entries'], function ($a, $b) {
//      return $a['start_time'] <=> $b['start_time'];
//    });
//    if($result['areas'][$i]['rooms'][$j]['entries'][0]['start_time'] != $begin || $result['areas'][$i]['rooms'][$j]['entries'][count($result['areas'][$i]['rooms'][$j]['entries']) - 1]['end_time'] != $end){
//      $result['areas'][$i]['rooms'][$j]['status'] = "可预约";
//      break;
//    }
//    for ($k = 1; $k < count($result['areas'][$i]['rooms'][$j]['entries']); $k++) {
//      if ($result['areas'][$i]['rooms'][$j]['entries'][$k - 1]['end_time'] != $result['areas'][$i]['rooms'][$j]['entries'][$k]['end_time']){
//        $result['areas'][$i]['rooms'][$j]['status'] = "可预约";
//        $flag = true;
//      }
//    }
//    if (!$flag){
//      $result['areas'][$i]['rooms'][$j]['status'] = "不可预约";
//    }
//  }
//}
//
//
//usort($rows, function ($a, $b) {
//  if ($a['morningstarts'] == $b['morningstarts']) {
//    return $a['morningstarts_minutes'] <=> $b['morningstarts_minutes'];
//  }
//  return $a['morningstarts'] <=> $b['morningstarts'];
//});
//if (empty($rows[0]['morningstarts']))
//  $rows[0]['morningstarts'] = 8;
//if (empty($rows[0]['morningstarts_minutes']))
//  $rows[0]['morningstarts_minutes'] = 0;
//$min_time = sprintf("%02d", $rows[0]['morningstarts'] > 12 ? $rows[0]['morningstarts'] - 12 : $rows[0]['morningstarts']) . ":" . sprintf("%02d", $rows[0]['morningstarts_minutes']) . ($rows[0]['morningstarts'] > 12 ? " PM" : " AM");
//usort($rows, function ($a, $b) {
//  if ($a['eveningends'] == $b['eveningends']) {
//    return $a['eveningends_minutes'] <=> $b['eveningends_minutes'];
//  }
//  return $a['eveningends'] <=> $b['eveningends'];
//});
//if (empty($rows[count($rows) - 1]['eveningends']))
//  $rows[count($rows) - 1]['eveningends'] = 21;
//if (empty($rows[count($rows) - 1]['eveningends_minutes']))
//  $rows[count($rows) - 1]['eveningends_minutes'] = 0;
//$max_time = sprintf("%02d", $rows[count($rows) - 1]['eveningends'] > 12 ? $rows[count($rows) - 1]['eveningends'] - 12 : $rows[count($rows) - 1]['eveningends']) . ":" . sprintf("%02d", $rows[count($rows) - 1]['eveningends_minutes']) . ($rows[count($rows) - 1]['eveningends'] > 12 ? " PM" : " AM");
//
//$now = time();
//$date = datetime_format($datetime_formats['date_and_time'], $now);
//
//$data = $result;
//$data['min_time'] = $min_time;
//$data['max_time'] = $max_time;
//$data['time'] = $date;
//$data['timestamp'] = $now;
ApiHelper::success($data);
