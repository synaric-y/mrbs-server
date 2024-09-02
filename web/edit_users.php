<?php

namespace MRBS;

global $max_level;

use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementP;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldSelect;
use MRBS\Form\Form;

/*****************************************************************************\
 *                                                                            *
 *   File name     edit_users.php                                             *
 *                                                                            *
 *   Description   Edit the user database                                     *
 *                                                                            *
 *   Notes         Designed to be easily extensible:                          *
 *                 Adding more fields for each user does not require          *
 *                 modifying the editor code. Only to add the fields in       *
 *                 the database creation code.                                *
 *                                                                            *
 *                 An admin rights model is used where the level (an          *
 *                 integer between 0 and $max_level) denotes rights:          *
 *                      0:  no rights                                         *
 *                      1:  an ordinary user                                  *
 *                      2+: admins, with increasing rights.   Designed to     *
 *                          allow more granularity of admin rights, for       *
 *                          example by having booking admins, user admins     *
 *                          snd system admins.  (System admins might be       *
 *                          necessary in the future if, for example, some     *
 *                          parameters currently in the config file are      *
 *                          made editable from MRBS)                          *
 *                                                                            *
 *                 Only admins with at least user editing rights (level >=    *
 *                 $min_user_editing_level) can edit other users, and they    *
 *                 cannot edit users with a higher level than themselves      *
 *                                                                            *
 *                                                                            *
 * \*****************************************************************************/

require "defaultincludes.inc";
require_once "mrbs_sql.inc";


/*---------------------------------------------------------------------------*\
|                         Authenticate the current user                         |
\*---------------------------------------------------------------------------*/
session_start();
if (!checkAuth()){
  echo json_encode(array(
    "code" => -99,
    "message" => get_vocab("please_login")
  ));
  return;
}


/*---------------------------------------------------------------------------*\
|             Edit a given entry - 1st phase: Get the user input.             |
\*---------------------------------------------------------------------------*/



$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'];
$id = $data['id'];

$email = $data['email'];
$name = $data['name'];
$password0 = $data['password0'];
$password1 = $data['password1'];
$level = $data['level'];
$display_name = $data['display_name'];

$response = array(
  "code" => 'int',
  "message" => 'string'
);
$isAdmin = getLevel($_SESSION['user']);
if ($isAdmin != 2){
  $response['code'] = -13;
  $response['message'] = get_vocab("access_denied");
  echo json_encode($response);
  return;
}

if (!isset($action) || ($action != 'edit' && $action != 'add' && $action != 'delete')) {
  $response["code"] = -1;
  $response["message"] = "unexpected action";
  echo json_encode($response);
  return;
}

if (isset($id)) {
  // If it's an existing user then get the data from the database
  $sql = "SELECT *
              FROM " . _tbl('users') . "
             WHERE id=?";
  $result = db()->query($sql, array($id));
  $data = $result->next_row_keyed();
  unset($result);
  // Check that we've got a valid result.   We should do normally, but if somebody alters
  // the id parameter in the query string then we won't.   If the result is invalid, go somewhere
  // safe.
  if (!$data) {
    $response['code'] = -2;
    $response['message'] = 'no user found';
    echo json_encode($response);
    return;
  }
}

//  // Find out how many admins are left in the table - it's disastrous if the last one is deleted,
//  // or admin rights are removed!
if ($action == "edit") {
  $sql = "SELECT COUNT(*)
              FROM " . _tbl('users') . "
             WHERE level=?";
  $n_admins = db()->query1($sql, array($max_level));
  $editing_last_admin = ($n_admins <= 1) && ($data['level'] != $max_level);
} else {
  $editing_last_admin = false;
}
$pattern = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
if (isset($email) && !preg_match($pattern, $email)) {
  $invalid_email = "true";
}

if ($action == "edit" && !isset($id)){
  $response["code"] = -9;
  $response["message"] = "id is necessary for editing";
  echo json_encode($response);
  return;
}

if ($action == "edit" && isset($id)){
  $idExist = db() -> query("SELECT * FROM " . _tbl("users") . " WHERE id = ?", array($id));
  if ($idExist -> count()) {
    $response["code"] = -10;
    $response["message"] = "no user found";
  }
}

// Error messages
if (!empty($invalid_email)) {
  $response['code'] = -3;
  $response['message'] = "invalid email address";
  echo json_encode($response);
  return;
}

$result = db()->query("SELECT * FROM " . _tbl("users") . " WHERE name = ?", array($name));
if ($result->count() > 0 && $action == "add")
  $name_not_unique = "true";
if (!empty($name_not_unique)) {
  $response['code'] = -4;
  $response['message'] = "name not unique";
  echo json_encode($response);
  return;
//    echo "<p class=\"error\">'" . htmlspecialchars($taken_name) . "' " . get_vocab('name_not_unique') . "<p>\n";
}
if (!isset($name) || empty($name)) {
  $response['code'] = -5;
  $response['message'] = "name is required";
  echo json_encode($response);
  return;
}


// Now do any password error messages

if ($password0 != $password1) {
  $pwd_not_match = "true";
}

if (!empty($pwd_not_match)) {
  $response['code'] = -6;
  $response['message'] = get_vocab("passwords_not_eq");
  echo json_encode($response);
  return;
}

///*---------------------------------------------------------------------------*\
//|             Edit a given entry - 2nd phase: Update the database.            |
//\*---------------------------------------------------------------------------*/
//
if (isset($action) && ($action == "edit")){
  $user = $result -> next_row_keyed();
  $isExist = db() -> query("SELECT * FROM " . _tbl("users") . " WHERE id = ?", array($id));
  $row = $isExist -> next_row_keyed();
  $isExist = db() -> query1("SELECT COUNT(*) FROM " . _tbl("users") . " WHERE name = ?", array($name));
  if ($_SESSION['user'] == $row['name']){
    session_write_close();
    if ($isExist > 1){
      $response['code'] = -11;
      $response['message'] = "name is already in use";
      echo json_encode($response);
      return;
    }
  }
  session_write_close();
  $user["name"] = $name;
  $user["display_name"] = $display_name;
  $user["email"] = $email;
  if (isset($password0) && isset($password1)) {
    $user["password"] = password_hash($password1, PASSWORD_DEFAULT);
  }
  db() -> query("UPDATE " . _tbl("users") . " SET name = ?, display_name = ?, email = ?, password_hash = ? WHERE id = ?", array($user['name']
    , $user["display_name"], $user["email"], $user["password"], $user["id"]));
  $response["code"] = 0;
  $response["message"] = "success";
  echo json_encode($response);
  return;
}else if (isset($action) && ($action == "add")){
  session_write_close();
  $user = $result -> next_row_keyed();
  $user["name"] = $name;
  $user["display_name"] = $display_name;
  $user["email"] = $email;
  $user["password_hash"] = password_hash($password0, PASSWORD_DEFAULT);
  $user["level"] = $level;
  db() -> query("INSERT INTO " . _tbl("users") . "(level, name, display_name, email, password_hash) VALUES (?, ?, ?, ?, ?)", array($user["level"], $user["name"], $user["display_name"], $user["email"], $user["password_hash"]));
  $response["code"] = 0;
  $response["message"] = "success";
  echo json_encode($response);
  return;
}


//
///*---------------------------------------------------------------------------*\
//|                                Delete a user                                |
//\*---------------------------------------------------------------------------*/
//
if (isset($action) && ($action == "delete")){

  if($_SESSION['user'] == $name){
    session_write_close();
    $response["code"] = -8;
    $response["message"] = "you cannot delete your own account";
    echo json_encode($response);
    return;
  }
  session_write_close();

  $result = db() -> query("DELETE FROM " . _tbl("users") . " WHERE name = ?", array($name));
  if (!$result) {
    $response["code"] = -7;
    $response["message"] = "delete failed in DB";
    echo json_encode($response);
    return;
  }

  $response["code"] = 0;
  $response["message"] = "success";
  echo json_encode($response);
  return;

}

