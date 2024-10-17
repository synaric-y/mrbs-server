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

$vars = array(
  "name",
  "display_name",
  "disabled",
  "level"
);

$count = 0;
foreach ($vars as $var) {
  if (!empty($_POST[$var])){
    $count++;
    $$var = $_POST[$var];
  }
}

$pagesize = intval($_POST["pagesize"]);
$pagenum = intval($_POST["pagenum"]);

$params = array();
$sql = "SELECT id, level, name, display_name, email, create_time, disabled FROM " . _tbl("users");
if ($count > 0){
  $sql .= " WHERE ";
  for ($i = 0; $i < $count; $i++){
    $var = $vars[$i];
    $sql .= $vars[$i] . " = ?";
    $params[] = $$var;
    if ($i < $count - 1){
      $sql .= " AND ";
    }
  }
}
$start_num = ($pagenum - 1) * $pagesize;
$sql .= " LIMIT ?, ?";
$params[] = $start_num;
$params[] = $pagesize;
$result = db() -> query($sql, $params);
$sql = str_replace("id, level, name, display_name, email, create_time, disabled", "COUNT(*)", $sql);
$total_num = db() -> query1($sql, $params);
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
  if ($total_num != 0){
    $data1['total_num'] = $total_num;
  }
  ApiHelper::success($data1 ?? null);
}
