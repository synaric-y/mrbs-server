<?php
declare(strict_types=1);
namespace MRBS;

/*
 * get all room or area
 * @Param
 * type：'room' means searching room information, 'area' means searching area information
 * area：when searching all rooms, the id of area should be given
 * @Return
 * all room or area information with tree structure
 */

function buildTree($rows, $parent_id = -1): array
{
  $branch = [];
  foreach ($rows as $row) {
    if ($row['parent_id'] == $parent_id) {
      $children = buildTree($rows, $row['id']);
      if ($children) {
        $row['children'] = $children;
      }
      $branch[] = $row;
    }
  }
  return $branch;
}


if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$type = $_POST['type'];
$area = $_POST['area'] ?? null;

 if ($type == 'area'){
  $sql = "SELECT * FROM " . _tbl("area");
  $result = db() -> query($sql);
  $data = array();
  while($row = $result -> next_row_keyed()){
    unset($row['exchange_server']);
    unset($row['wxwork_corpid']);
    unset($row['wxwork_secret']);
    $data[] = $row;
  }
  $data = buildTree($data);
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
