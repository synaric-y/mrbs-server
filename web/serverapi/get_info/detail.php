<?php
declare(strict_types=1);
namespace MRBS;


/*
 * get all information of a room or an area
 * @Params
 * type：'room' means searching room information, 'area' means searching area information
 * id：the id of the room or area which should be searched
 * @Return
 * the information of the room or area
 */


$type = $_POST['type'];
$id = $_POST['id'];

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$count = 0;
$back = null;
if ($type == 'area'){
  $result = db() -> query("SELECT * FROM " . _tbl("area") . " WHERE id = ?", array($id));
  $count = $result->count();
  $back = $result->all_rows_keyed();
  $sql = "SELECT G.id, G.name, G.source FROM " . _tbl("a2g_map") . " A2G LEFT JOIN " . _tbl("user_group") . " G ON" .
    " A2G.group_id = G.id WHERE A2G.area_id = ? AND group_id != -1";
  $groups = db() -> query($sql, [$id])->all_rows_keyed();
  if (!empty($back[0]) && !empty($groups)) {
    $back[0]['groups'] = $groups;
  }
}else if ($type == 'room'){
  $result = db() -> query("SELECT * FROM " . _tbl("room") . " WHERE id = ?", array($id));
  $count = $result->count();
  $back = $result->all_rows_keyed();
}else if ($type == 'device'){

}else{
  ApiHelper::fail(get_vocab("wrong_type"), ApiHelper::WRONG_TYPE);
}
if ($count < 1){
  if ($type == 'room')
    ApiHelper::fail(get_vocab($type . "_not_exist"), ApiHelper::ROOM_NOT_EXIST);
  else if ($type == 'area')
    ApiHelper::fail(get_vocab($type . "_not_exist"), ApiHelper::AREA_NOT_EXIST);
}

ApiHelper::success($back);
