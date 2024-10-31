<?php

namespace MRBS;

use MRBS\LDAP\SyncADManager;

// Only can be called internally

$sync_version = $_POST['sync_version'];

// Safe check
$task = RedisConnect::get(RedisKeys::$CURRENT_SYNC_AD_TASK);
if (empty($sync_version) || empty($task)) {
  $result = array();
  ApiHelper::fail($result, ApiHelper::INTERNAL_ERROR);
} else {
  $task = json_decode($task, true);
  if ($sync_version != $task['sync_version']) {
    $result = array();
    $result['sync_version'] = $sync_version;
    $result['task_sync_version'] = $task['sync_version'];
    ApiHelper::fail($result, ApiHelper::INTERNAL_ERROR);
  }
}

$manager = new SyncADManager();
$manager->sync($sync_version);
$result = array();
ApiHelper::success($result);

