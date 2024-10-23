<?php

declare(strict_types=1);
namespace MRBS;


if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

global $now_version, $server_address;
$version = $_POST['version'];

$result = db() -> query("SELECT * FROM " . _tbl("version") . " WHERE version = ?", array($version));
if ($result -> count() < 1){
  ApiHelper::fail(get_vocab("version_not_exist"), ApiHelper::VERSION_NOT_EXIST);
}

$row = $result -> next_row_keyed();
if ($row['is_delete'] == 1){
  ApiHelper::fail(get_vocab("version_is_deleted"), ApiHelper::VERSION_IS_DELETED);
}

$now_version = $version;
db() -> command("UPDATE " . _tbl("system_variable") . " SET now_version = ?", array($now_version));


ApiHelper::success($server_address . "/display/" . $version . "/index.html");
