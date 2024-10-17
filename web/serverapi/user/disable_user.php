<?php


namespace MRBS;

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$id = $_POST['userid'];
$disabled = $_POST['disabled'];
if (empty($id)){
  ApiHelper::fail(get_vocab("edit_without_id"), ApiHelper::EDIT_WITHOUT_ID);
}

db()->command("UPDATE " . _tbl("users") . " SET disabled = ? WHERE id = ?", array($disabled, $id));

ApiHelper::success(null);
