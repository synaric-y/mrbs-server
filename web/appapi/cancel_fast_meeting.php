<?php


declare(strict_types=1);

namespace MRBS;
require_once "../mrbs_sql.inc";
require "../defaultincludes.inc";
require_once "api_helper.php";

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$meeting_id = $data["meeting_id"];

$meeting = DBHelper::one(_tbl("entry"), "id = $meeting_id");

if (empty($meeting)) {
  ApiHelper::fail("meeting not found", -1);
  return;
}

if ($meeting["entry_type"] != 99){
  ApiHelper::fail("only fast meeting can be canceled", -2);
}

if ($meeting["end_time"] < time()){
  ApiHelper::fail("meeting has expired", -3);
}

$result = db() -> query("DELETE FROM " . _tbl("entry") . " WHERE id = ?", array($meeting_id));
if ($result) {
  ApiHelper::success(null);
}else{
  ApiHelper::fail("Mysql error", -4);
}
