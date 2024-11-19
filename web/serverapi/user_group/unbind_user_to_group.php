<?php

namespace MRBS;

/*
 * Unbind User to User Group.
 * @Param
 * user_ids:    Specify the list of user IDs to be unbound.
 * group_id:    Specify the parent group id.
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

$user_ids = $_POST['user_ids'];
$group_id = $_POST['group_id'];

$groupInfo = DBHelper::one(_tbl("user_group"), "id = $group_id");
if (empty($groupInfo) || $groupInfo['source'] != 'system') {
  ApiHelper::fail(get_vocab("group_not_exist"), ApiHelper::GROUP_NOT_EXIST);
}

$uidIds = join(",", $user_ids);
$foundUsers = DBHelper::query_array("select id from " ._tbl("users")." where id in( $uidIds )");
if (empty($foundUsers) || count($foundUsers) < count($user_ids)) {
  ApiHelper::fail(get_vocab("user_not_exist"), ApiHelper::USER_NOT_EXIST);
}
foreach ($user_ids as $user_id) {
  if (empty($user_id) || empty($group_id) || $group_id < 0) {
    ApiHelper::fail(get_vocab("missing_parameters"), ApiHelper::MISSING_PARAMETERS);
  }

  $userInfo = DBHelper::one(_tbl("users"), "id = $user_id");
  if (empty($userInfo)) {
    ApiHelper::fail(get_vocab("user_not_exist"), ApiHelper::USER_NOT_EXIST);
  }
//  $relation = DBHelper::one(_tbl("u2g_map"), "user_id = $user_id and parent_id = $group_id");
//  if (empty($relation) || $relation['source'] != 'system') {
//    ApiHelper::fail(get_vocab("group_cannot_unbind"), ApiHelper::GROUP_CANNOT_UNBIND);
//  }
  $success = unbind_user_to_group($user_id, $group_id);
}

resolve_user_group_count();

$result = array();
ApiHelper::success($result);
