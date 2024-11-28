<?php


declare(strict_types=1);

namespace MRBS;
require_once "../mrbs_sql.inc";
require "../defaultincludes.inc";

/*
 * Cancel a fast meeting.
 * This API should only allow requests on the tablet side.
 * @Param
 * meeting_id:         Meeting ID of the entry to be canceled.
 *
 * @Return
 * None
 */

$meeting_id = $_POST["meeting_id"];

$meeting = DBHelper::one(_tbl("entry"), "id = $meeting_id");

if (empty($meeting)) {
  ApiHelper::fail("meeting not found", ApiHelper::ACCESS_DENIED);
  return;
}

if ($meeting["entry_type"] != ENTRY_FAST){
  ApiHelper::fail("only fast meeting can be canceled", ApiHelper::NO_ACCESS_TO_ENTRY);
}

if ($meeting["end_time"] < time()){
  ApiHelper::fail("meeting has expired", ApiHelper::NO_ACCESS_TO_ENTRY);
}

$result = db() -> query("DELETE FROM " . _tbl("entry") . " WHERE id = ?", array($meeting_id));
if ($result) {
  ApiHelper::success(null);
}else{
  ApiHelper::fail("Mysql error", ApiHelper::INTERNAL_ERROR);
}
