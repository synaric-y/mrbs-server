<?php

namespace MRBS;

function check_sync_ad_running()
{
  $task = RedisConnect::get(RedisKeys::$CURRENT_SYNC_AD_TASK);
  if (!empty($task)) {
    $task = json_decode($task, true);

    return $task['complete'] != 1;
  }

  return false;
}