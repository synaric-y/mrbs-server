<?php

namespace MRBS;

/*
 * Bind User to User Group.
 * @Param
 * user_ids:    Specify the list of user IDs to be bound.
 * group_id:    Specify the parent group id.
 * @Return
 * No Return
 */


if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}
$user_ids = $_POST['user_ids'];
$group_id = $_POST['group_id'];

$groupInfo = DBHelper::one(_tbl("user_group"), "id = $group_id");
if (empty($groupInfo) || $groupInfo['source'] != 'system') {
  ApiHelper::fail(get_vocab("group_not_exist"), ApiHelper::GROUP_NOT_EXIST);
}

// Filter valid user_id, which not hold a relationship to $group_id
$uidIds = join(",", $user_ids);
$checkSQL = "
  select id from " . _tbl("users") . " where id in ($uidIds) and id not in (
    select user_id from " . _tbl("u2g_map") . " where parent_id = $group_id GROUP BY user_id
  )
";
$foundUsers = DBHelper::query_array($checkSQL);
if (empty($foundUsers) || count($foundUsers) < count($user_ids)) {
  ApiHelper::fail(get_vocab("user_not_exist"), ApiHelper::USER_NOT_EXIST);
}

foreach ($user_ids as $user_id) {
  if (empty($user_id) || empty($group_id) || $group_id < 0) {
    ApiHelper::fail(get_vocab("missing_parameters"), ApiHelper::MISSING_PARAMETERS);
  }

  $success = bind_user_to_group($user_id, $group_id, "system");
}
resolve_user_group_count();

$result = array();
ApiHelper::success($result);
