<?php


namespace MRBS;

/*
 * reset password of a user
 * password0 should be the same with password1
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}
$username = $_SESSION['user'];

$id = $_POST['id'];
$password0 = $_POST["password0"];
$password1 = $_POST["password1"];

if ($password1 != $password0){
  ApiHelper::fail(get_vocab("passwords_not_eq"), ApiHelper::PASSWORDS_NOT_EQ);
}

$one = db() -> query1("SELECT * FROM " . _tbl("users") . " WHERE id = ?", array($id));
if($one < 1){
  ApiHelper::fail(get_vocab("user_not_exist"), ApiHelper::USER_NOT_EXIST);
}
// TODO check if the password obey the rule


$password_hash = password_hash($password0, PASSWORD_DEFAULT);

db()->command("UPDATE " . _tbl("users") . " SET password_hash = ? WHERE id = ?", array($password_hash, $id));
ApiHelper::success(null);
