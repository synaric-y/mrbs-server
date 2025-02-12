<?php

declare(strict_types=1);

namespace MRBS;

/*
 * Force end entry.
 * @Param
 * id:    Specify the list of entry to be ended.
 * @Return
 * No Return
 */


global $min_booking_admin_level;

use MRBS\CalendarServer\CalendarServerManager;

$entry_id = $_POST['id'];

$entry = get_entry_by_id($entry_id);
if (empty($entry)) {
  ApiHelper::fail(get_vocab("edit_entry_not_exist"), ApiHelper::ENTRY_NOT_EXIST);
}
if ($entry['entry_type'] != ENTRY_FAST) {
  // Not a fast meeting, need login
  if (!checkAuth()){
    setcookie("session_id", "", time() - 3600, "/web/");
    ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
  }
}

$now = time();
if ($entry['start_time'] > $now) {
  ApiHelper::fail(get_vocab("invalid_start_time"), ApiHelper::INVALID_START_TIME);
}
if ($entry['end_time'] > $now) {
  db()->command("UPDATE " . _tbl("entry") . " SET end_time = ? WHERE id = ?", array($now, $entry_id));
} else {
  // Ignore and do not return an error
}

// Unsupportable for editing an exception in series
if (empty($entry['repeat_id'])) {
  try {
    CalendarServerManager::updateMeeting($entry['id']);
  } catch (\Exception $e) {
    log_i($e->getMessage());
    log_i($e->getTraceAsString());
  }
}

ApiHelper::success(null);


