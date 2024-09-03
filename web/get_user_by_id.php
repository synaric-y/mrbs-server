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
  "response" => 'string'
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


if (empty($id)){
  $response['code'] = -1;
  $response['message'] = 'id cannot be empty';
  echo json_encode($response);
  return;
}

$result = db() -> query("SELECT * FROM " . _tbl("users") . " WHERE id = ?", array($id));

if ($result -> count() < 1){
  $response['code'] = -2;
  $response['message'] = 'user not found';
  echo json_encode($response);
  return;
}

$user = $result -> next_row_keyed();
unset($user['password_hash']);
unset($user['timestamp']);
unset($user['reset_key_hash']);
unset($user['reset_key_expiry']);
$user['last_login'] = date('Y-m-d h:i:s A', intval($user['last_login']));

$response['code'] = 0;
$response['data'] = $user;
$response['message'] = 'success';
echo json_encode($response);
