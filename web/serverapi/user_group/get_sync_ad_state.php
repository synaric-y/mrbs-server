<?php

namespace MRBS;

/*
 * Get sync AD state.
 * @Param
 * No params
 * @Return
 * task:       Task state.
 *              sync_version:   Current task id.
 *              progress:       A sub-task progress list.
 *              complete:       0: running; 1: complete; -1: failed
 */

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
}

$result = array();
$result['task'] = $task ?? null;
ApiHelper::success($result);
