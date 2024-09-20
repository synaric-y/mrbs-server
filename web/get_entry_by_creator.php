<?php

declare(strict_types=1);

namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

/*
 * 查询自己创建的会议
 * @Params
 * 无
 * @Return
 * 所有自己创建的会议
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

$creator = $_SESSION['user'];

$result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE create_by = ?", array($creator));
ApiHelper::success($result -> all_rows_keyed());
