<?php
declare(strict_types=1);

namespace MRBS;


/*
 * get user information by user id
 * @Params
 * id：user id
 * @Return
 * user information
 */

$id = $_POST['id'];

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

session_write_close();


if (empty($id)) {
  ApiHelper::fail(get_vocab("search_without_id"), ApiHelper::SEARCH_WITHOUT_ID);
}

$result = db()->query("SELECT * FROM " . _tbl("users") . " WHERE id = ?", array($id));

if ($result->count() < 1) {
  ApiHelper::fail(get_vocab("user_not_exist"), ApiHelper::USER_NOT_EXIST);
}

$user = $result->next_row_keyed();
unset($user['password_hash']);
unset($user['timestamp']);
unset($user['reset_key_hash']);
unset($user['reset_key_expiry']);
if (!empty($user['last_login']))
  $user['last_login'] = date('Y-m-d h:i:s A', intval($user['last_login']));
else
  $user['last_login'] = get_vocab('never_login');
ApiHelper::success($user);
