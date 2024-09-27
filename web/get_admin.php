<?php
declare(strict_types=1);

namespace MRBS;

require_once "defaultincludes.inc";
require_once './appapi/api_helper.php';
require_once "mrbs_sql.inc";

/*
 * 用于获取所有管理员名称的接口
 * @Param
 * 无
 * @Return
 * 所有管理员的name
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}


global $min_booking_admin_level;

$result = db() -> query("SELECT name FROM " . _tbl("users") . " WHERE level >= ?", array($min_booking_admin_level));
if ($result -> count() < 1){
  ApiHelper::fail(get_vocab("no_admin"), ApiHelper::NO_ADMIN);
}

foreach ($result -> all_rows_keyed() as $row){
  $data1[] = $row['name'];
}
echo json_encode($data1);
