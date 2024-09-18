<?php
declare(strict_types=1);
namespace MRBS;


require "defaultincludes.inc";
require_once "mrbs_sql.inc";


$json = file_get_contents('php://input');
$data = json_decode($json, true);

$type = $data['type'];
$id = $data['id'];

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

if ($type == 'area'){
  $result = db() -> query("SELECT * FROM " . _tbl("area") . " WHERE id = ?", array($id));
}else if ($type == 'room'){
  $result = db() -> query("SELECT * FROM " . _tbl("room") . " WHERE id = ?", array($id));
}else if ($type == 'device'){

}else{
  ApiHelper::fail(get_vocab("wrong_type"), ApiHelper::WRONG_TYPE);
}
if ($result -> count() < 1){
  if ($type == 'room')
    ApiHelper::fail(get_vocab($type . "_not_exist"), ApiHelper::ROOM_NOT_EXIST);
  else if ($type == 'area')
    ApiHelper::fail(get_vocab($type . "_not_exist"), ApiHelper::AREA_NOT_EXIST);
}

ApiHelper::success($result -> all_rows_keyed());
