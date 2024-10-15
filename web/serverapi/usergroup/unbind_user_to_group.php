<?php

namespace MRBS;


if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$user_id = intval($_POST['user_id']);
$group_id = intval($_POST['group_id']);

if (empty($user_id) || empty($group_id) || $group_id < 0) {
  ApiHelper::fail(get_vocab("missing_parameters"), ApiHelper::MISSING_PARAMETERS);
}

$userInfo = DBHelper::one(_tbl("users"), "id = $user_id");
if (empty($userInfo)) {
  ApiHelper::fail(get_vocab("user_not_exist"), ApiHelper::USER_NOT_EXIST);
}
$groupInfo = DBHelper::one(_tbl("user_group"), "id = $group_id");
if (empty($groupInfo)) {
  ApiHelper::fail(get_vocab("group_not_exist"), ApiHelper::GROUP_NOT_EXIST);
}

$relation = DBHelper::one(_tbl("u2g_map"), "user_id = $user_id and parent_id = $group_id");
if (empty($relation) || $relation['source'] != 'system') {
  ApiHelper::fail(get_vocab("group_cannot_unbind"), ApiHelper::GROUP_CANNOT_UNBIND);
}

$success = unbind_user_to_group($user_id, $group_id);

$result = array();
ApiHelper::success($result);
