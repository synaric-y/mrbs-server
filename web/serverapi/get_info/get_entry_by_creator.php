<?php

declare(strict_types=1);

namespace MRBS;


/*
 * get the entries created by self
 * @Params
 * none
 * @Return
 * all entries created by user self
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

$creator = $_SESSION['user'];
$pagesize = intval($_POST['pagesize']);
$pagenum = intval($_POST['pagenum']);

$offset = ($pagenum - 1) * $pagesize;
$result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE create_by = ?  LIMIT {$offset}, {$pagesize}", array($creator));
ApiHelper::success($result -> all_rows_keyed());
