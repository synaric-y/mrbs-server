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
  ApiHelper::fail(get_vocab("already_login"), ApiHelper::ALREADY_LOGIN);
}
setcookie("session_id", "", time() - 3600, "/web/");
$result = auth() -> validateUser($username, $password);
if (!$result) {
  ApiHelper::fail(get_vocab("invalid_username_or_password"), ApiHelper::INVALID_USERNAME_OR_PASSWORD);
}
$_SESSION['user'] = $username;
setcookie("session_id", session_id(), time() + 30 * 24 * 60 * 60, "/web/");
$result = db() -> query("SELECT level, display_name FROM " . _tbl("users") . " WHERE name = ?", array($username));
$row = $result -> next_row_keyed();
$data = array(
  "username" => $username,
  "level" => $row['level'],
  "display_name" => $row['display_name']
);
session_write_close();
ApiHelper::success($data);
