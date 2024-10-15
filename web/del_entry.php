<?php
declare(strict_types=1);
namespace MRBS;

require_once dirname(__DIR__) . '/vendor/autoload.php';

use MRBS\CalendarServer\CalendarServerManager;

// Deletes an entry, or a series.    The $id is always the id of
// an individual entry.   If $series is set then the entire series
// of which $id is a member should be deleted. [Note - this use of
// $series is inconsistent with use in the rest of MRBS where it
// means that $id is the id of an entry in the repeat table.   This
// should be fixed sometime.]

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";
require_once "functions_mail.inc";
require_once './appapi/api_helper.php';

/*
 * 用于删除会议，支持删除普通会议以及周期会议
 * @Params
 * id：待删除会议的id
 * series：用于判断待删除会议是否是周期会议，如果不是，则该参数为空字符串，否则为任意非空字符串
 */

$id = $_POST["entry_id"];
$series = boolval($_POST["entry_series"] ?? false);

if (!$series){
  $result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE id = ?", array($id));
  if ($result -> count() < 1){
    ApiHelper::fail(get_vocab("entry_not_exist"), ApiHelper::ENTRY_NOT_EXIST);
  }
  $row = $result -> next_row_keyed();
  if($row['end_time'] <= time()){
    ApiHelper::fail(get_vocab("expired_end_time"), ApiHelper::EXPIRED_END_TIME);
  }
}

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

//if (getLevel($_SESSION['user']) < 2){
//  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
//}

$user = db() -> query("SELECT * FROM " . _tbl("users") . " WHERE name = ?", array($_SESSION['user']));
$user = $user -> next_row_keyed();
session_write_close();
// Check the CSRF token
//Form::checkToken();

// Check the user is authorised for this page
//checkAuthorised(this_page());

//if (empty($returl))
//{
//  $vars = array('view'  => $default_view,
//                'year'  => $year,
//                'month' => $month,
//                'day'   => $day,
//                'area'  => $area,
//                'room'  => $room);
//
//  $returl .= 'index.php?' . http_build_query($vars, '', '&');
//}
if ($info = get_booking_info($id, FALSE, TRUE))
{
  // check that the user is allowed to delete this entry
  if ($user['level'] == 2 || $user['name'] == $info['create_by']){
    $authorised = true;
  }else
    $authorised = false;
  if ($authorised)
  {
    $area  = mrbsGetRoomArea($info["room_id"]);
    // Get the settings for this area (they will be needed for policy checking)
    get_area_settings($area);

    $notify_by_email = $mail_settings['on_delete'] && need_to_send_mail();

    if ($notify_by_email)
    {
      // Gather all fields values for use in emails.
      $mail_previous = get_booking_info($id, FALSE);
      // If this is an individual entry of a series then force the entry_type
      // to be a changed entry, so that when we create the iCalendar object we know that
      // we only want to delete the individual entry
      if (!$series && ($mail_previous['repeat_rule']->getType() != RepeatRule::NONE))
      {
        $mail_previous['entry_type'] = ENTRY_RPT_CHANGED;
      }
    }

    CalendarServerManager::deleteMeeting($id);

    $start_times = mrbsDelEntry($id, $series, true);

    // [At the moment MRBS does not inform the user if it was not able to delete
    // an entry, or, for a series, some entries in a series.  This could happen for
    // example if a booking policy is in force that prevents the deletion of entries
    // in the past.   It would be better to inform the user that the operation has
    // been unsuccessful or only partially successful]
    if (($start_times !== FALSE) && (count($start_times) > 0))
    {
      // Send a mail to the Administrator
      if ($notify_by_email)
      {
        // Now that we've finished with mrbsDelEntry, change the id so that it's
        // the repeat_id if we're looking at a series.   (This is a complete hack,
        // but brings us back into line with the rest of MRBS until the anomaly
        // of del_entry is fixed)
        if ($series)
        {
          $mail_previous['id'] = $mail_previous['repeat_id'];
        }
        if (isset($action) && ($action == "reject"))
        {
          notifyAdminOnDelete($mail_previous, $start_times, $series, $action, $note);
        }
        else
        {
          notifyAdminOnDelete($mail_previous, $start_times, $series);
        }
      }
      ApiHelper::success(null);
    }
    ApiHelper::fail(get_vocab("no_access_to_entry"));
  }
}

ApiHelper::fail(get_vocab("fail_to_delete_entry"), ApiHelper::FAIL_TO_DELETE_ENTRY);

