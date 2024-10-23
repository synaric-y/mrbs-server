<?php
declare(strict_types=1);


require_once '../vendor/autoload.php';
require_once "./defaultincludes.inc";
require_once "./functions_table.inc";
require_once "./mrbs_sql.inc";
require_once "./lib/Wxwork/api/src/CorpAPI.class.php";
require_once "./lib/Wxwork/api/src/Utils.php";

use MRBS\ApiHelper;


/*
 * 企业微信登录接口，通过企业微信获取登陆人（GET），通常通过email字段进行比对，如果email字段没有比对成功
 * 则会新创建一个用户
 * @Param
 * code：通过企业微信回调后，由企业微信提供的code值，利用该code值可以获取到登陆人信息
 * @Return
 * 无，但是会设置js不可修改的cookie
 */

global $corpid, $secret, $default_password_hash;

$corpid = "ww09d67060e82cbfa5";
$secret = "4kQjzoLSa1uBR5Ow5UWItaiI7CCSzjFYqzYTgKuR4IA";
if (isset($_SESSION) && !empty($_SESSION['user'])){
  error_log("[" . date("Y-m-d H:i:s", time()) . "]" . \MRBS\get_vocab('already_login') . "\n", 3, dirname(__DIR__) . "/log/wxwork_log.log");
  MRBS\ApiHelper::success(\MRBS\get_vocab('already_login'));
}

if (!isset($_GET) || empty($_GET["code"])) {
  error_log("[" . date("Y-m-d H:i:s", time()) . "]" . \MRBS\get_vocab("invalid_code") . "\n", 3, dirname(__DIR__) . "/log/wxwork_log.log");
  MRBS\ApiHelper::fail(\MRBS\get_vocab("invalid_code"), MRBS\ApiHelper::INVALID_CODE);
}

$retry = 0;
while ($retry < 2){
  $access_token = get_access_token($corpid, $secret);
  $code = $_GET['code'];

  $url = HttpUtils::MakeUrl("/cgi-bin/auth/getuserinfo?access_token={$access_token}&code={$code}");
  $json = HttpUtils::httpGet($url);
  $data = json_decode($json, true);

  if ($data['errcode'] != 0) {
    if ($retry != 0){
      error_log("[" . date("Y-m-d H:i:s", time()) . "]" . "wxwork errcode: {$data['errcode']}\n", 3, dirname(__DIR__) . "/log/wxwork_log.log");
      ApiHelper::fail("wxwork errcode: {$data['errcode']}", ApiHelper::INTERNAL_ERROR);
    }
    $file = fopen("./lib/Wxwork/api/src/mutex_lock.lock", "w+");
    if (flock($file, LOCK_EX | LOCK_NB)){
      try{
        $result = refresh_access_token($corpid, $secret);
        if($result === false || $result === -1) {
          error_log("[" . date("Y-m-d H:i:s", time()) . "]" . "net error or redis error\n", 3, dirname(__DIR__) . "/log/wxwork_log.log");
          ApiHelper::fail("net error or redis error", ApiHelper::INTERNAL_ERROR);
        }else if ($result != 0){
          error_log("[" . date("Y-m-d H:i:s", time()) . "]" . "wxwork errcode: {$result}\n", 3, dirname(__DIR__) . "/log/wxwork_log.log");
          ApiHelper::fail("wxwork errcode: {$result}", ApiHelper::INTERNAL_ERROR);
        }
      }catch (\Exception $e){
        throw new Exception("internal error");
      } finally {
        flock($file, LOCK_UN);
        fclose($file);
      }
    }else{
      if (flock($file, LOCK_EX)){
        try{
          $access_token = get_access_token($corpid, $secret);
        }finally{
          flock($file, LOCK_UN);
          fclose($file);
        }
      }
    }
  }else{
    break;
  }
  $retry++;
}


$data = array(
  "user_ticket" => $data['user_ticket']
);

$retry = 0;

while($retry < 2){
  $url = HttpUtils::MakeUrl("/cgi-bin/auth/getuserdetail?access_token={$access_token}");
  $json = HttpUtils::httpPost($url, json_encode($data));
  $data = json_decode($json, true);
  if ($data['errcode'] != 0) {
    if ($retry != 0){
      error_log("[" . date("Y-m-d H:i:s", time()) . "]" . "wxwork errcode: {$data['errcode']}\n", 3, dirname(__DIR__) . "/log/wxwork_log.log");
      ApiHelper::fail("wxwork errcode: {$data['errcode']}", ApiHelper::UNKOWN_ERROR);
    }
    $file = fopen("./lib/Wxwork/api/src/mutex_lock.lock", "w+");
    if (flock($file, LOCK_EX)){
      try{
        $result = refresh_access_token($corpid, $secret);
        if($result === false || $result === -1) throw new Exception("internal error");
        if ($result === 40029)  ApiHelper::fail(\MRBS\get_vocab("invalid_code"), ApiHelper::INVALID_CODE);
        if ($result === 40001)  ApiHelper::fail(\MRBS\get_vocab("invalid_secret"), ApiHelper::INVALID_SECRET);
        if ($result === 40013)  ApiHelper::fail(\MRBS\get_vocab("invalid_corpid"), ApiHelper::INVALID_CORPID);
      }catch (\Exception $e){
        throw new Exception("internal error");
      } finally {
        flock($file, LOCK_UN);
        fclose($file);
      }
    }
  }else{
    break;
  }
  $retry++;
}

if ($retry == 2){
  ApiHelper::fail(\MRBS\get_vocab("unknown_error"), ApiHelper::UNKNOWN_ERROR);
}

$result = \MRBS\db()-> query("SELECT * FROM " . \MRBS\_tbl("users") . " WHERE email = ? or email = ?", array($data['email'], $data['userid']));
if ($result -> count() < 1){
  \MRBS\db()->begin();
  try{
    $transaction_ok = \MRBS\db()-> query("INSERT INTO " . \MRBS\_tbl("users") . "(level, name, display_name, password_hash, email, timestamp) VALUES (?, ?, ?, ?, ?, ?)", array(1, explode("@", $data['userid'])[0], str_replace(".", " ", explode("@", $data['userid'])[0]), $default_password_hash, $data['email'] ?? $data['userid'], date("Y-m-d H:i:s", time())));
    if ($transaction_ok){
      \MRBS\db()->commit();
      $_SESSION['user'] = explode("@", $data['userid'])[0];
    }else{
      \MRBS\db()->rollback();
    }
  }catch (\Exception $e){
    \MRBS\db()->rollback();
    error_log("[" . date("Y-m-d H:i:s", time()) . "] " . $e->getMessage() . $e->getTraceAsString() . "\n", 3, dirname(__DIR__) . "/log/wxwork_log.log");
    MRBS\ApiHelper::fail(\MRBS\get_vocab("fail_to_create_user"), MRBS\ApiHelper::FAIL_TO_CREATE_USER);
  }
}else{
  $row = $result->next_row_keyed();
  $_SESSION['user'] = $row['name'];
  setcookie("session_id", session_id(), time() + 24 * 60 * 60, "/web/", "", false, true);
}

session_write_close();
error_log("[" . date("Y-m-d H:i:s", time()) . "]" . "success\n", 3, "./log/wxwork_log.log");
MRBS\ApiHelper::success(null);


