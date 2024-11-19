<?php

declare(strict_types=1);

namespace MRBS;

global $min_booking_admin_level;

$entry_id = $_POST['id'];

$entry = get_entry_by_id($entry_id);
if (empty($entry)) {
  ApiHelper::fail(get_vocab("edit_entry_not_exist"), ApiHelper::ENTRY_NOT_EXIST);
}
if ($entry['entry_type'] != 99) {
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

ApiHelper::success(null);


