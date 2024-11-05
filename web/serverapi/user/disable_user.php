<?php


namespace MRBS;

/*
 * set the disabled status of a user
 */

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}


$id = $_POST['userid'];
$disabled = $_POST['disabled'];
if (empty($id)){
  ApiHelper::fail(get_vocab("edit_without_id"), ApiHelper::EDIT_WITHOUT_ID);
}

$result = db() -> query("SELECT * FROM " . _tbl("users") . " WHERE id = ?", array($id));
$user = $result->next_row_keyed();
if ($result -> count() < 1){
  ApiHelper::fail(get_vocab("user_not_exist"), ApiHelper::USER_NOT_EXIST);
} elseif ($user['source'] == 'ad') {
  ApiHelper::fail(get_vocab("user_not_exist"), ApiHelper::USER_CANNOT_EDIT);
}else{
  $row = $result -> next_row_keyed();
  if ($row['name'] == $_SESSION['user']){
    ApiHelper::fail(get_vocab("disabled_self"), ApiHelper::DISABLED_SELF);
  }
}

db()->command("UPDATE " . _tbl("users") . " SET disabled = ? WHERE id = ?", array($disabled, $id));

ApiHelper::success(null);
