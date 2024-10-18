<?php

namespace MRBS;

/*
 * Query the first-level system-created groups under the specified User Group.
 * @Param
 * group_id:    Specify the group to be queried. If you need to return all top-level groups, pass -1
 * page:        Page number, start from 1.
 * search:      Search by name.
 * @Return
 * group:       A User Group info, with a child_groups property which contains
 *              all the first-level groups under this group.
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$group_id = $_POST['group_id'];
$page = intval($_POST['page']) ?? 1;
$search = $_POST['search'];
$group = get_user_group_tree($group_id, $search, "system", $page);
if (empty($group)) {
  ApiHelper::fail(get_vocab("group_not_exist"), ApiHelper::GROUP_NOT_EXIST);
}

$result = array();
$result['group'] = $group;
ApiHelper::success($result);
