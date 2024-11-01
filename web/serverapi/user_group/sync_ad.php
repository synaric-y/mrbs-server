<?php

namespace MRBS;
use LdapRecord\Container;
use LdapRecord\Connection;

/*
 * Synchronize User Groups and users structure from AD.
 * @Param
 * No params
 * @Return
 * status:    Return pending_start if idle. Return already_running if a task exists.
 * task_id:   Current running task id.
 */

use MRBS\LDAP\SyncADManager;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$task = RedisConnect::get(RedisKeys::$CURRENT_SYNC_AD_TASK);
if (!empty($task)) {
  $task = json_decode($task, true);

  if ($task['complete'] != 1) {
    $result = array(
      'status' => 'already_running',
      'task_id' => $task['sync_version']
    );
    ApiHelper::success($result);
  }
}

$config = DBHelper::one(_tbl("system_variable"), "1=1");
$server_address = $config['server_address'];
$AD_server = $config['AD_server'];
$AD_port = $config['AD_port'];
$AD_base_dn = $config['AD_base_dn'];
$AD_username = $config['AD_username'];
$AD_password = $config['AD_password'];

if (empty($AD_server) || empty($AD_port) || empty($AD_base_dn) || empty($AD_username) || empty($AD_password)) {
  ApiHelper::fail(get_vocab("missing_ad_params"), ApiHelper::MISSING_AD_PARAMS);
}
$connection = new Connection([
  'hosts' => [$AD_server],
  'port' => $AD_port,
  'base_dn' => $AD_base_dn,
  'username' => $AD_username,
  'password' => $AD_password,
]);
try {
  $connection->connect();
} catch (\LdapRecord\Auth\BindException $e) {
  $error = $e->getDetailedError();
  ApiHelper::fail($error->getDiagnosticMessage(), ApiHelper::LDAP_CONNECT_ERROR);
}


$sync_version = md5(uniqid('', true));
// In order to facilitate deployment, we did not choose Swoole or other asynchronous programming framework,
// but sent a POST request for processing. This interface will return immediately and inform the requesting
// end of the task_id.
$task = array(
  'sync_version' => $sync_version
);
RedisConnect::setex(RedisKeys::$CURRENT_SYNC_AD_TASK, json_encode($task), SyncADManager::$TASK_EXPIRE_SECONDS);
post_url_no_result("$server_address/web/call.php?act=user_group/sync_ad_inter",
  array("sync_version" => $sync_version));

$result = array(
  'status' => 'pending_start',
  'task_id' => $sync_version
);
ApiHelper::success($result);

