<?php


declare(strict_types=1);

namespace MRBS;
require_once "../mrbs_sql.inc";
require "../defaultincludes.inc";
require_once "api_helper.php";

while(1){
  $result = RedisConnect::zRangeByScore('heart_beat', '-inf', time() - 30);
  if ($result === false){
    sleep(5);
    continue;
  }
  $sql = "UPDATE " . _tbl("device") . " SET status = 0 WHERE id in (";
  for ($i = 0; $i < sizeof($result); $i++) {
    if ($i != sizeof($result) - 1) {
      $sql .= $result[$i] . ", ";
    }else
      $sql .= $result[$i];
  }
  $sql .= ")";
  db()->query($sql);
}
