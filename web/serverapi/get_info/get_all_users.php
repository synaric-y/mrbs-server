<?php
declare(strict_types=1);

namespace MRBS;

/*
 * get all user information
 * @Params
 * name: username(be used to log in)
 * display_name: name to display
 * disabled: whether the user is disabled
 * level: permission level
 * pagesize: display quantity per page
 * pagenum: page number
 * @Return
 * user information and total number of users
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
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
$sql .= " LIMIT $start_num, $pagesize";
$result = db() -> query($sql, $params);
$sql = str_replace("id, level, name, display_name, email, create_time, disabled", "COUNT(*)", $sql);
$sql = str_replace(" LIMIT $start_num, $pagesize", "", $sql);
$total_num = db() -> query1($sql, $params);
if ($result -> count() == 0){
  ApiHelper::success(['total_num' => $total_num]);
}else{
  while($row = $result -> next_row_keyed()){
    if ($row['name'] == $username){
      $row['is_self'] = 1;
    }else{
      $row['is_self'] = 0;
    }
    $data1['users'][] = $row;
  }
  if ($total_num != 0){
    $data1['total_num'] = $total_num;
  }
  ApiHelper::success($data1 ?? null);
}
