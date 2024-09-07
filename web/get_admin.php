<?php
declare(strict_types=1);

namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

if (!checkAuth()){
  $response["code"] = -99;
  $response["message"] = get_vocab("please_login");
  echo json_encode($response);
  return;
}

if (getLevel($_SESSION['user']) < 2){
  $response["code"] = -98;
  $response["message"] = get_vocab("accessdenied");
  echo json_encode($response);
  return;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

global $min_booking_admin_level;

$result = db() -> query("SELECT name FROM " . _tbl("users") . " WHERE level >= ?", array($min_booking_admin_level));
if ($result -> count() < 1){
  $response = array(
    "code" => -1,
    "message" => get_vocab("no_admin")
  );
  echo json_encode($response);
  return;
}

$response = array(
  "code" => 0,
  "message" => get_vocab("success")
);
foreach ($result -> all_rows_keyed() as $row){
  $response["data"][] = $row['name'];
}
echo json_encode($response);
