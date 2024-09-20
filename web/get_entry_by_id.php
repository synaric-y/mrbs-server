<?php

declare(strict_types=1);

namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

/*
 * 根据会议id查询会议
 * @Params
 * id：待查询的会议id
 * @Return
 * data中包含该会议的所有信息
 */

$id = $_POST['id'];

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

if (empty($id)){
  ApiHelper::fail(get_vocab("search_without_id"), ApiHelper::SEARCH_WITHOUT_ID);
}

$result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE id = ?", array($id));
if ($result -> count() === 0){
  ApiHelper::fail(get_vocab("entry_not_exist"), ApiHelper::ENTRY_NOT_EXIST);
}



$row = $result -> next_row_keyed();
$row['start_time'] = intval($row['start_time']);
$row['end_time'] = intval($row['end_time']);
$result = db() -> query("SELECT room_name FROM " . _tbl("room") . " WHERE id = ?", array($row['room_id']));
$row['room_name'] = $result -> next_row_keyed()['room_name'];


ApiHelper::success($row);

