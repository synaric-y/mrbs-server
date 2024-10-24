<?php


declare(strict_types=1);

namespace MRBS;

/*
 * get all versions in the database and server
 */

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$result = db()->query("SELECT * FROM " . _tbl("version") . " WHERE is_delete = 0");

ApiHelper::success($result->all_rows_keyed());
