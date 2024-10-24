<?php
declare(strict_types=1);

namespace MRBS;

header('Content-Type: application/json');

/*
 * login in device
 * @Params
 * username：登录用户名
 * password：登录密码
 * @Return
 * no return value but cookie
 */

$username = $_POST['username'];
$password = $_POST['password'];

if (!empty($_SESSION) && isset($_SESSION['user'])) {
  ApiHelper::fail(get_vocab("already_login"), ApiHelper::ALREADY_LOGIN);
}
setcookie("session_id", "", time() - 3600, "/web/appapi/");
$result = auth() -> validateUser($username, $password);
if (!$result) {
  ApiHelper::fail(get_vocab("invalid_username_or_password"), ApiHelper::INVALID_USERNAME_OR_PASSWORD);
}
// specially, when someone log in on device, the account must be a manager
if (getLevel($username) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}
$_SESSION['user'] = $username;
setcookie("session_id", session_id(), time() + 24 * 60 * 60, "/web/appapi/", "", false, true);
$result = db() -> query("SELECT level, display_name FROM " . _tbl("users") . " WHERE name = ?", array($username));
$row = $result -> next_row_keyed();
$data = array(
  "username" => $username,
  "level" => $row['level'],
  "display_name" => $row['display_name']
);
session_write_close();
ApiHelper::success($data);
