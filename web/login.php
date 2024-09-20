<?php
declare(strict_types=1);

namespace MRBS;

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

header('Content-Type: application/json');

/*
 * 用于登录的接口
 * @Params
 * username：登录用户名
 * password：登录密码
 * @Return
 * 无，但是会设置js不可更改的cookie
 */

$username = $_POST['username'];
$password = $_POST['password'];

if (!empty($_SESSION) && isset($_SESSION['user'])) {
  ApiHelper::fail(get_vocab("already_login"), ApiHelper::ALREADY_LOGIN);
}
setcookie("session_id", "", time() - 3600, "/web/");
$result = auth() -> validateUser($username, $password);
if (!$result) {
  ApiHelper::fail(get_vocab("invalid_username_or_password"), ApiHelper::INVALID_USERNAME_OR_PASSWORD);
}
$_SESSION['user'] = $username;
setcookie("session_id", session_id(), time() + 30 * 24 * 60 * 60, "/web/", "", false, true);
$result = db() -> query("SELECT level, display_name FROM " . _tbl("users") . " WHERE name = ?", array($username));
$row = $result -> next_row_keyed();
$data = array(
  "username" => $username,
  "level" => $row['level'],
  "display_name" => $row['display_name']
);
session_write_close();
ApiHelper::success($data);
