<?php
declare(strict_types=1);
namespace MRBS;


global $datetime_formats;

/*
 * get entries between start_time and end_time
 * @Params
 * type：'all' means the entries in all rooms, 'area' means the entries in the area, 'room' means the
 *     entries in the room
 * id：only be used when type is 'area' or 'room', id of the room or area
 * start_time：timestamp
 * end_time：timestamp
 * timezone：TODO temporarily be the parameter, will get from area information soon after
 * @Return
 * entries information, the booking min_time of all area, the booking max_time of all area
 *    the timestamp and date of now
 */

function buildTree($data, $parent_id = -1)
{
  $tree = [];
  foreach ($data as $row) {
    if ($row['parent_id'] == $parent_id) {
      $children = buildTree($data, $row['area_id']);
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

$sql = "SELECT E.id AS id, area_id, room_id, start_time, end_time, E.name AS name, create_by, morningstarts, morningstarts_minutes, eveningends, eveningends_minutes, R.room_name, area_name, A.disabled as area_disabled, R.disabled as room_disabled, timezone, R.description as description, resolution, capacity, repeat_id FROM " . _tbl("entry") . " E LEFT JOIN " . _tbl("room") .
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
  $rooms = db() -> query("SELECT id, disabled, description, capacity, area_id, room_name FROM " . _tbl("room")) -> all_rows_keyed();
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
        $room['entries'][] = array(
          'entry_id' => $entry['id'],
          'start_time' => $entry['start_time'],
          'end_time' => $entry['end_time'],
          'entry_name' => $entry['name'],
          'book_by' => $entry['book_by'],
          'status' => $entry['start_time'] > time() ? 0 : ($entry['end_time'] < time() ? 2 : 1),
          'duration' => date("h:iA", intval($entry['start_time'])) . "-" . date("h:iA", intval($entry['end_time'])),
          'room_name' => $room['room_name'],
          'repeat_id' => $entry['repeat_id']
        );
      }
    }
    unset($room);
  }
  foreach ($rooms as &$room) {
    foreach ($areas as &$area) {
      if ($room['area_id'] == $area['id']) {
        $room['room_id'] = $room['id'];
        unset($room['id']);
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
      $area['area_id'] = $area['id'];
      unset($area['id']);
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
  foreach ($entries as &$entry) {
    $entry['status'] = $entry['start_time'] > time() ? 0 : ($entry['end_time'] < time() ? 2 : 1);
  }
  unset($entry);
  $area = db() -> query("SELECT id as area_id, area_name, disabled, morningstarts, morningstarts_minutes, eveningends, eveningends_minutes, resolution, parent_id FROM " . _tbl("area") . " WHERE id = ?", array($id)) -> next_row_keyed();
  $root = $area['parent_id'];
  $rooms = [];
  $areas[] = $area;
  $parent_ids[] = $area['area_id'];
  while(1){
    $parent_string = "(";
    foreach ($parent_ids as $parent_id) {
      $parent_string .= $parent_id . ",";
    }
    $parent_string = substr($parent_string, 0, -1);
    $parent_string .= ")";
    $tmp_a = db() -> query("SELECT id as area_id, area_name, disabled, morningstarts, morningstarts_minutes, eveningends, eveningends_minutes, resolution, parent_id FROM " . _tbl("area") . " WHERE parent_id in " . $parent_string);
    $count = $tmp_a -> count();
    $tmp_a = $tmp_a -> all_rows_keyed();
    $areas = array_merge($areas, $tmp_a);
    $tmp_r = db() -> query("SELECT id as room_id, disabled, description, capacity, area_id, room_name FROM " . _tbl("room") . " WHERE area_id in " . $parent_string) -> all_rows_keyed();
    $rooms = array_merge($rooms, $tmp_r);

    if ($count == 0){
      break;
    }
    $parent_ids = [];
    foreach ($tmp_a as $item) {
      $parent_ids[] = $item['area_id'];
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
      if ($entry['room_id'] == $room['room_id']){
        $item = [];
        $item['entry_id'] = $entry['id'];
        $item['start_time'] = $entry['start_time'];
        $item['end_time'] = $entry['end_time'];
        $item['entry_name'] = $entry['name'];
        $item['book_by'] = $entry['book_by'];
        $item['status'] = $entry['start_time'] > time() ? 0 : ($entry['end_time'] < time() ? 2 : 1);
        $item['duration'] = date("h:iA", intval($entry['start_time'])) . "-" . date("h:iA", intval($entry['end_time']));
        $item['room_name'] = $room['room_name'];
        $item['repeat_id'] = $entry['repeat_id'];
        $room['entries'][] = $item;
      }
    }
    unset($room);
  }

  foreach ($rooms as $room) {
    foreach ($areas as &$area) {
      if ($room['area_id'] == $area['area_id']) {
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

ApiHelper::success($data);
