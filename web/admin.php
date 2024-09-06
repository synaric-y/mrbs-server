<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";


// Check the CSRF token.
// Only check the token if the page is accessed via a POST request.  Therefore
// this page should not take any action, but only display data.
//Form::checkToken(true);

// Check the user is authorised for this page
//checkAuthorised(this_page());

if (!checkAuth()){
  $response["code"] = -99;
  $response["message"] = get_vocab("please_login");
  echo json_encode($response);
  return;
}

if (getLevel($_SESSION['user']) < 2){
  $response["code"] = -98;
  $response["message"] = get_vocab("accessdenied");
  echo json_encode($response);
  return;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$type = $data['type'];
$area = $data['area'];

if ($type == 'all'){

} else if ($type == 'area'){
  $sql = "SELECT * FROM " . _tbl("area");
  $result = db() -> query($sql);
  $response = array(
    "code" => 0,
    "message" => get_vocab("success"),
    "data" => array(

    )
  );
  while($row = $result -> next_row_keyed()){
    unset($row['exchange_server']);
    unset($row['wxwork_corpid']);
    unset($row['wxwork_secret']);
    $response["data"][] = $row;
  }
  echo json_encode($response);
}else if ($type == 'room'){
  if (!empty($area)){
    $areaExist = db() -> query("SELECT id FROM " . _tbl("area") . " WHERE id = ?", array($area));
    if ($areaExist -> count() == 0){
      $response = array(
        "code" => -3,
        "message" => get_vocab("area not exist")
      );
      echo json_encode($response);
    }
    $sql = "SELECT *  FROM " . _tbl("room") . " WHERE area_id = ?";
    $result = db() -> query($sql, array($area));
    if ($result -> count() === 0){
      $response = array(
        "code" => -4,
        "message" => get_vocab("no_room_in_area")
      );
      echo json_encode($response);
    }else{
      $response = array(
        "code" => 0,
        "message" => get_vocab("success"),
        "data" => array()
      );
      while($row = $result -> next_row_keyed()){
        unset($row['exchange_username']);
        unset($row['exchange_password']);
        unset($row['wxwork_mr_id']);
        unset($row['exchange_sync_state']);
        $response["data"][] = $row;
      }
      echo json_encode($response);
    }
  }else{
    $result = db() -> query("SELECT * FROM " . _tbl("room"));
    if ($result -> count() === 0){
      $response = array(
        "code" => -3,
        "message" => get_vocab("room_not_exist")
      );
      echo json_encode($response);
      return;
    }
    $rows = $result -> all_rows_keyed();
    foreach($rows as $row){
      unset($row['exchange_username']);
      unset($row['exchange_password']);
      unset($row['wxwork_mr_id']);
      unset($row['exchange_sync_state']);
      $ans[] = $row;
    }
    $response = array(
      "code" => 0,
      "message" => get_vocab("success"),
      "data" => $ans
    );
    echo json_encode($response);
    return;
  }
}else{
  $response = array(
    "code" => -1,
    "message" => get_vocab("wrong_type"),
  );
  echo json_encode($response);
}


