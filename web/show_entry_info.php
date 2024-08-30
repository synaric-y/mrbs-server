<?php
declare(strict_types=1);

namespace MRBS;

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id = intval($data['id']);
$response = array(
  "code" => 'int',
  "message" => 'string',
);

if (!isset($id) || $id === '' || $id == 0) {
  $response['code'] = -1;
  $response['message'] = 'missing id or id is not a number';
  echo json_encode($response);
  return;
}

$result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE id = ?", array($id));
if ($result -> count() < 1){
  $response['code'] = -2;
  $response['message'] = 'entry ' . $id . ' not found';
  echo json_encode($response);
  return;
}

$response['code'] = 0;
$response['message'] = 'success';
$response['data'] = $result ->next_row_keyed();
echo json_encode($response);
