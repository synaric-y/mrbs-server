<?php

use MRBS\ApiHelper;
use MRBS\DBHelper;
use function MRBS\checkAuth;
use function MRBS\get_vocab;
use function MRBS\getLevel;
use function \MRBS\_tbl;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$name = $_POST['name'];
$parentId = $_POST['parent_id'];

if (empty($name)) {
  ApiHelper::fail(get_vocab("invalid_group_name"), ApiHelper::INVALID_GROUP_NAME);
}
if (empty($parentId)) {
  ApiHelper::fail(get_vocab("invalid_parent"), ApiHelper::INVALID_GROUP_PARENT);
}

$parentGroup = DBHelper::one(_tbl("user_group"), "id = $parentId");
if (empty($parentGroup) || $parentGroup['source'] != 'system' || $parentGroup['sync_state'] != 0) {
  ApiHelper::fail(get_vocab("invalid_parent"), ApiHelper::INVALID_GROUP_PARENT);
}

$insertGroup = array();
$insertGroup['name'] = $name;
$insertGroup['source'] = 'system';
$insertGroup['disabled'] = 0;
$insertGroup['user_count'] = 0;
DBHelper::insert(_tbl("user_group"), $insertGroup);
$id = DBHelper::insert_id(_tbl("user_group"), "id");
if (empty($id)) {
  ApiHelper::fail(get_vocab("internal_database_error"), ApiHelper::INTERNAL_ERROR);
}
$insertGroup["id"] = $id;

$insertG2G = array();
$insertG2G['group_id'] = $id;
$insertG2G['parent_id'] = $parentGroup['id'];
$insertG2G['deep'] = 1;
$insertG2G['source'] = "system";
DBHelper::insert(_tbl("g2g_map"), $insertG2G);
$g2gId = DBHelper::insert_id(_tbl("g2g_map"), "id");
if (empty($g2gId)) {
  ApiHelper::fail(get_vocab("internal_database_error"), ApiHelper::INTERNAL_ERROR);
}

$result = array();
$result['user_group'] = $insertGroup;
ApiHelper::success($result);


