<?php

namespace MRBS;

/*
 * Query the first-level users under the specified User Group.
 * @Param
 * group_id:    Specify the group to be queried. If you need to return all top-level groups, pass -1
 * page:        Page number, start from 1.
 * search:      Search by name.
 * source:      Search by source, values: system/ad, pass null or empty string to filter all.
 * @Return
 * users:       A user list.
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
$source = $_POST['source'];
$users = get_user_group_members($group_id, $search, $source, $page);

$result = array();
$result['users'] = $users;
ApiHelper::success($result);
