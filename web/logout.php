<?php
declare(strict_types=1);

namespace MRBS;

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($_SESSION['user']) || empty($_SESSION) ) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}
session_write_close();
$result = db() -> query("DELETE FROM " . _tbl("sessions") . " WHERE id = ?", [$_COOKIE['session_id']]);
if (!$result){
  ApiHelper::fail("", ApiHelper::UNKOWN_ERROR);
//  $response = array(
//    "code" => -2,
//    "message" => "DB error"
//  );
//  echo json_encode($response);
//  return;
}

setcookie("session_id", "", time() - 3600, "/web/");
$response = array(
  "code" => 0,
  "message" => get_vocab("success")
);
echo json_encode($response);
