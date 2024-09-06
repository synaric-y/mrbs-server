<?php
declare(strict_types=1);

namespace MRBS;

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$username = $data['username'];
$password = $data['password'];

if (!empty($_SESSION) && isset($_SESSION['user'])) {
  $response = array(
    "code" => 1,
    "message" => get_vocab("already_login")
  );
  echo json_encode($response);
  return;
}

$result = auth() -> validateUser($username, $password);
if (!$result) {
  $response = array(
    "code" => -1,
    "message" => get_vocab("invalid_username_or_password")
  );
  echo json_encode($response);
  return;
}
$_SESSION['user'] = $username;
$result = db() -> query("SELECT level, display_name FROM " . _tbl("users") . " WHERE name = ?", array($username));
$row = $result -> next_row_keyed();
$response = array(
  "code" => 0,
  "message" => get_vocab("success"),
  "data" => array(
    "username" => $username,
    "level" => $row['level'],
    "display_name" => $row['display_name']
  )
);
session_write_close();
echo json_encode($response);
