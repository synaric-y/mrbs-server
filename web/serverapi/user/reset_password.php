<?php


namespace MRBS;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}
$username = $_SESSION['user'];

$password0 = $_POST["password0"];
$password1 = $_POST["password1"];

if ($password1 != $password0){
  ApiHelper::fail(get_vocab("passwords_not_eq"), ApiHelper::PASSWORDS_NOT_EQ);
}

// TODO check if the password obey the rule


$password_hash = password_hash($password0, PASSWORD_DEFAULT);

db()->command("UPDATE " . _tbl("users") . " SET password_hash = ? WHERE name = ?", array($password_hash, $username));
ApiHelper::success(null);
