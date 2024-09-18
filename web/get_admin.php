<?php
declare(strict_types=1);

namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

global $min_booking_admin_level;

$result = db() -> query("SELECT name FROM " . _tbl("users") . " WHERE level >= ?", array($min_booking_admin_level));
if ($result -> count() < 1){
  ApiHelper::fail(get_vocab("no_admin"), ApiHelper::NO_ADMIN);
}

foreach ($result -> all_rows_keyed() as $row){
  $data1[] = $row['name'];
}
echo json_encode($data1);
