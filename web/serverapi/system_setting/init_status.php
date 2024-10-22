<?php


declare(strict_types=1);

namespace MRBS;

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$result = db()->query("SELECT init_status FROM " . _tbl("system_variable"));
$row = $result -> next_row_keyed();

ApiHelper::success(["status" => $row['init_status']]);
