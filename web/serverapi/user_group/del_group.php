<?php

namespace MRBS;

/*
 * Delete a User Group.
 * @Param
 * group_id:    Specify the group to be deleted.
 * @Return
 * No Return
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$group_id = $_POST['group_id'];

$group = DBHelper::one(_tbl("user_group"), "id = $group_id");
if (empty($group)) {
  ApiHelper::fail(get_vocab("group_not_exist"), ApiHelper::GROUP_NOT_EXIST);
}
if ($group['source'] == 'ad') {
  ApiHelper::fail(get_vocab("group_cannot_modify"), ApiHelper::GROUP_CANNOT_DEL_OR_UPDATE);
}
$peek = DBHelper::one(_tbl("g2g_map"), " parent_id = $group_id");
if (!empty($peek)) {
  ApiHelper::fail(get_vocab("group_cannot_modify"), ApiHelper::GROUP_CANNOT_DEL_OR_UPDATE);
}

del_user_group($group_id);

$result = array();
ApiHelper::success($result);
