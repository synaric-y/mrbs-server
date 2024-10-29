<?php
declare(strict_types=1);

namespace MRBS;

require_once "../defaultincludes.inc";
require_once "../mrbs_sql.inc";

/*
 * logout, request should take the cookie
 */

if (!isset($_SESSION['user']) || empty($_SESSION) ) {
  setcookie("session_id", "", time() - 3600);
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}
session_write_close();
$result = db() -> query("DELETE FROM " . _tbl("sessions") . " WHERE id = ?", [$_COOKIE['session_id']]);
if (!$result){
  ApiHelper::fail("", ApiHelper::UNKNOWN_ERROR);
//  $response = array(
//    "code" => -2,
//    "message" => "DB error"
//  );
//  echo json_encode($response);
//  return;
}

setcookie("session_id", "", time() - 3600, "/web/appapi/");
ApiHelper::success(null);
