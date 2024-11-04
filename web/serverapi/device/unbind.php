<?php


declare(strict_types=1);

namespace MRBS;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

// whether the user have the access to checking the device information
if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$device_id = $_POST['device_id'];

if(empty($device_id)){
  ApiHelper::fail(get_vocab("missing_parameters"), ApiHelper::MISSING_PARAMETERS);
}

db()->command("UPDATE " . _tbl("device") . " SET is_set=0, room_id=NULL WHERE device_id=?", array($device_id));

ApiHelper::success(null);
