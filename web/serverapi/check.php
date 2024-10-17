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
  if (empty($room['group_ids'])){
    return true;
  }

  $user = db() -> query("SELECT * FROM " . _tbl("users") . " WHERE name = ?", array($name)) ->next_row_keyed();

  $data = json_decode($room['group_ids'], true);
  foreach ($data as $group_id) {
    $exist = db() -> query1("SELECT COUNT(*) FROM " . _tbl("u2g_map") . " WHERE user_id = ? AND parent_id = ?", array($user['id'], $group_id));
    if ($exist > 1){
      return true;
    }
  }
  return false;
}
