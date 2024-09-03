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
  $response['code'] = -99;
  $response['message'] = get_vocab("please_login");
  echo json_encode($response);
  return;
}
$username = $_SESSION['user'];

session_write_close();

$sql = "SELECT id, level, name, display_name, email FROM " . _tbl("users");
$result = db() -> query($sql);

if ($result -> count() == 0){
  $response['code'] = -1;
  $response['message'] = 'No users found';
  echo json_encode($response);
  return;
}else{
  $response['code'] = 0;
  $response['message'] = 'success';
  $response['data'] = $result -> all_rows_keyed();
  echo json_encode($response);
}
