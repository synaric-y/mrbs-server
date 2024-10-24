<?php

declare(strict_types=1);

namespace MRBS;

/*
 * get entry by entry id
 * @Params
 * id：id of the entry
 * @Return
 * entry information
 */
if (isset($_POST['id']))
  $id = $_POST['id'];

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

//if (getLevel($_SESSION['user']) < 2){
//  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
//}

if (empty($id)){
  ApiHelper::fail(get_vocab("search_without_id"), ApiHelper::SEARCH_WITHOUT_ID);
}

$result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE id = ?", array($id));
if ($result -> count() === 0){
  ApiHelper::fail(get_vocab("entry_not_exist"), ApiHelper::ENTRY_NOT_EXIST);
}



$row = $result -> next_row_keyed();
if(getLevel($_SESSION['user']) == 1 && $row['create_by'] != $_SESSION['user']){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::NO_RIGHT);
}

$row['start_time'] = intval($row['start_time']);
$row['end_time'] = intval($row['end_time']);
$result = db() -> query("SELECT room_name FROM " . _tbl("room") . " WHERE id = ?", array($row['room_id']));
$row['room_name'] = $result -> next_row_keyed()['room_name'];


ApiHelper::success($row);

