<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__) . "/defaultincludes.inc";
require_once dirname(__DIR__) . "/functions_table.inc";
require_once dirname(__DIR__) . "/mrbs_sql.inc";
include_once dirname(__DIR__) . "/lib/Wxwork/callback/WXBizMsgCrypt.php";


ini_set('display_errors', 1);            //错误信息
ini_set('display_startup_errors', 1);    //php启动错误信息
error_reporting(E_ALL);

$corpId = "ww09d67060e82cbfa5";
$encodingAesKey = "JxuDbakLiWkT6MIGQuQ24wJf3FVvM4s6NAK4Qqdk98K";
$token = "pTLfINAgRTG6wYdGYbK9Ubdzhq4N2Q";

global $thirdCalendarService;
$tag = "wxwork_callback_";
$wxcpt = new WXBizMsgCrypt($token, $encodingAesKey, $corpId);

$sVerifyMsgSig = $_GET["msg_signature"];
$sVerifyTimeStamp = $_GET["timestamp"];
$sVerifyNonce = $_GET["nonce"];

\MRBS\log_by_name($tag, ["REQUEST start------------------------"]);
\MRBS\log_by_name($tag, [$sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // post请求的密文数据
  $sReqData = file_get_contents('php://input');
//  \MRBS\log_by_name($tag, ["[POST BODY]", $sReqData]);
  $sMsg = "";  // 解析之后的明文
  $errCode = $wxcpt->DecryptMsg($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sReqData, $sMsg);
  if ($errCode == 0) {
    // 解密成功，sMsg即为xml格式的明文
    \MRBS\log_by_name($tag, ["[POST] ", $sMsg]);





    echo "";
  } else {
    \MRBS\log_by_name($tag, ["[POST] ", "error: " . $errCode]);
  }
} else if ($_SERVER["REQUEST_METHOD"] === "GET") {
  $sVerifyEchoStr = $_GET["echostr"];
  $sEchoStr = "";

  $errCode = $wxcpt->VerifyURL($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr, $sEchoStr);
  if ($errCode == 0) {
    \MRBS\log_by_name($tag, ["[GET] ", $sEchoStr]);
    echo $sEchoStr;





  } else {
    \MRBS\log_by_name($tag, ["[GET] ", "error: " . $errCode]);
  }
}
\MRBS\log_by_name($tag, ["REQUEST end--------------------------"]);

