<?php


declare(strict_types=1);

namespace MRBS;

/*
 * get all entries(include the entries which is passed)
 */

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

global $time_type;

$vars = array(
  "status",                   //0未开始，1进行中，2已结束
  "area_id",
  "room_id",
  "create_by",
  "awaiting_approval"
);

$pagesize = $_POST['pagesize'] ?? 20;
$pagenum = $_POST['pagenum'] ?? 1;

foreach ($vars as $key => $var) {
  if (!isset($_POST[$var]) || $$var === '') {
    unset($vars[$key]);
  } else {
    $$var = $_POST[$var];
    if ($var === 'status') {
      unset($vars[$key]);
    }
  }
}

$sql = "SELECT E.*, R.room_name as room_name, A.area_name as area_name FROM " . _tbl("entry") .
  " E LEFT JOIN " . _tbl("room") . " R ON E.room_id = R.id LEFT JOIN " . _tbl("area") .
  " A ON R.area_id = A.id " . " WHERE repeat_id IS NULL";

$params = [];
$vars = array_values($vars);
if (!empty($vars)) {
  $sql .= " AND ";
  for ($i = 0; $i < count($vars); $i++) {
    $var = $vars[$i];
    if ($var === 'room_id') {
      $sql .= "R.id = ?";
      $params[] = $$var;
    } else if ($var === 'area_id') {
      $sql .= "A.id = ?";
      $params[] = $$var;
    } else if ($var === 'create_by') {
      $sql .= "E.create_by = ?";
      $params[] = $$var;
    } else if ($var === 'awaiting_approval') {
      $sql .= "E.status&" . STATUS_AWAITING_APPROVAL . " = ?";
      $params[] = $$var;
    }
    if ($i != count($vars) - 1) {
      $sql .= " AND ";
    }
  }
}

if (isset($status)) {
  $sql .= " AND ";
  if ($status === 0) {
    $sql .= "E.start_time >= ?";
    $params[] = time();
  } else if ($status === 1) {
    $sql .= "E.start_time <= ? AND E.end_time >= ?";
    $params[] = time();
    $params[] = time();
  } else if ($status === 2) {
    $sql .= "E.end_time <= ?";
    $params[] = time();
  }
}


$result = db()->query($sql, $params);

$sql = str_replace("E.*, R.room_name as room_name, A.area_name as area_name", "COUNT(*)", $sql);
$total_num = db()->query1($sql, $params);

$data = array();
while ($row = $result->next_row_keyed()) {
  $row['start_time'] = intval($row['start_time']);
  $row['end_time'] = intval($row['end_time']);
  $entry = array();
  $entry['id'] = $row['id'];
  $entry['name'] = $row['name'];
  $entry['room_name'] = $row['room_name'];
  $entry['area_name'] = $row['area_name'];
  $entry['start_time'] = $row['start_time'];
  $entry['end_time'] = $row['end_time'];
  $entry['approval_status'] = $row['status'];
  $entry['is_repeat'] = 0;
  $entry['status'] = $entry['start_time'] > time() ? 0 : ($entry['end_time'] < time() ? 2 : 1);
  if ($time_type == 24) {
    $entry['duration'] = date("H:i:s", $row['start_time']) . " - " . date("H:i:s", $row['end_time']);
  } else if ($time_type == 12) {
    $entry['duration'] = date("h:i:s A", $row['start_time']) . " - " . date("h:i:s A", $row['end_time']);
  }
  $entry['create_by'] = $row['create_by'];
  $data[] = $entry;
}

$sql = "SELECT E.repeat_id as repeat_id, MIN(E.start_time) as start_time, MAX(E.end_time) as end_time FROM " . _tbl("entry") .
  " E LEFT JOIN " . _tbl("room") . " R ON E.room_id = R.id LEFT JOIN " . _tbl("area") .
  " A ON R.area_id = A.id " . " WHERE repeat_id IS NOT NULL";

$params = [];
if (!empty($vars)) {
  $sql .= " AND ";
  for ($i = 0; $i < count($vars); $i++) {
    $var = $vars[$i];
    if ($var === 'room_id') {
      $sql .= "R.id = ?";
      $params[] = $$var;
    } else if ($var === 'area_id') {
      $sql .= "A.id = ?";
      $params[] = $$var;
    } else if ($var === 'create_by') {
      $sql .= "E.create_by = ?";
      $params[] = $$var;
    } else if ($var === 'awaiting_approval') {
      $sql .= "E.status&" . STATUS_AWAITING_APPROVAL . " = ?";
      $params[] = $$var;
    }
    if ($i != count($vars) - 1) {
      $sql .= " AND ";
    }
  }
}

$sql .= " GROUP BY E.repeat_id";
if (isset($status)) {
  $sql .= " HAVING ";
  if ($status === 0) {
    $sql .= "MIN(E.start_time) >= ?";
    $params[] = time();
  } else if ($status === 1) {
    $sql .= "MIN(E.start_time) <= ? AND MAX(E.end_time) >= ?";
    $params[] = time();
    $params[] = time();
  } else if ($status === 2) {
    $sql .= "MAX(E.end_time) <= ?";
    $params[] = time();
  }
}

$result = db()->query($sql, $params);
$sql = "SELECT COUNT(*) FROM (" . $sql . ") AS subquery";
$total_num += db()->query1($sql, $params);

while ($row = $result->next_row_keyed()) {
  $repeat = db()->query("SELECT Re.*, R.room_name as room_name, A.area_name as area_name FROM " . _tbl("repeat") .
    " Re LEFT JOIN " . _tbl("room") . " R ON Re.room_id = R.id LEFT JOIN " . _tbl("area") .
    " A ON R.area_id = A.id WHERE Re.id = ?", array($row['repeat_id']))->next_row_keyed();
  $repeat['start_time'] = intval($repeat['start_time']);
  $repeat['end_time'] = intval($repeat['end_time']);
  $entry = array();
  $entry['id'] = $repeat['id'];
  $entry['name'] = $repeat['name'];
  $entry['room_name'] = $repeat['room_name'];
  $entry['area_name'] = $repeat['area_name'];
  $entry['start_time'] = $repeat['start_time'];
  $entry['end_time'] = $repeat['end_time'];
  if ($time_type == 24) {
    $entry['duration'] = date("H:i:s", $repeat['start_time']) . " - " . date("H:i:s", $repeat['end_time']);
  }
  else if ($time_type == 12) {
    $entry['duration'] = date("h:i:s A", $repeat['start_time']) . " - " . date("h:i:s A", $repeat['end_time']);
  }
  $entry['duration'] .= " 每";
  for ($i = 0; $i < 7; $i++) {
    if ($repeat['rep_opt'][$i] == '1') {
      switch ($i) {
        case 0:
          $entry['duration'] .= get_vocab("duration.0");
          break;
        case 1:
          $entry['duration'] .= get_vocab("duration.1");
          break;
        case 2:
          $entry['duration'] .= get_vocab("duration.2");
          break;
        case 3:
          $entry['duration'] .= get_vocab("duration.3");
          break;
        case 4:
          $entry['duration'] .= get_vocab("duration.4");
          break;
        case 5:
          $entry['duration'] .= get_vocab("duration.5");
          break;
        case 6:
          $entry['duration'] .= get_vocab("duration.6");
          break;
      }
    }
  }
  $entry['is_repeat'] = 1;
//  $entry['status'] = $entry['start_time'] > time() ? 0 : ($entry['end_time'] < time() ? 2 : 1);
  $entry['status'] = $status ?? ($entry['start_time'] > time() ? 0 : ($entry['end_time'] < time() ? 2 : 1));
  $entry['create_by'] = $repeat['create_by'];
  $entry['days_of_week'] = $repeat['rep_opt'];
  $data[] = $entry;
}

usort($data, function ($a, $b) {
  return intval($a['start_time']) <=> intval($b['start_time']);
});

$offset = ($pagenum - 1) * $pagesize;

$entries = array_slice($data, $offset, $pagesize);

ApiHelper::success(["entries" => $entries, "total" => $total_num]);
