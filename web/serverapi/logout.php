<?php
declare(strict_types=1);

namespace MRBS;

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";
require_once './appapi/api_helper.php';

header('Content-Type: application/json');

/*
 * 登出接口，无传入传出值，需要有登录状态的cookie
 */

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
ApiHelper::success(null);
