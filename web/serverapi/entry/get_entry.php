<?php


declare(strict_types=1);

namespace MRBS;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$result = db() -> query("SELECT * FROM " . _tbl("entry"));

$data = array();

while($row = $result -> next_row_keyed()){
  if (time() < $row['start_time'])
    $row['is_begin'] = 0;
  else if (time() > $row['end_time'])
    $row['is_begin'] = 2;
  else
    $row["is_begin"] = 1;
  $data[] = $row;
}

ApiHelper::success($data ?? null);
