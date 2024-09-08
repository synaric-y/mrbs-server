<?php
declare(strict_types=1);

namespace MRBS;

use MRBS\Form\Form;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";


//// Check the CSRF token
//Form::checkToken();
//
//// Check the user is authorised for this page
//checkAuthorised(this_page());



// Get non-standard form variables
//$name = get_form_var('name', 'string', null, INPUT_POST);
//$description = get_form_var('description', 'string', null, INPUT_POST);
//$capacity = get_form_var('capacity', 'int', null, INPUT_POST);
//$room_admin_email = get_form_var('room_admin_email', 'string', null, INPUT_POST);
//$type = get_form_var('type', 'string', null, INPUT_POST);

// This file is for adding new areas/rooms
$error = '';
$json = file_get_contents('php://input');
$data = json_decode($json, true);
$name = $data['name'];

if (!checkAuth()){
  $response["code"] = -99;
  $response["message"] = get_vocab("please_login");
  setcookie("session_id", "", time() - 3600, "/web/");
  echo json_encode($response);
  return;
}

if (getLevel($_SESSION['user']) < 2){
  $response["code"] = -98;
  $response["message"] = get_vocab("accessdenied");
  echo json_encode($response);
  return;
}

$description = $data['description'];
$capacity = $data['capacity'];
$room_admin_email = $data['room_admin_email'];
$type = $data['type'];
$area = $data['area'];
if ($type === 'area') {
  $area = false;
  $room = true;
} else if ($type !== 'room') {
  $error = get_vocab("wrong_type");
} else {
  $room = false;
}

// First of all check that we've got an area or room name
if (!isset($name) || ($name === '')) {
  $error = get_vocab("empty_name");
}

// we need to do different things depending on if it's a room
// or an area
elseif ($type == "area") {
  $area = mrbsAddArea($name, $error);
} elseif ($type == "room") {
  $room = mrbsAddRoom($name, $area, $error, $description, $capacity, $room_admin_email);
}

if ($area && isset($room) && $room) {
  $response = array(
    "code" => 0,
    "message" => get_vocab("success")
  );
  echo json_encode($response);
  return;
} else {
  $response = array(
    "code" => -1,
    "message" => $error
  );
  echo json_encode($response);
  return;
}

