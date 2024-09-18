<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";


// Check the CSRF token.
// Only check the token if the page is accessed via a POST request.  Therefore
// this page should not take any action, but only display data.
//Form::checkToken(true);

// Check the user is authorised for this page
//checkAuthorised(this_page());

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$type = $data['type'];
$area = $data['area'];

if ($type == 'all'){

} else if ($type == 'area'){
  $sql = "SELECT * FROM " . _tbl("area");
  $result = db() -> query($sql);
  while($row = $result -> next_row_keyed()){
    unset($row['exchange_server']);
    unset($row['wxwork_corpid']);
    unset($row['wxwork_secret']);
    $data[] = $row;
  }
  ApiHelper::success($data);
}else if ($type == 'room'){
  if (!empty($area)){
    $areaExist = db() -> query("SELECT id FROM " . _tbl("area") . " WHERE id = ?", array($area));
    if ($areaExist -> count() == 0){
      ApiHelper::fail(get_vocab("area_not_exist"), ApiHelper::AREA_NOT_EXIST);
    }
    $sql = "SELECT *  FROM " . _tbl("room") . " WHERE area_id = ?";
    $result = db() -> query($sql, array($area));
    if ($result -> count() === 0){
      ApiHelper::fail(get_vocab("no_room_in_area"), ApiHelper::NO_ROOM_IN_AREA);
    }else{
      while($row = $result -> next_row_keyed()){
        unset($row['exchange_username']);
        unset($row['exchange_password']);
        unset($row['wxwork_mr_id']);
        unset($row['exchange_sync_state']);
        $data[] = $row;
      }
      ApiHelper::success($data);
    }
  }else{
    $result = db() -> query("SELECT * FROM " . _tbl("room"));
    if ($result -> count() === 0){
      ApiHelper::fail(get_vocab("room_not_exist"), ApiHelper::ROOM_NOT_EXIST);
    }
    $rows = $result -> all_rows_keyed();
    foreach($rows as $row){
      unset($row['exchange_username']);
      unset($row['exchange_password']);
      unset($row['wxwork_mr_id']);
      unset($row['exchange_sync_state']);
      $ans[] = $row;
    }
    ApiHelper::success($ans);
  }
}else{
  ApiHelper::fail(get_vocab("wrong_type"), ApiHelper::WRONG_TYPE);
}


