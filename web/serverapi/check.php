<?php
namespace MRBS;

/*
 * 由于前端发送请求后，后端会根据前端的cookie自动查询解析session，所以只需要判断session中是否有用户
 * 数据就可以判断用户是否登录了。
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
 * 用于获取用户的权限级别，TODO 当前系统中，管理员权限级别为2，普通用户权限级别为1。
 */
function getLevel($name)
{
  $result = db() -> query("SELECT * FROM " . _tbl("users") . " WHERE name = ?", array($name));
  if ($result -> count() == 0){
    return false;
  }
  $row = $result -> next_row_keyed();
  return intval($row['level']);
}

function user_can_book($name, $room)
{
  $result = db()->query("SELECT * FROM " . _tbl("room_group") . " WHERE room_id = ?", array($room['id']));
  $user = db() -> query("SELECT * FROM " . _tbl("users") . " WHERE name = ?", array($name)) ->next_row_keyed();
  if ($result -> count() == 0){
    $result = db() -> query("SELECT * FROM " . _tbl("area") . " A INNER JOIN " . _tbl("area_group") . " AG ON A.id = AG.area_id INNER JOIN " . _tbl("u2g_map") . " u2g ON u2g.group_id = AG.group_id WHERE A.id = ?", array($room['area_id']));
    while($row = $result -> next_row_keyed()){
      if ($row['user_id'] == $user['id']){
        return true;
      }
    }
  }


  while($row = $result ->next_row_keyed()){
    if ($row['group_id'] == -1){
      return true;
    }
    $result = db() -> query("SELECT COUNT(*) FROM " . _tbl("u2g_map") . " WHERE user_id = ? AND parent_id = ?", array($user['id'], $row['group_id']));
    if ($result > 0){
      return true;
    }
  }
  return false;
}
