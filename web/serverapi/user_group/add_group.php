<?php

namespace MRBS;

/*
 * Add a user group.
 * @Param
 * name:        Name of the User Group
 * parent_id:   Specify the parent group id, or -1 if you are creating the top-level group.
 * third_id:    Bind third-party ID. Only empty groups with parent_id == -1 && source == 'system' can be bound to third-party groups.
 * @Return
 * Added User Group info
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

$name = $_POST['name'];
$parent_id = $_POST['parent_id'];
$third_id = $_POST['third_id'];

if (empty($name)) {
  ApiHelper::fail(get_vocab("invalid_group_name"), ApiHelper::INVALID_GROUP_NAME);
}
if (empty($parent_id)) {
  ApiHelper::fail(get_vocab("invalid_parent"), ApiHelper::INVALID_GROUP_PARENT);
}

if ($parent_id != -1) {
  if (!empty($third_id)) {
    ApiHelper::fail(get_vocab("group_cannot_bind_third"), ApiHelper::GROUP_CANNOT_SYNC_THIRD);
  }

  $parentGroup = DBHelper::one(_tbl("user_group"), "id = $parent_id");
  if (empty($parentGroup) || $parentGroup['source'] != 'system' || $parentGroup['sync_state'] != 0) {
    ApiHelper::fail(get_vocab("invalid_parent"), ApiHelper::INVALID_GROUP_PARENT);
  }
}

$exist_group = db()->query("SELECT id FROM " . _tbl("user_group") . " WHERE name = ? LIMIT 1", array($name));
if ($exist_group->count() > 1) {
  ApiHelper::fail(get_vocab("duplicated_group_name"), ApiHelper::DUPLICATED_GROUP_NAME);
}

$insertGroup = array();
$insertGroup['name'] = $name;
$insertGroup['source'] = 'system';
$insertGroup['disabled'] = 0;
$insertGroup['sync_state'] = 0;
$insertGroup['user_count'] = 0;
$insertGroup['third_id'] = $third_id;
$insert_id = add_user_group($insertGroup, $parent_id);
$insertGroup['id'] = $insert_id;

$result = array(
  'group' => $insertGroup
);
ApiHelper::success($result);


