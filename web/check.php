<?php
namespace MRBS;

require_once "mrbs_sql.inc";

function checkAuth()
{
  if (!isset($_SESSION) || empty($_SESSION))
    return false;
  return true;
}

function getLevel($name)
{
  $result = db() -> query("SELECT * FROM " . _tbl("users") . " WHERE name = ?", array($name));
  if ($result -> count() == 0){
    return false;
  }
  $row = $result -> next_row_keyed();
  return $row['level'];
}
