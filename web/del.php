<?php
declare(strict_types=1);

namespace MRBS;

use MRBS\CalendarServer\CalendarServerManager;

require_once "defaultincludes.inc";
require_once './appapi/api_helper.php';

/*
 * 用于判断删除区域或者房间的接口，如果删除放进，则会将该房间下的所有会议删除，但是如果删除区域，则必须保证该
 * 区域下没有其他房间，否则会删除失败。
 * @Params
 * type：待删除的是区域还是房间
 * room：待删除的房间id
 * area：待删除的区域id
 */

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), \MRBS\ApiHelper::PLEASE_LOGIN);
}


if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

// Get non-standard form variables
//$type = get_form_var('type', 'string');
//$confirm = get_form_var('confirm', 'string', null, INPUT_POST);

$type = $_POST['type'] ?? null;
$room = $_POST['room'] ?? null;
$area = $_POST['area'] ?? null;

//$context = array(
//    'view'      => $view,
//    'view_all'  => $view_all,
//    'year'      => $year,
//    'month'     => $month,
//    'day'       => $day,
//    'area'      => $area,
//    'room'      => $room ?? null
//  );

// This is gonna blast away something. We want them to be really
// really sure that this is what they want to do.
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

    // Now take out the room itself
    $sql = "DELETE FROM " . _tbl('room') . " WHERE id=?";
    db()->command($sql, array($room));
  } catch (DBException $e) {
    db()->rollback();
    throw $e;
  }

  db()->commit();

  // Go back to the admin page
  ApiHelper::success(null);

}

if ($type == "area") {
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

    db()->command($sql, array($area));

    // Redirect back to the admin page
    ApiHelper::success(null);
  } else {
    // There are rooms left in the area
    ApiHelper::fail(get_vocab("room_in_area"), ApiHelper::ROOM_IN_AREA);
  }
}

ApiHelper::fail(get_vocab("invalid_types"), ApiHelper::INVALID_TYPES);

