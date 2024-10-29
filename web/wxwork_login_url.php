<?php

require "defaultincludes.inc";

global $corpid, $secret, $call_back_domain, $agentid;

$corpid = "ww09d67060e82cbfa5";
$agentid = '1000032';
$call_back_domain = 'meeting-manage-test.businessconnectchina.com:12443';
$call_back = urlencode($call_back_domain);

echo "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$corpid}&redirect_uri=https%3A%2F%2F{$call_back}%2Fapp%2Findex.html%23%2Fcb&response_type=code&scope=snsapi_privateinfo&agentid={$agentid}&state=#wechat_redirect";

