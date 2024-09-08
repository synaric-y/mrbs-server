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

if (!checkAuth()){
  $response["code"] = -99;
  $response["message"] = get_vocab("please_login");
  setcookie("session_id", "", time() - 3600, "/web/");
  echo json_encode($response);
  return;
}

if (getLevel($_SESSION['user']) < 2){
  $response["code"] = -98;
  $response["message"] = get_vocab("accessdenied");
  echo json_encode($response);
  return;
}

if ($type == 'area'){
  $result = db() -> query("SELECT * FROM " . _tbl("area") . " WHERE id = ?", array($id));
}else if ($type == 'room'){
  $result = db() -> query("SELECT * FROM " . _tbl("room") . " WHERE id = ?", array($id));
}else if ($type == 'device'){

}else{
  $response['code'] = -1;
  $response['message'] = get_vocab("wrong_type");
  echo json_encode($response);
  return;
}
if ($result -> count() < 1){
  $response['code'] = -2;
  $response['message'] = get_vocab($type . "_not_exist");
  echo json_encode($response);
  return;
}

$response['code'] = 0;
$response['message'] = get_vocab('success');
while($row = $result -> next_row_keyed()){
  $response['data'][] = $row;
}
echo json_encode($response);
