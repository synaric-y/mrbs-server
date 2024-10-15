<?php

use MRBS\ApiHelper;
use MRBS\DBHelper;
use function MRBS\_tbl;
use function MRBS\bind_user_to_group;
use function MRBS\checkAuth;
use function MRBS\get_vocab;
use function MRBS\getLevel;



if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$user_id = $_POST['user_id'];
$group_id = $_POST['group_id'];

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

$success = bind_user_to_group($user_id, $group_id, "system");

$result = array();
ApiHelper::success($result);
