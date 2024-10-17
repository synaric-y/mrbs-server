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
$page = intval($_POST['page']) ?? 1;
$search = $_POST['search'];
$users = get_user_group_members($group_id, $search, $page);

$result = array();
$result['users'] = $users;
ApiHelper::success($result);
