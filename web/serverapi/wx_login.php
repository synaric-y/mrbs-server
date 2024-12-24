<?php

namespace MRBS;

global $default_password_hash;

function log_wx()
{
  $aglist = func_get_args();
  log_by_name("wxmini_", $aglist);
}

$code = $_POST["code"];
log_wx("wx code: " . $code);
if (isset($_SESSION) && !empty($_SESSION['user'])) {
  log_wx(\MRBS\get_vocab('already_login'));
  \MRBS\ApiHelper::success(\MRBS\get_vocab('already_login'));
}

if (!isset($_GET) || empty($code)) {
  log_wx(\MRBS\get_vocab("invalid_code"));
  \MRBS\ApiHelper::fail(\MRBS\get_vocab("invalid_code"), \MRBS\ApiHelper::INVALID_CODE);
}

$config = DBHelper::one(_tbl("system_variable"), "1=1");
$wx_appid = $config["wx_appid"];
$wx_secret = $config["wx_secret"];

$url = "https://api.weixin.qq.com/sns/jscode2session?appid=$wx_appid&secret=$wx_secret&js_code=$code&grant_type=authorization_code";
$result = get_url($url);
$errcode = $result["errcode"];
$openid = $result["openid"];
if (empty($openid)) {
  ApiHelper::fail("wxwork errcode: $errcode", ApiHelper::INTERNAL_ERROR);
}

$sql = "SELECT * FROM " . \MRBS\_tbl("users") . " WHERE wx_openid = '$openid'";
try {
  $result = \MRBS\db()->query($sql);

  if ($result->count() < 1) {
    log_wx("count < 1");

    \MRBS\db()->begin();
    $userid = "_" . uniqid();
    $display_name = "用户" . $userid;
    try {
      $transaction_ok = \MRBS\db()->query("INSERT INTO " . \MRBS\_tbl("users") . "(level, name, display_name, password_hash, wx_openid, email, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)", array(1, $userid, $display_name, $default_password_hash, $openid, "", date("Y-m-d H:i:s", time())));
      if ($transaction_ok) {
        \MRBS\db()->commit();
        $_SESSION['user'] = $userid;
      } else {
        \MRBS\db()->rollback();
      }
    } catch (\Exception $e) {
      \MRBS\db()->rollback();
      log_wx("insert user transaction failed");
      log_wx($e->getMessage() . $e->getTraceAsString());
      ApiHelper::fail(\MRBS\get_vocab("fail_to_create_user"), ApiHelper::FAIL_TO_CREATE_USER);
    }
  } else {
    $row = $result->next_row_keyed();
    log_wx("found user: id = {$row['id']} , name = {$row['name']}");
    $_SESSION['user'] = $row['name'];
    setcookie("session_id", session_id(), time() + 365 * 24 * 60 * 60, "/web/", "", false, true);
  }
} catch (\Exception $e) {
  log_wx($e->getMessage());
  log_wx($e->getTraceAsString());
}

session_write_close();
log_wx("success");
ApiHelper::success(null);
