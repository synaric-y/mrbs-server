<?php

declare(strict_types=1);

namespace MRBS;

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$result = db() -> query("SELECT logo_dir, app_logo_dir FROM " . _tbl("system_variable") . " LIMIT 1");
$row = $result -> next_row_keyed();
ApiHelper::success(["web_logo" => $row['logo_dir'], "app_logo" => $row['app_logo_dir']]);
