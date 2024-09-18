<?php

declare(strict_types=1);

namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$creator = $_SESSION['user'];

$result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE create_by = ?", array($creator));
ApiHelper::success($result -> all_rows_keyed());
