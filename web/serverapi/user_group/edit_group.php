<?php

namespace MRBS;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$group_id = $_POST['group_id'];
$name = $_POST['name'];
$third_id = $_POST['third_id'];

$group = DBHelper::one(_tbl("user_group"), "id = $group_id");
if (empty($group)) {
  ApiHelper::fail(get_vocab("group_not_exist"), ApiHelper::GROUP_NOT_EXIST);
}
if ($group['source'] == 'ad') {
  ApiHelper::fail(get_vocab("group_cannot_modify"), ApiHelper::GROUP_CANNOT_DEL_OR_UPDATE);
}

edit_user_group($_POST);

$result = array();

ApiHelper::success($result);