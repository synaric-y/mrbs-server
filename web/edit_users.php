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

/*
 * 编辑用户接口
 * @Params
 * action：用于判断该操作是新增还是编辑还是删除
 * id：当action是编辑或者删除时，id为待编辑或待删除用户的id
 * email：用户的电子邮箱地址
 * name：用户的用户名
 * password0：第一次输入的密码
 * password1：第二次输入的密码
 * level：用户的权限级别
 * display_name：用户用于展示的名字
 */
/*---------------------------------------------------------------------------*\
|                         Authenticate the current user                         |
\*---------------------------------------------------------------------------*/
if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}
$username = $_SESSION['user'];

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}
session_write_close();



/*---------------------------------------------------------------------------*\
|             Edit a given entry - 1st phase: Get the user input.             |
\*---------------------------------------------------------------------------*/



$action = $_POST['action'];
$id = $_POST['id'];

$email = $_POST['email'];
$name = $_POST['name'];
$password0 = $_POST['password0'];
$password1 = $_POST['password1'];
$level = $_POST['level'];
$display_name = $_POST['display_name'];

if (!isset($action) || ($action != 'edit' && $action != 'add' && $action != 'delete')) {
  ApiHelper::fail(get_vocab("wrong_type"), ApiHelper::WRONG_TYPE);
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
    ApiHelper::fail(get_vocab("user_not_exist"), ApiHelper::USER_NOT_EXIST);
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
if (!empty($email) && !preg_match($pattern, $email)) {
  $invalid_email = "true";
}

if ($action == "edit" && !isset($id)){
  ApiHelper::fail(get_vocab("edit_without_id"), ApiHelper::EDIT_WITHOUT_ID);
}

// Error messages
if (!empty($invalid_email)) {
  ApiHelper::fail(get_vocab("invalid_email"), ApiHelper::INVALID_EMAIL);
}

$result = db()->query("SELECT * FROM " . _tbl("users") . " WHERE name = ?", array($name));
if ($result->count() > 0 && $action == "add")
  $name_not_unique = "true";
if (!empty($name_not_unique)) {
  ApiHelper::fail(get_vocab("name_not_unique"), ApiHelper::NAME_NOT_UNIQUE);
}
if (!isset($name) || empty($name)) {
  ApiHelper::fail(get_vocab("empty_name"), ApiHelper::EMPTY_NAME);
}


// Now do any password error messages

if ($password0 != $password1) {
  $pwd_not_match = "true";
}

if (!empty($pwd_not_match)) {
  ApiHelper::fail(get_vocab("passwords_not_eq"), ApiHelper::PASSWORDS_NOT_EQ);
}

///*---------------------------------------------------------------------------*\
//|             Edit a given entry - 2nd phase: Update the database.            |
//\*---------------------------------------------------------------------------*/
//
if (!empty($action) && ($action == "edit")){
  $user = $result -> next_row_keyed();
  $isExist = db() -> query("SELECT * FROM " . _tbl("users") . " WHERE id = ?", array($id));
  $row = $isExist -> next_row_keyed();
  $isExist = db() -> query1("SELECT COUNT(*) FROM " . _tbl("users") . " WHERE name = ?", array($name));
  if ($username == $row['name']){
    if ($isExist > 1){
      ApiHelper::fail(get_vocab("name_not_unique"), ApiHelper::NAME_NOT_UNIQUE);
    }
  }
  $user["name"] = $name;
  $user["display_name"] = $display_name;
  $user["email"] = $email;
  if (!empty($password0) && !empty($password1)) {
    $user["password"] = password_hash($password1, PASSWORD_DEFAULT);
  }
  db() -> query("UPDATE " . _tbl("users") . " SET name = ?, display_name = ?, email = ?, password_hash = ? WHERE id = ?", array($user['name']
    , $user["display_name"], $user["email"], $user["password"], $user["id"]));
  ApiHelper::success(null);
}else if (!empty($action) && ($action == "add")){
  $user = $result -> next_row_keyed();
  $user["name"] = $name;
  $user["display_name"] = $display_name;
  $user["email"] = $email;
  $user["password_hash"] = password_hash($password0, PASSWORD_DEFAULT);
  $user["level"] = $level;
  db() -> query("INSERT INTO " . _tbl("users") . "(level, name, display_name, email, password_hash) VALUES (?, ?, ?, ?, ?)", array($user["level"], $user["name"], $user["display_name"], $user["email"], $user["password_hash"]));
  ApiHelper::success(null);
}


//
///*---------------------------------------------------------------------------*\
//|                                Delete a user                                |
//\*---------------------------------------------------------------------------*/
//
if (!empty($action) && ($action == "delete")){

  if($username == $name){
    ApiHelper::fail(get_vocab("delete_yourself"), ApiHelper::DELETE_YOURSELF);
  }

  $result = db() -> query("DELETE FROM " . _tbl("users") . " WHERE name = ?", array($name));
  if (!$result) {
    ApiHelper::fail("", ApiHelper::UNKOWN_ERROR);
//    $response["code"] = -7;
//    $response["message"] = get_vocab("db_failed");
//    echo json_encode($response);
//    return;
  }
  ApiHelper::success(null);
}

