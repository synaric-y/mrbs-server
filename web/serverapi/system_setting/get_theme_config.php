<?php


declare(strict_types=1);

namespace MRBS;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

if (!file_exists(dirname(__DIR__, 3) . "/config/theme.json")){
  ApiHelper::fail(get_vocab("file_not_exist"), ApiHelper::FILE_NOT_EXIST);
}
$file = file_get_contents(dirname(__DIR__, 3) . "/config/theme.json");

ApiHelper::success($file);
