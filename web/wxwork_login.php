<?php
declare(strict_types=1);

namespace MRBS;


use HttpUtils;

require_once '../vendor/autoload.php';
require_once "./defaultincludes.inc";
require_once "./functions_table.inc";
require_once "./mrbs_sql.inc";
require_once "./lib/Wxwork/api/src/CorpAPI.class.php";
require_once "./lib/Wxwork/api/src/Utils.php";


/*
 * 企业微信登录接口，通过企业微信获取登陆人（GET），通常通过email字段进行比对，如果email字段没有比对成功
 * 则会新创建一个用户
 * @Param
 * code：通过企业微信回调后，由企业微信提供的code值，利用该code值可以获取到登陆人信息
 * @Return
 * 无，但是会设置js不可修改的cookie
 */

global $corpid, $secret, $default_password_hash;

function log_wxwork()
{
  $aglist = func_get_args();
  log_by_name("wxwork_", $aglist);
}

$config = DBHelper::one(_tbl("system_variable"), "1=1");

$corpid = $config['corpid'];
$secret = $config['secret'];
log_wxwork("======================================================");
log_wxwork("wxwork corpid: $corpid, secret: $secret");
log_wxwork("wxwork code: " . $_GET["code"]);
if (isset($_SESSION) && !empty($_SESSION['user'])) {
  log_wxwork(\MRBS\get_vocab('already_login'));
  \MRBS\ApiHelper::success(\MRBS\get_vocab('already_login'));
}

if (!isset($_GET) || empty($_GET["code"])) {
  log_wxwork(\MRBS\get_vocab("invalid_code"));
  \MRBS\ApiHelper::fail(\MRBS\get_vocab("invalid_code"), \MRBS\ApiHelper::INVALID_CODE);
}

$retry = 0;
while ($retry < 2) {
  $code = $_GET['code'];
  $access_token = get_access_token($corpid, $secret);
  log_wxwork("wxwork access_token: $access_token");
  // Retry
  if (empty($access_token)) {
    $access_token = get_access_token($corpid, $secret);
  }
  $url = HttpUtils::MakeUrl("/cgi-bin/auth/getuserinfo?access_token={$access_token}&code={$code}");
  $json = HttpUtils::httpGet($url);
  /** @noinspection PhpStrictTypeCheckingInspection */
  $data = json_decode($json, true);
  log_wxwork("resp: $json");
  if ($data['errcode'] != 0) {
    log_wxwork("wxwork errcode: {$data['errcode']}");
    ApiHelper::fail("wxwork errcode: $json", ApiHelper::INTERNAL_ERROR);

    $file = fopen("./lib/Wxwork/api/src/mutex_lock.lock", "w+");
    if (flock($file, LOCK_EX | LOCK_NB)) {
      try {
        $result = refresh_access_token($corpid, $secret);
        if ($result === false || $result === -1) {
          log_wxwork("net error or redis error");
          ApiHelper::fail("net error or redis error", ApiHelper::INTERNAL_ERROR);
        } else if ($result != 0) {
          log_wxwork("refresh_access_token: {$result}");
          ApiHelper::fail("wxwork errcode: {$result}", ApiHelper::INTERNAL_ERROR);
        }
      } catch (\Exception $e) {
        log_wxwork("refresh access_token failed");
        log_wxwork($e->getMessage());
        log_wxwork($e->getTraceAsString());
        throw new Exception("internal error");
      } finally {
        flock($file, LOCK_UN);
        fclose($file);
      }
    } else {
      if (flock($file, LOCK_EX)) {
        try {
          $access_token = get_access_token($corpid, $secret);
        } finally {
          flock($file, LOCK_UN);
          fclose($file);
        }
      }
    }
  } else {
    break;
  }
  $retry++;
}


$data = array(
  "user_ticket" => $data['user_ticket']
);

$retry = 0;

while ($retry < 2) {
  $url = HttpUtils::MakeUrl("/cgi-bin/auth/getuserdetail?access_token={$access_token}");
  $json = HttpUtils::httpPost($url, json_encode($data));
  /** @noinspection PhpStrictTypeCheckingInspection */
  $data = json_decode($json, true);
  log_wxwork("resp: $json");
  if ($data['errcode'] != 0) {
    if ($retry != 0) {
      log_wxwork("wxwork errcode: {$data['errcode']}");
      ApiHelper::fail("wxwork errcode: {$data['errcode']}", ApiHelper::UNKNOWN_ERROR);
    }
    $file = fopen("./lib/Wxwork/api/src/mutex_lock.lock", "w+");
    if (flock($file, LOCK_EX)) {
      try {
        $result = refresh_access_token($corpid, $secret);
        if ($result === false || $result === -1) throw new Exception("internal error");
        if ($result === 40029) ApiHelper::fail(\MRBS\get_vocab("invalid_code"), ApiHelper::INVALID_CODE);
        if ($result === 40001) ApiHelper::fail(\MRBS\get_vocab("invalid_secret"), ApiHelper::INVALID_SECRET);
        if ($result === 40013) ApiHelper::fail(\MRBS\get_vocab("invalid_corpid"), ApiHelper::INVALID_CORPID);
      } catch (\Exception $e) {
        throw new Exception("internal error");
      } finally {
        flock($file, LOCK_UN);
        fclose($file);
      }
    }
  } else {
    break;
  }
  $retry++;
}

if ($retry == 2) {
  ApiHelper::fail(\MRBS\get_vocab("unknown_error"), ApiHelper::UNKNOWN_ERROR);
}

$sql = "SELECT * FROM " . \MRBS\_tbl("users") . " WHERE email = '" . $data['userid'] . "'";
if (!empty($data['email'])) {
  $sql .= (" or email = '{$data['email']}'");
}
if (!empty($data['userid'])) {
  $rName = explode("@", $data['userid'])[0] ?: '';
  if (!empty($rName)) {
    $sql .= (" or name = '{$rName}'");
  }
}
try {
  $result = \MRBS\db()->query($sql);
} catch (\Exception $e) {
  log_wxwork($e->getMessage());
  log_wxwork($e->getTraceAsString());
}
if ($result->count() < 1) {
  log_wxwork("count < 1");

  \MRBS\db()->begin();
  try {
    $transaction_ok = \MRBS\db()->query("INSERT INTO " . \MRBS\_tbl("users") . "(level, name, display_name, password_hash, email, timestamp) VALUES (?, ?, ?, ?, ?, ?)", array(1, explode("@", $data['userid'])[0], str_replace(".", " ", explode("@", $data['userid'])[0]), $default_password_hash, $data['email'] ?? $data['userid'], date("Y-m-d H:i:s", time())));
    if ($transaction_ok) {
      \MRBS\db()->commit();
      $_SESSION['user'] = explode("@", $data['userid'])[0];
    } else {
      \MRBS\db()->rollback();
    }
  } catch (\Exception $e) {
    \MRBS\db()->rollback();
    log_wxwork("insert user transaction failed");
    log_wxwork($e->getMessage() . $e->getTraceAsString());
    ApiHelper::fail(\MRBS\get_vocab("fail_to_create_user"), ApiHelper::FAIL_TO_CREATE_USER);
  }
} else {
  $row = $result->next_row_keyed();
  $_SESSION['user'] = $row['name'];
  setcookie("session_id", session_id(), time() + 365 * 24 * 60 * 60, "/web/", "", false, true);
}

session_write_close();
log_wxwork("success");
ApiHelper::success(null);


