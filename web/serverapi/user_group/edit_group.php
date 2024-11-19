<?php

namespace MRBS;

/*
 * Edit a User Group.
 * @Param
 * group_id:    Specify the group to be edited.
 * name:        New User Group name.
 * third_id:    Rebind the third-party ID, the group members will be cleared,
 *              and the members under the third-party ID will be synchronized to this group again.
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

if (check_sync_ad_running()) {
  ApiHelper::fail(get_vocab("sync_user_group_running"), ApiHelper::SYNC_USER_GROUP_RUNNING);
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
if (!empty($third_id)) {
  $g2g = DBHelper::one(_tbl("g2g_map"), "group_id = $group_id");
  if (!empty($g2g) && $g2g['parent_id'] != -1) {
    ApiHelper::fail(get_vocab("group_cannot_bind_third"), ApiHelper::GROUP_CANNOT_SYNC_THIRD);
  }
}

edit_user_group($_POST);

$result = array();

ApiHelper::success($result);
