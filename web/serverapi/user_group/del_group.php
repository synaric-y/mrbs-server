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

$group = DBHelper::one(_tbl("user_group"), "id = $group_id");
if (empty($group)) {
  ApiHelper::fail(get_vocab("group_not_exist"), ApiHelper::GROUP_NOT_EXIST);
}
if ($group['source'] == 'ad') {
  ApiHelper::fail(get_vocab("group_cannot_delete"), ApiHelper::GROUP_CANNOT_DELETE);
}
$peek = DBHelper::one(_tbl("g2g_map"), " parent_id = $group_id");
if (!empty($peek)) {
  ApiHelper::fail(get_vocab("group_cannot_delete"), ApiHelper::GROUP_CANNOT_DELETE);
}

del_user_group($group_id);

$result = array();
ApiHelper::success($result);
