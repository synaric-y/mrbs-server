<?php
declare(strict_types=1);
namespace MRBS;


require "defaultincludes.inc";
require_once "mrbs_sql.inc";


$json = file_get_contents('php://input');
$data = json_decode($json, true);

$type = $data['type'];
$id = $data['id'];
$response = array(
  "code" => 'int',
  "message" => 'string',
  "data" => null
);

if ($type == 'area'){
  $result = db() -> query("SELECT * FROM " . _tbl("area") . " WHERE id = ?", array($id));
}else if ($type == 'room'){
  $result = db() -> query("SELECT * FROM " . _tbl("room") . " WHERE id = ?", array($id));
}else if ($type == 'device'){

}else{
  $response['code'] = -1;
  $response['message'] = 'Invalid type';
  echo json_encode($response);
  return;
}
if ($result -> count() < 1){
  $response['code'] = -2;
  $response['message'] = 'No ' . $type . ' found';
  echo json_encode($response);
  return;
}

$response['code'] = 0;
$response['message'] = 'success';
while($row = $result -> next_row_keyed()){
  $response['data'][] = $row;
}
echo json_encode($response);
