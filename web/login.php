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

if (isset($_COOKIE)){
  $session_id = $_COOKIE['session_id'];
  $session = DBHelper::one(_tbl("sessions"), "id = '$session_id'");
  if ($session) {
    session_decode($session['data']);
    if ($_SESSION['user'] == $username) {
      $response = array(
        "code" => 1,
        "message" => "already login"
      );
      echo json_encode($response);
      return;
    }
  }
}

$result = auth() -> validateUser($username, $password);
if (!$result) {
  $response = array(
    "code" => -1,
    "message" => "invalid username or password"
  );
  echo json_encode($response);
  return;
}

session_regenerate_id(false);
$_SESSION['user'] = $username;
setcookie("session_id", session_id(), [
  "httponly" => true
]);
$response = array(
  "code" => 0,
  "message" => "success"
);
session_write_close();
echo json_encode($response);
