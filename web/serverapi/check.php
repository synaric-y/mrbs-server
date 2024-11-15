<?php

namespace MRBS;

/*
 * check if the user is logged in
 *
 */
function checkAuth()
{
  if (empty($_SESSION))
    return false;
  else if (empty($_SESSION['user']))
    return false;
  return true;
}

/*
 * get the permission level of a user by username
 */
function getLevel($name)
{
  $result = db()->query("SELECT * FROM " . _tbl("users") . " WHERE name = ?", array($name));
  if ($result->count() == 0) {
    return false;
  }
  $row = $result->next_row_keyed();
  return intval($row['level']);
}

/*
 * check if the user can book an entry
 */
function user_can_book($name, $room)
{
//  $result = db()->query("SELECT * FROM " . _tbl("r2g_map") . " WHERE room_id = ?", array($room['id']));
  $user = db()->query("SELECT * FROM " . _tbl("users") . " WHERE name = ?", array($name))->next_row_keyed();
  if ($user['level'] == 2) {
    return true;
  }
//  if ($result->count() == 0) {
  $check_is_free = db()->query("SELECT * FROM " . _tbl("a2G_map") . " WHERE area_id = ? AND group_id = -1", array($room['area_id']))->next_row_keyed();
  if (!empty($check_is_free)) {
      return true;
  }
  $sql = "SELECT * FROM " . _tbl("area") . " A INNER JOIN " . _tbl("a2g_map") .
    " AG ON A.id = AG.area_id INNER JOIN " . _tbl("u2g_map") .
    " U2G ON U2G.parent_id = AG.group_id WHERE A.id = ? AND U2G.parent_id != -1 GROUP BY U2G.user_id";
  $result = db()->query($sql, array($room['area_id']));
  if ($result->count() == 0) {
    return false;
  }
  while ($row = $result->next_row_keyed()) {
    if ($row['user_id'] == $user['id']) {
      return true;
    }
  }
//  }


//  while ($row = $result->next_row_keyed()) {
//    if ($row['group_id'] == -1) {
//      return true;
//    }
//    $result = db()->query1("SELECT COUNT(*) FROM " . _tbl("u2g_map") . " WHERE user_id = ? AND parent_id = ?", array($user['id'], $row['group_id']));
//    if ($result > 0) {
//      return true;
//    }
//  }
  return false;
}
