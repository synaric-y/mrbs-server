<?php

declare(strict_types=1);

namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$id = $data['id'];

$response = array(
  "code" => 'int',
  "message" => 'string',
);

if (empty($id)){
  $response["code"] = -1;
  $response["message"] = "id cannot be empty";
  echo json_encode($response);
  return;
}

$result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE id = ?", array($id));
if ($result -> count() === 0){
  $response["code"] = -2;
  $response["message"] = "entry not found";
  echo json_encode($response);
  return;
}



$row = $result -> next_row_keyed();
$row['start_time'] = intval($row['start_time']);
$row['end_time'] = intval($row['end_time']);
$result = db() -> query("SELECT room_name FROM " . _tbl("room") . " WHERE id = ?", array($row['room_id']));
$row['room_name'] = $result -> next_row_keyed()['room_name'];
$response['code'] = 0;
$response['message'] = "success";
$response['data'] = $row;
echo json_encode($response);
