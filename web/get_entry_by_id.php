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

if (!checkAuth()){
  $response["code"] = -99;
  $response["message"] = get_vocab("please_login");
  echo json_encode($response);
  return;
}

if (getLevel($_SESSION['user']) < 2){
  $response["code"] = -98;
  $response["message"] = get_vocab("accessdenied");
  echo json_encode($response);
  return;
}

if (empty($id)){
  $response["code"] = -1;
  $response["message"] = get_vocab("search_without_id");
  echo json_encode($response);
  return;
}

$result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE id = ?", array($id));
if ($result -> count() === 0){
  $response["code"] = -2;
  $response["message"] = get_vocab("entry_not_exist");
  echo json_encode($response);
  return;
}



$row = $result -> next_row_keyed();
$row['start_time'] = intval($row['start_time']);
$row['end_time'] = intval($row['end_time']);
$result = db() -> query("SELECT room_name FROM " . _tbl("room") . " WHERE id = ?", array($row['room_id']));
$row['room_name'] = $result -> next_row_keyed()['room_name'];
$response['code'] = 0;
$response['message'] = get_vocab("success");
$response['data'] = $row;
echo json_encode($response);
