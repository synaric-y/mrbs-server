<?php

declare(strict_types=1);

/*
 * though getting access token, check if the wxwork server can be connected
 */

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . "/lib/Wxwork/api/src/CorpAPI.class.php";
require_once dirname(__DIR__, 2) . "/lib/Wxwork/api/src/Utils.php";

use MRBS\ApiHelper;

$corpid = $_POST['corpid'];
$secret = $_POST['secret'];

$url = HttpUtils::MakeUrl(
  "/cgi-bin/gettoken?corpid=$corpid&corpsecret=$secret");

try{
  $jspRawStr = HttpUtils::httpGet($url);
  $data = json_decode($jspRawStr, true);
  if ($data['errcode'] == 0) {
    ApiHelper::success(null);
  }
}catch(Exception $e){
  ApiHelper::fail("network error", ApiHelper::UNKNOWN_ERROR);
}

ApiHelper::fail("network error", ApiHelper::UNKNOWN_ERROR);

