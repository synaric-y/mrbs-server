<?php

declare(strict_types=1);


require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . "/lib/Wxwork/api/src/CorpAPI.class.php";
require_once dirname(__DIR__, 2) . "/lib/Wxwork/api/src/Utils.php";

use MRBS\ApiHelper;

$url = HttpUtils::MakeUrl(
  "/cgi-bin/gettoken?corpid=&corpsecret=");

try{
  $jspRawStr = HttpUtils::httpGet($url);
  $data = json_decode($jspRawStr, true);
  if (!empty($data['errcode'])) {
    ApiHelper::success(null);
  }
}catch(Exception $e){
  ApiHelper::fail("network error", ApiHelper::UNKOWN_ERROR);
}

ApiHelper::fail("network error", ApiHelper::UNKOWN_ERROR);
