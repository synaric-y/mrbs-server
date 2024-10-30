<?php

declare(strict_types=1);

namespace MRBS;

/*
 * get entry by entry id
 * @Params
 * id：id of the entry
 * is_series：whether the entry is repeating entry
 * @Return
 * entry information
 */

global $max_level;

$id = $_POST['id'];
$is_series = $_POST['is_series'];

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

if ($is_series === 0)
  $result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE id = ?", array($id));
else if ($is_series === 1)
  $result = db() -> query("SELECT * FROM " . _tbl("repeat") . " WHERE id = ?", array($id));
else
  ApiHelper::fail(get_vocab("invalid_types"), ApiHelper::INVALID_TYPES);
if ($result -> count() === 0){
  ApiHelper::fail(get_vocab("entry_not_exist"), ApiHelper::ENTRY_NOT_EXIST);
}



$row = $result -> next_row_keyed();
if(getLevel($_SESSION['user']) < $max_level && $row['create_by'] != $_SESSION['user']){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::NO_RIGHT);
}

$row['start_time'] = intval($row['start_time']);
$row['end_time'] = intval($row['end_time']);
$result = db() -> query("SELECT * FROM " . _tbl("room") . " WHERE id = ?", array($row['room_id']));
$room = $result -> next_row_keyed();
$row['room_name'] = $room['room_name'];
$result = db() -> query("SELECT area_name, id as area_id FROM " . _tbl("area") . " WHERE id = ?", array($room['area_id']));
$area = $result -> next_row_keyed();
$row['area_name'] = $area['area_name'];
$row['end_date'] = date("Y-m-d", intval($row['end_date']));
$row['area_id'] = $area['area_id'];
ApiHelper::success($row);

