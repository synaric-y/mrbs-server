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

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$type = $data['type'];
$id = $data['id'];
$start_time = $data['start_time'];
$end_time = $data['end_time'];
$response = array(
  "code" => 'int',
  "message" => 'string'
);

$sql = "SELECT E.id AS id, area_id, room_id, start_time, end_time, E.name AS name, book_by  FROM " . _tbl("entry") . " E LEFT JOIN " . _tbl("room") .
" R ON E.room_id = R.id " . "LEFT JOIN " . _tbl("area") . " A ON R.area_id = A.id";
if ($type == 'area'){
  $sql .= " WHERE A.id = ? AND start_time >= AND end_time <= ?";
}else if ($type == 'room') {
  $sql .= " WHERE R.id = ? AND start_time >= ? AND end_time <= ?";
} else if($type != 'all'){
  $response['code'] = -1;
  $response['message'] = 'Invalid type';
  echo json_encode($response);
  return;
}else{
  $sql .= " WHERE start_time >= ? AND end_time <= ?";
}

if ($type != 'all')
  $result = db() -> query($sql, array($id, $start_time, $end_time));
else
  $result = db() -> query($sql, array($start_time, $end_time));
$rows = $result -> all_rows_keyed();

foreach ($rows as $row) {
  $areaId = $row['area_id'];
  $roomId = $row['room_id'];
  if (!isset($tmp[$areaId])){
    $tmp[$areaId] = array(
      'area_id' => $areaId,
      'rooms' => array()
    );
  }

  if (!isset($tmp[$areaId]['rooms'][$roomId])){
    $tmp[$areaId]['rooms'][$roomId] = array(
      'room_id' => $roomId,
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
    "year" => date("Y", intval($row['start_time'])),
    "month" => date("m", intval($row['start_time'])),
    "day" => date("d", intval($row['start_time'])),
    "week" => date("l", intval($row['start_time'])),
    "duration" => date("h:i:s A", intval($row['start_time'])) . "-" . date("h:i:s A", intval($row['end_time']))
  );
}

$result = array(
  'data' => array_values($tmp)
);
foreach ($result['data'] as &$area) {
  $area['rooms'] = array_values($area['rooms']);
}

$response['code'] = 0;
$response['message'] = "success";
$response['data'] = $result;
echo json_encode($response);
