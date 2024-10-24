<?php
declare(strict_types=1);

namespace MRBS;

use MRBS\CalendarServer\CalendarServerManager;


/*
 * delete a room or an area, when deleting a room, the entries in the room will be deleted, but when
 *    deleting an area, user should make sure there are no room in the area, otherwise the operation
 *    will no be allowed
 * @Params
 * type：'room' means deleting a room, 'area' means deleting an area
 * room：id of the room which will be deleted
 * area：id of the area which will be deleted
 */

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), \MRBS\ApiHelper::PLEASE_LOGIN);
}


if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

// Get non-standard form variables
//$type = get_form_var('type', 'string');
//$confirm = get_form_var('confirm', 'string', null, INPUT_POST);

$type = $_POST['type'] ?? null;
$room = $_POST['room'] ?? null;
$area = $_POST['area'] ?? null;


if ($type == "room") {

  db()->begin();
  try {
    // First take out all appointments for this room
    $result = db() -> query("SELECT id FROM " . _tbl("entry") . " WHERE room_id = ?", array($room)) -> all_rows_keyed();
    $sql = "DELETE FROM " . _tbl('entry') . " WHERE room_id=?";
    db()->command($sql, array($room));
    foreach ($result as $entry) {
      CalendarServerManager::deleteMeeting($entry['id']);
    }
    $sql = "DELETE FROM " . _tbl('repeat') . " WHERE room_id=?";
    db()->command($sql, array($room));
    db()->command("DELETE FROM " . _tbl("room_group") . " WHERE room_id=?", array($room));
    // Now take out the room itself
    $sql = "DELETE FROM " . _tbl('room') . " WHERE id=?";
    db()->command($sql, array($room));
  } catch (DBException $e) {
    db()->rollback();
    throw $e;
  }

  db()->commit();

  ApiHelper::success(null);

}

if ($type == "area") {
  $one = db() -> query1( "SELECT COUNT(*) FROM " . _tbl("area") . " WHERE parent_id = ?", array($area));
  if ($one > 0){
    ApiHelper::fail(get_vocab("delete_one_with_child"), ApiHelper::DELETE_ONE_WITH_CHILD);
  }
  // We are only going to let them delete an area if there are
  // no rooms. its easier
  $sql = "SELECT COUNT(*)
            FROM " . _tbl('room') . "
           WHERE area_id=?";

  $n = db()->query1($sql, array($area));
  if ($n === 0) {
    // OK, nothing there, let's blast it away
    $sql = "DELETE FROM " . _tbl('area') . "
             WHERE id=?";
    try{
      db()->begin();
      db()->command("DELETE FROM " . _tbl("area_group") . " WHERE area_id = ?", array($area));
      db()->command($sql, array($area));
      db()->commit();
    }catch(Exception $e){
      db()->rollback();
      throw $e;
    }
    ApiHelper::success(null);
  } else {
    // There are rooms left in the area
    ApiHelper::fail(get_vocab("room_in_area"), ApiHelper::ROOM_IN_AREA);
  }
}

ApiHelper::fail(get_vocab("invalid_types"), ApiHelper::INVALID_TYPES);

