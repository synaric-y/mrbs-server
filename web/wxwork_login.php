<?php
declare(strict_types=1);



require_once '../vendor/autoload.php';
require_once "./defaultincludes.inc";
require_once "./functions_table.inc";
require_once "./mrbs_sql.inc";
require_once "./lib/Wxwork/api/src/CorpAPI.class.php";
require_once "./lib/Wxwork/api/src/Utils.php";

global $corpid, $secret, $default_password_hash;

$corpid = "ww09d67060e82cbfa5";
$secret = "4kQjzoLSa1uBR5Ow5UWItaiI7CCSzjFYqzYTgKuR4IA";
if (isset($_SESSION) && !empty($_SESSION['user'])){
  \MRBS\ApiHelper::fail(\MRBS\get_vocab('already_login'), \MRBS\ApiHelper::ALREADY_LOGIN);
}

if (!isset($_GET) || empty($_GET["code"])) {
  \MRBS\ApiHelper::fail(get_vocab("invalid_code"), \MRBS\ApiHelper::INVALID_CODE);
}

$retry = 0;

while ($retry < 2){
  $access_token = get_access_token($corpid, $secret);
  $code = $_GET['code'];

  $url = HttpUtils::MakeUrl("/cgi-bin/auth/getuserinfo?access_token={$access_token}&code={$code}");
  $json = HttpUtils::httpGet($url);
  $data = json_decode($json, true);

  if ($data['errcode'] != 0) {
    $file = fopen("./lib/Wxwork/api/src/mutex_lock.lock", "w+");
    if (flock($file, LOCK_EX | LOCK_NB)){
      try{
        $result = refresh_access_token($corpid, $secret);
        if($result === false || $result === -1) throw new Exception("internal error");
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
    $file = fopen("./lib/Wxwork/api/src/mutex_lock.lock", "w+");
    if (flock($file, LOCK_EX)){
      try{
        $result = refresh_access_token($corpid, $secret);
        if($result === false || $result === -1) throw new Exception("internal error");
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

$result = \MRBS\db()-> query("SELECT * FROM " . \MRBS\_tbl("user") . " WHERE email = ? or email = ?", array($data['email'], $data['userid']));
if ($result -> count() < 1){
  \MRBS\db()->begin();
  try{
    $transaction_ok = \MRBS\db()-> query("INSERT INTO " . \MRBS\_tbl("user") . "(level, name, display_name, password_hash, email, timestamp) VALUES (?, ?, ?, ?, ?, ?)", array(1, explode("@", $data['userid'])[0], str_replace(".", " ", explode("@", $data['userid'])[0]), $default_password_hash, $data['email'] ?? $data['userid'], time()));
    if ($transaction_ok){
      \MRBS\db()->commit();
    }else{
      \MRBS\db()->rollback();
    }
  }catch (\Exception $e){
    \MRBS\db()->rollback();
    \MRBS\ApiHelper::fail(\MRBS\get_vocab("fail_to_create_user"), \MRBS\ApiHelper::FAIL_TO_CREATE_USER);
  }
}

$_SESSION['user'] = explode("@", $data['userid'])[0];
session_write_close();
\MRBS\ApiHelper::success(null);


