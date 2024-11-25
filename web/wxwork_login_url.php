<?php

namespace MRBS;

require_once dirname(__DIR__) . '/vendor/autoload.php';
include_once "defaultincludes.inc";
include_once "functions_table.inc";
include_once "mrbs_sql.inc";

global $corpid, $secret, $call_back_domain, $agentid;

$config = DBHelper::one(_tbl("system_variable"), "1=1");

$corpid = $config['corpid'];
$agentid = $config['agentid'];
$room_id = $_POST['room_id'];
$area_id = $_POST['area_id'];
$call_back_domain = $config['server_address'];
$call_back = urlencode($call_back_domain);

echo "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$corpid}&redirect_uri={$call_back}%2Fapp%2Findex.html%23%2Fpages%2Fcb%2Fcb?room_id={$room_id}%26area_id={$area_id}&response_type=code&scope=snsapi_privateinfo&agentid={$agentid}&state=#wechat_redirect";

