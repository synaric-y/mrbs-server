<?php

namespace MRBS;

use MRBS\LDAP\SyncADManager;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$task = RedisConnect::get('CURRENT_SYNC_AD_TASK');
if (!empty($task)) {
  $task = json_decode($task, true);
  $result = array(
    'status' => 'already_running',
    'task_id' => $task['sync_version']
  );
  ApiHelper::success($result);
}

$config = DBHelper::one(_tbl("system_variable"), "1=1");
$server_address = $config['server_address'];
$SYNC_VERSION = md5(uniqid('', true));
//In order to facilitate deployment, we did not choose Swoole or other asynchronous programming framework,
// but sent a POST request for processing. This interface will return immediately and inform the requesting
// end of the task_id.
$task = array(
  'sync_version' => $SYNC_VERSION
);
RedisConnect::setex('CURRENT_SYNC_AD_TASK', json_encode($task), 3600);
post_url_no_result("$server_address/web/call.php?act=user_group/sync_ad_inter", array("sync_version" => $SYNC_VERSION));

$result = array(
  'status' => 'pending_start',
  'task_id' => $SYNC_VERSION
);
ApiHelper::success($result);
