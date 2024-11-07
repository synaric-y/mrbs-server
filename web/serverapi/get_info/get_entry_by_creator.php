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
$status = intval($_POST['status']);
$pagesize = intval($_POST['pagesize']);
$pagenum = intval($_POST['pagenum']);

$filter = "";
$params = [$creator];
if (isset($status)) {
  $filter .= " AND ";
  if ($status === 0) {
    $filter .= "E.start_time >= ?";
    $params[] = time();
  } else if ($status === 1) {
    $filter .= "E.start_time <= ? AND E.end_time >= ?";
    $params[] = time();
    $params[] = time();
  } else if ($status === 2) {
    $filter .= "E.end_time <= ?";
    $params[] = time();
  }
}

$offset = ($pagesize - 1) * $pagenum;
$result = db() -> query("SELECT * FROM " . _tbl("entry") . " E WHERE create_by = ? $filter ORDER BY timestamp DESC LIMIT {$offset}, {$pagesize}", $params);
ApiHelper::success($result -> all_rows_keyed());
