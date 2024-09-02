<?php
namespace MRBS;

require_once "mrbs_sql.inc";
//require_once "defaultincludes.inc";

function checkAuth()
{
  if (!isset($_COOKIE)){
    return false;
  }
  $session_id = $_COOKIE["session_id"];
  $username = $_COOKIE["username"];
  $result = db() -> query("SELECT * FROM " . \MRBS\_tbl("sessions") . " WHERE id = ?", array($session_id));
  if ($result -> count() == 0){
    return false;
  }
  $row = $result -> next_row_keyed();
  session_decode($row['data']);
  if ($_SESSION['user'] != $username){
    return false;
  }
  return true;
}
