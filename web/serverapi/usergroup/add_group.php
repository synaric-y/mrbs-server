<?php

namespace MRBS;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$name = $_POST['name'];
$parent_id = $_POST['parent_id'];

if (empty($name)) {
  ApiHelper::fail(get_vocab("invalid_group_name"), ApiHelper::INVALID_GROUP_NAME);
}
if (empty($parent_id)) {
  ApiHelper::fail(get_vocab("invalid_parent"), ApiHelper::INVALID_GROUP_PARENT);
}

if ($parent_id != -1) {
  $parentGroup = DBHelper::one(_tbl("user_group"), "id = $parent_id");
  if (empty($parentGroup) || $parentGroup['source'] != 'system' || $parentGroup['sync_state'] != 0) {
    ApiHelper::fail(get_vocab("invalid_parent"), ApiHelper::INVALID_GROUP_PARENT);
  }
}

$insertGroup = array();
$insertGroup['name'] = $name;
$insertGroup['source'] = 'system';
$insertGroup['disabled'] = 0;
$insertGroup['sync_state'] = 0;
$insertGroup['user_count'] = 0;
add_user_group($insertGroup, $parent_id);

$result = array();
ApiHelper::success($result);


