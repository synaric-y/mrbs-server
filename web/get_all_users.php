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

session_start();
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
$username = $_SESSION['user'];

session_write_close();

$sql = "SELECT id, level, name, display_name, email FROM " . _tbl("users");
$result = db() -> query($sql);

if ($result -> count() == 0){
  $response['code'] = -1;
  $response['message'] = get_vocab("user_not_exist");
  echo json_encode($response);
  return;
}else{
  while($row = $result -> next_row_keyed()){
    if ($row['name'] == $username){
      $row['is_self'] = 1;
    }else{
      $row['is_self'] = 0;
    }
    $response['data'][] = $row;
  }
  $response['code'] = 0;
  $response['message'] = get_vocab('success');
  echo json_encode($response);
}
