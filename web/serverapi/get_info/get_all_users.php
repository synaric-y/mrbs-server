<?php
declare(strict_types=1);

namespace MRBS;

/*
 * 获取所有用户信息
 * @Params
 * 无
 * @Return
 * data中包含查询到的所有信息
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}
$username = $_SESSION['user'];

session_write_close();

$sql = "SELECT id, level, name, display_name, email FROM " . _tbl("users");
$result = db() -> query($sql);

if ($result -> count() == 0){
  ApiHelper::fail(get_vocab("user_not_exist"), ApiHelper::USER_NOT_EXIST);
}else{
  while($row = $result -> next_row_keyed()){
    if ($row['name'] == $username){
      $row['is_self'] = 1;
    }else{
      $row['is_self'] = 0;
    }
    $data1[] = $row;
  }
  ApiHelper::success($data1);
}
