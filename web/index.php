<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementSelect;
use MRBS\Form\Form;
use MRBS\Intl\IntlDateFormatter;
use OpenPsa\Ranger\Ranger;

require "defaultincludes.inc";
require_once "functions_table.inc";
require_once "mrbs_sql.inc";

global $datetime_formats;

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$type = $data['type'];
$id = $data['id'];
$start_time = $data['start_time'];
$end_time = $data['end_time'];
$timezone = $data['timezone'];
$response = array(
  "code" => 'int',
  "message" => 'string'
);

if ($type != 'all') {
  $roomExist = db()->query1("SELECT COUNT(*) FROM " . _tbl($type) . " WHERE id = ?", array($id));
  if ($roomExist <= 0) {
    $response["code"] = -2;
    $response["message"] = get_vocab($type . "_not_exist");
    echo json_encode($response);
    return;
  }
}

$sql = "SELECT E.id AS id, area_id, room_id, start_time, end_time, E.name AS name, book_by, morningstarts, morningstarts_minutes, eveningends, eveningends_minutes, R.room_name, area_name, A.disabled as area_disabled, R.disabled as room_disabled, timezone  FROM " . _tbl("entry") . " E LEFT JOIN " . _tbl("room") .
" R ON E.room_id = R.id " . "LEFT JOIN " . _tbl("area") . " A ON R.area_id = A.id";
if ($type == 'area'){
  $sql .= " WHERE A.id = ? AND start_time >= ? AND end_time <= ?";
}else if ($type == 'room') {
  $sql .= " WHERE R.id = ? AND start_time >= ? AND end_time <= ?";
} else if($type != 'all'){
  $response['code'] = -1;
  $response['message'] = get_vocab("invalid_types");
  echo json_encode($response);
  return;
}else{
  $sql .= " WHERE start_time >= ? AND end_time <= ?";
}

if ($type != 'all')
  $result = db() -> query($sql, array($id, $start_time, $end_time));
else
  $result = db() -> query($sql, array($start_time, $end_time));

if ($result -> count() < 1){
  $result = db() -> query("SELECT * FROM " . _tbl("area"));
  $rows = $result -> all_rows_keyed();
  usort($rows, function ($a, $b) {
    if ($a['morningstarts'] == $b['morningstarts']) {
      return $a['morningstarts_minutes'] <=> $b['morningstarts_minutes'];
    }
    return $a['morningstarts'] <=> $b['morningstarts'];
  });
  if (empty($rows[0]['morningstarts']))
    $rows[0]['morningstarts'] = 8;
  if (empty($rows[0]['morningstarts_minutes']))
    $rows[0]['morningstarts_minutes'] = 0;
  $min_time = sprintf("%02d", $rows[0]['morningstarts'] > 12 ? $rows[0]['morningstarts'] - 12 : $rows[0]['morningstarts']) . ":" . sprintf("%02d", $rows[0]['morningstarts_minutes']) . ($rows[0]['morningstarts'] > 12 ? " PM" : " AM");
  usort($rows, function ($a, $b) {
    if ($a['eveningends'] == $b['eveningends']) {
      return $a['eveningends_minutes'] <=> $b['eveningends_minutes'];
    }
    return $a['eveningends'] <=> $b['eveningends'];
  });
  if (empty($rows[count($rows) - 1]['eveningends']))
    $rows[count($rows) - 1]['eveningends'] = 21;
  if (empty($rows[count($rows) - 1]['eveningends_minutes']))
    $rows[count($rows) - 1]['eveningends_minutes'] = 0;
  $max_time = sprintf("%02d", $rows[count($rows) - 1]['eveningends'] > 12 ? $rows[count($rows) - 1]['eveningends'] - 12 : $rows[count($rows) - 1]['eveningends']) . ":" . sprintf("%02d", $rows[count($rows) - 1]['eveningends_minutes']) . ($rows[count($rows) - 1]['eveningends'] > 12 ? " PM" : " AM");
  $response["code"] = 0;
  $response["message"] = get_vocab("success");
  $response["data"] = array();
  $response['data']['min_time'] = $min_time;
  $response['data']['max_time'] = $max_time;
  $date = datetime_format($datetime_formats['view_day'], time());
  $response['data']['time'] = $date;
  $response['data']['areas'] = array();
  echo json_encode($response);
  return;
}
$rows = $result -> all_rows_keyed();
$default_timezone = date_default_timezone_get();
foreach ($rows as $row) {
  if (!empty($row['timezone']))
    date_default_timezone_set($timezone);
  else
    date_default_timezone_set($default_timezone);
  $areaId = $row['area_id'];
  $roomId = $row['room_id'];
  if (!isset($tmp[$areaId])){
    $tmp[$areaId] = array(
      'area_id' => $areaId,
      'area_name' => $row['area_name'],
      'disabled' => $row['area_disabled'],
      'rooms' => array()
    );
  }

  if (!isset($tmp[$areaId]['rooms'][$roomId])){
    $tmp[$areaId]['rooms'][$roomId] = array(
      'room_id' => $roomId,
      'disabled' => $row['area_disabled'] == 1 ? 1 : $row['room_disabled'],
      'entries' => array()
    );
  }

  if (time() < $row['start_time'])
    $status = 0;
  else if (time() > $row['end_time'])
    $status = 2;
  else
    $status = 1;

  $tmp[$areaId]['rooms'][$roomId]['entries'][] = array(
    "entry_id" => $row['id'],
    "start_time" => $row['start_time'],
    "end_time" => $row['end_time'],
    "entry_name" => $row['name'],
    "book_by" => $row['book_by'],
    "status" => $status,
    "duration" => date("h:iA", intval($row['start_time'])) . "-" . date("h:iA", intval($row['end_time'])),
    "room_name" => $row['room_name']
  );
}
date_default_timezone_set($default_timezone);
$result = array(
  'areas' => array_values($tmp)
);
foreach ($result['areas'] as &$area) {
  $area['rooms'] = array_values($area['rooms']);
}

usort($rows, function ($a, $b) {
  if ($a['morningstarts'] == $b['morningstarts']) {
    return $a['morningstarts_minutes'] <=> $b['morningstarts_minutes'];
  }
  return $a['morningstarts'] <=> $b['morningstarts'];
});
if (empty($rows[0]['morningstarts']))
  $rows[0]['morningstarts'] = 8;
if (empty($rows[0]['morningstarts_minutes']))
  $rows[0]['morningstarts_minutes'] = 0;
$min_time = sprintf("%02d", $rows[0]['morningstarts'] > 12 ? $rows[0]['morningstarts'] - 12 : $rows[0]['morningstarts']) . ":" . sprintf("%02d", $rows[0]['morningstarts_minutes']) . ($rows[0]['morningstarts'] > 12 ? " PM" : " AM");
usort($rows, function ($a, $b) {
  if ($a['eveningends'] == $b['eveningends']) {
    return $a['eveningends_minutes'] <=> $b['eveningends_minutes'];
  }
  return $a['eveningends'] <=> $b['eveningends'];
});
if (empty($rows[count($rows) - 1]['eveningends']))
  $rows[count($rows) - 1]['eveningends'] = 21;
if (empty($rows[count($rows) - 1]['eveningends_minutes']))
  $rows[count($rows) - 1]['eveningends_minutes'] = 0;
$max_time = sprintf("%02d", $rows[count($rows) - 1]['eveningends'] > 12 ? $rows[count($rows) - 1]['eveningends'] - 12 : $rows[count($rows) - 1]['eveningends']) . ":" . sprintf("%02d", $rows[count($rows) - 1]['eveningends_minutes']) . ($rows[count($rows) - 1]['eveningends'] > 12 ? " PM" : " AM");

$now = time();
$date = datetime_format($datetime_formats['view_day'], $now);

$response['code'] = 0;
$response['message'] = get_vocab("success");
$response['data'] = $result;
$response['data']['min_time'] = $min_time;
$response['data']['max_time'] = $max_time;
$response['data']['time'] = $date;
$response['data']['timestamp'] = $now;
echo json_encode($response);
