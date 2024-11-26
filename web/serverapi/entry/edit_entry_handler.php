<?php
declare(strict_types=1);

namespace MRBS;


require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/functions_ical.inc';
require_once dirname(__DIR__, 2) . '/functions_mail.inc';

use MRBS\CalendarServer\CalendarServerManager;

global $min_booking_admin_level;


if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

//if (getLevel($_SESSION['user']) < $min_booking_admin_level){
//  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
//}

$mrbs_username = $_SESSION['user'];

function sanitize_room_id($id): int
{
  if (empty($id)) {
    throw new Exception("Room id not set");
  }

  if ($id === '') {
    throw new Exception("Room id is ''");
  }

  return intval($id);
}

$create_by = $_SESSION['user'];
$book_by = $_SESSION['user'];

// Get non-standard form variables
$form_vars = array(
  'name' => 'string',
//  'book_by' => 'string',
  'description' => 'string',
  'start_seconds' => 'int',
  'start_date' => 'string',
  'end_seconds' => 'int',
  'end_date' => 'string',
  'all_day' => 'string',  // bool, actually
  'type' => 'string',
  'rooms' => 'array',
  'original_room_id' => 'int',
  'ical_uid' => 'string',
  'ical_sequence' => 'int',
  'ical_recur_id' => 'string',
  'allow_registration' => 'string',  // bool, actually
  'registrant_limit' => 'int',
  'registrant_limit_enabled' => 'string',  // bool, actually
  'registration_opens_value' => 'int',
  'registration_opens_units' => 'string',
  'registration_opens_enabled' => 'string',  // bool, actually
  'registration_closes_value' => 'int',
  'registration_closes_units' => 'string',
  'registration_closes_enabled' => 'string',  // bool, actually
  'returl' => 'string',
  'id' => 'int',
  'rep_id' => 'int',
  'edit_series' => 'bool',
  'rep_type' => 'int',
  'rep_end_date' => 'string',
  'rep_day' => 'array',   // array of bools
  'rep_interval' => 'int',
  'month_type' => 'int',
  'month_absolute' => 'int',
  'month_relative_ord' => 'string',
  'month_relative_day' => 'string',
  'skip' => 'bool',
  'no_mail' => 'bool',
  'private' => 'string',  // bool, actually
  'confirmed' => 'string',
  'back_button' => 'string',
  'timetohighlight' => 'int',
  'commit' => 'string'
);

$just_check = false;

foreach ($form_vars as $var => $var_type) {
  if (isset($_POST[$var]))
    $$var = $_POST[$var];
  if ($var_type == 'string') {
    $$var = "" . $$var;
  }
  // Trim the strings and truncate them to the maximum field length
  if (!empty($$var) && is_string($$var)) {
    $$var = trim($$var);
    $$var = truncate($$var, "entry.$var");
  }

}


if (empty($_POST['edit_series'])) {
  if ($end_seconds < time()) {
    ApiHelper::fail(get_vocab("expired_end_time"), ApiHelper::EXPIRED_END_TIME);
  }
  if ($start_date != $end_date) {
    ApiHelper::fail(get_vocab("not_support_multi_day"), ApiHelper::NOT_SUPPORT_MULTI_DAY);
  }
  if ($start_date == $end_date && $end_seconds < $start_seconds) {
    ApiHelper::fail(get_vocab("expired_end_time"), ApiHelper::EXPIRED_END_TIME);
  }
}
$id = intval($_POST["id"]);
$midnight = strtotime("midnight", intval($start_seconds));
$start_seconds -= $midnight;
$midnight = strtotime("midnight", intval($end_seconds));
$end_seconds -= $midnight;
$confirmed = "";
$skip = boolval($skip);
$edit_series = boolval($edit_series);

if (!empty($id) && db()->query1("SELECT * FROM " . _tbl("entry") . " WHERE id = ?", array($id)) < 1) {
  ApiHelper::fail(get_vocab("edit_entry_not_exist"), ApiHelper::ENTRY_NOT_EXIST);
}

// Provide a default for $rep_interval (it could be null in an Ajax post request
// if the user has an empty string in the input).
if (!isset($rep_interval)) {
  $rep_interval = 1;
}

// Sanitize the room ids
$rooms = array_map(__NAMESPACE__ . '\sanitize_room_id', $rooms);

$result = db()->query("SELECT R.disabled as room_disabled, A.disabled as area_disabled FROM " . _tbl("room") . " R LEFT JOIN " . _tbl("area") . " A ON R.area_id = A.id WHERE R.id = ?", array($rooms[0]));
if ($result->count() < 1) {
  ApiHelper::fail(get_vocab("room_not_exist"), ApiHelper::ROOM_NOT_EXIST);
}
$row = $result->next_row_keyed();
if ($row['room_disabled'] == 1 || $row['area_disabled'] == 1) {
  ApiHelper::fail(get_vocab("area_or_room_disabled"), ApiHelper::AREA_OR_ROOM_DISABLED);
}
// Query old entry and reset create_by
if (!empty($id)) {
  $old_entry = db()->query("SELECT * FROM " . _tbl("entry") . " WHERE id = ?", array($id))->next_row_keyed();
  if (empty($old_entry)) {
    ApiHelper::fail(get_vocab("edit_entry_not_exist"), ApiHelper::ENTRY_NOT_EXIST);
  }
  $create_by = $old_entry['create_by'];
  $book_by = $old_entry['book_by'];
}

// Convert the registration opens and closes times into seconds
if (isset($registration_opens_value) && isset($registration_opens_units)) {
  $registration_opens = $registration_opens_value;
  fromTimeString($registration_opens, $registration_opens_units);
  $registration_opens = constrain_int($registration_opens, 4);
}

if (isset($registration_closes_value) && isset($registration_closes_units)) {
  $registration_closes = $registration_closes_value;
  fromTimeString($registration_closes, $registration_closes_units);
  $registration_closes = constrain_int($registration_closes, 4);
}

// Convert the database booleans (the custom field booleans are done later)
foreach (['allow_registration', 'registrant_limit_enabled', 'registration_opens_enabled', 'registration_closes_enabled'] as $var) {
  $$var = !empty($$var) ? 1 : 0;
}

// If they're not an admin and multi-day bookings are not allowed, then
// set the end date to the start date
//if (!is_book_admin($rooms) && $auth['only_admin_can_book_multiday']) {
if ($auth['only_admin_can_book_multiday']) {
  $end_date = $start_date;
}

if (false === ($start_date_split = split_iso_date($start_date))) {
  ApiHelper::fail(get_vocab("invalid_start_time"), ApiHelper::INVALID_START_TIME);
}
list($start_year, $start_month, $start_day) = $start_date_split;

if (false === ($end_date_split = split_iso_date($end_date))) {
  ApiHelper::fail(get_vocab("invalid_end_time"), ApiHelper::INVALID_END_TIME);
}

list($end_year, $end_month, $end_day) = $end_date_split;

$result = db()->query("SELECT * FROM " . _tbl("room") . " WHERE id = ?", $rooms);
$room = $result->next_row_keyed();
if (!user_can_book($mrbs_username, $room)) {
  ApiHelper::fail(get_vocab("group_limit"), ApiHelper::GROUP_LIMIT);
}

// Get custom form variables
$custom_fields = array();

// Get the information about the fields in the entry table
$fields = db()->field_info(_tbl('entry'));

foreach ($fields as $field) {
  if (!in_array($field['name'], $standard_fields['entry'])) {
    switch ($field['nature']) {
      case 'character':
        $f_type = 'string';
        break;
      case 'integer':
        // Smallints and tinyints are considered to be booleans
        $f_type = (isset($field['length']) && ($field['length'] <= 2)) ? 'string' : 'int';
        break;
      case 'decimal':
        $f_type = 'decimal';
        break;
      // We can only really deal with the types above at the moment
      default:
        $f_type = 'string';
        break;
    }

    $var = VAR_PREFIX . $field['name'];
    $custom_fields[$field['name']] = get_form_var($var, $f_type);

    if (($f_type == 'int') && ($custom_fields[$field['name']] === '')) {
      $custom_fields[$field['name']] = null;
    }
// Turn checkboxes into booleans
    if (($field['nature'] == 'integer') &&
      isset($field['length']) &&
      ($field['length'] <= 2)) {
      $custom_fields[$field['name']] = (bool)$custom_fields[$field['name']];
    }

// Trim any strings and truncate them to the maximum field length
    if (is_string($custom_fields[$field['name']]) && ($field['nature'] != 'decimal')) {
      $custom_fields[$field['name']] = trim($custom_fields[$field['name']]);
      $custom_fields[$field['name']] = truncate($custom_fields[$field['name']], 'entry.' . $field['name']);
    }

  }
}


// (3) Clean up the form variables
// -------------------------------

// Form validation checks.   Normally checked for client side.

// Validate the create_by variable, checking that it's the current user, unless the
// user is an admin and the booking is being edited or it's a new booking and we allow
// admins to make bookings on behalf of others.
//
// Only carry out this check if it's not an Ajax request.  If it is an Ajax request then
// $create_by isn't set yet, but a getWritable check will be done later,

if (!isset($create_by)) {
  // Shouldn't happen, unless something's gone wrong with the form or the POST request.
  throw new Exception('$create_by not set');
}
if (!is_book_admin($rooms) || (empty($id) && $auth['admin_can_only_book_for_self'])) {
  if ($create_by !== $mrbs_username) {
    $message = "Attempt made by user '$mrbs_username' to make a booking in the name of '$create_by'";
    trigger_error($message, E_USER_NOTICE);
    $create_by = $mrbs_username;
  }
}

if (empty($rooms)) {
  ApiHelper::fail(get_vocab("invalid_room_name"), ApiHelper::INVALID_ROOM_NAME);
}
if (!isset($name) || ($name === '')) {
  ApiHelper::fail(get_vocab("empty_name"), ApiHelper::EMPTY_NAME);
}

if (($rep_type != RepeatRule::NONE) && ($rep_interval < 1)) {
  ApiHelper::fail(get_vocab("invalid_rep_interval"), ApiHelper::INVALID_REP_INTERVAL);
}
//
if (count($is_mandatory_field)) {
  foreach ($is_mandatory_field as $field => $value) {
    $field = preg_replace('/^entry\./', '', $field);
    if ($value) {
      if ((in_array($field, $standard_fields['entry']) && ($$field === '')) ||
        (array_key_exists($field, $custom_fields) && ($custom_fields[$field] === ''))) {
        ApiHelper::fail(get_vocab("missing_mandatory_field"), ApiHelper::MISSING_MANDATORY_FIELD);
      }
    }
  }
}


if (!isset($type)) {
  $type = $default_type;
}

// Check that the type is allowed
if (!is_book_admin($rooms) && isset($auth['admin_only_types']) && in_array($type, $auth['admin_only_types'])) {
  ApiHelper::fail(get_vocab("type_reserved_for_admins", get_type_vocab($type)), ApiHelper::TYPE_RESERVED_FOR_ADMINS);
}

if (isset($month_relative_ord) && isset($month_relative_day)) {
  $month_relative = $month_relative_ord . $month_relative_day;
}

// Handle private booking
// Enforce config file settings if needed
if ($private_mandatory && !is_book_admin()) {
  $isprivate = $private_default;
} else {
  $isprivate = (bool)$private;
}

// Make sure the area corresponds to the room that is being booked
$area = get_area($rooms[0]);
get_area_settings($area);  // Update the area settings

// Check that they really are allowed to set $no_mail;
if ($no_mail) {
  if (!$mail_settings['allow_no_mail'] &&
    (!is_book_admin($rooms) || !$mail_settings['allow_admins_no_mail'])) {
    $no_mail = false;
  }
}

// If this is an Ajax request and we're being asked to commit the booking, then
// we'll only have been supplied with parameters that need to be changed.  Fill in
// the rest from the existing booking information.
// Note: we assume that
// (1) this is not a series (we can't cope with them yet)
// (2) we always get passed start_seconds and end_seconds in the Ajax data

if (!empty($id)) {
  $old_booking = get_booking_info($id, false);

  foreach ($form_vars as $var => $var_type) {
    if (!isset($$var) || (($var_type == 'array') && empty($$var))) {
      switch ($var) {
        case 'rep_type':
          // If it's a series we're just going to change this entry
          $$var = RepeatRule::NONE;
          break;
        case 'rooms':
          $rooms = array($old_booking['room_id']);
          break;
        case 'original_room_id':
          $$var = $old_booking['room_id'];
          break;
        case 'private':
          $$var = $old_booking['private'];
          break;
        case 'confirmed':
          $$var = !$old_booking['tentative'];
          break;
        // In the calculation of $start_seconds and $end_seconds below we need to take
        // care of the case when 0000 on the day in question is across a DST boundary
        // from the current time, ie the days on which DST starts and ends.
        case 'start_seconds';
          $date = getdate($old_booking['start_time']);
          $start_year = (int)$date['year'];
          $start_month = (int)$date['mon'];
          $start_day = (int)$date['mday'];
          $start_daystart = mktime(0, 0, 0, $start_month, $start_day, $start_year);
          $old_start = $old_booking['start_time'];
          $start_seconds = $old_start - $start_daystart;
          $start_seconds -= cross_dst($start_daystart, $old_start);
          break;
        case 'end_seconds';
          $date = getdate($old_booking['end_time']);
          $end_year = (int)$date['year'];
          $end_month = (int)$date['mon'];
          $end_day = (int)$date['mday'];
          $end_daystart = mktime(0, 0, 0, $end_month, $end_day, $end_year);
          $old_end = $old_booking['end_time'];
          $end_seconds = $old_end - $end_daystart;
          $end_seconds -= cross_dst($end_daystart, $old_end);
          // When using periods end_seconds is actually the start of the last period
          if ($enable_periods) {
            $end_seconds -= 60;
          }
          break;
        default:
          if (array_key_exists($var, $old_booking)) {
            $$var = $old_booking[$var];
          }
          break;
      }
    }
  }
}

// Now the custom fields
$custom_fields = array();
foreach ($fields as $field) {
  if (!in_array($field['name'], $standard_fields['entry'])) {
    $custom_fields[$field['name']] = $old_booking[$field['name']];
  }
}


// When All Day is checked, $start_seconds and $end_seconds are disabled and so won't
// get passed through by the form.   We therefore need to set them.
if (!empty($all_day)) {
  if ($enable_periods) {
    $start_seconds = 12 * SECONDS_PER_HOUR;
    // This is actually the start of the last period, which is what the form would
    // have returned.   It will get corrected in a moment.
    $end_seconds = $start_seconds + ((count($periods) - 1) * 60);
  } else {
    $start_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
    $end_seconds = (($eveningends * 60) + $eveningends_minutes) * 60;
    $end_seconds += $resolution;  // We want the end of the last slot, not the beginning
    if ($end_seconds <= $start_seconds) {
      $end_seconds += SECONDS_PER_DAY;
    }
  }
}

// If we're operating on a booking day that stretches past midnight, it's more convenient
// for the sections past midnight to be shown as being on the day before.  That way the
// $returl will end up taking us back to the day we started on
if (day_past_midnight()) {
  $end_last = (((($eveningends * 60) + $eveningends_minutes) * 60) + $resolution) % SECONDS_PER_DAY;
  if ($start_seconds < $end_last) {
    $start_seconds += SECONDS_PER_DAY;
    $day_before = getdate(mktime(0, 0, 0, $start_month, $start_day - 1, $start_year));
    $start_day = (int)$day_before['mday'];
    $start_month = (int)$day_before['mon'];
    $start_year = (int)$day_before['year'];
  }
}

$target_rooms = $rooms;

// Check that the user has permission to create/edit an entry for this room.
// Get the id of the room that we are creating/editing
if (!empty($id)) {
  // Editing an existing booking: get the room_id from the database (you can't
  // get it from $rooms because they are the new rooms)
  $sql = "SELECT room_id
            FROM " . _tbl('entry') . "
           WHERE id=?
           LIMIT 1";
  $existing_room = db()->query1($sql, array($id));
  if ($existing_room < 0) {
    // Ideally we should give more feedback to the user when this happens, or
    // even lock the entry once a user starts to edit it.
    ApiHelper::fail(get_vocab("edit_entry_not_exist"), ApiHelper::EDIT_ENTRY_NOT_EXIST);
  }
  $target_rooms[] = $existing_room;
  $target_rooms = array_unique($target_rooms);
}

// Must have write access to at least one of the rooms
if (!getWritable($create_by, $target_rooms, false)) {
  ApiHelper::fail(get_vocab("no_access_to_entry"), ApiHelper::NO_ACCESS_TO_ENTRY);
}

if ($enable_periods) {
  $resolution = 60;
}

// Now work out the start and times
$start_time = mktime(0, 0, $start_seconds, $start_month, $start_day, $start_year);
$end_time = mktime(0, 0, $end_seconds, $end_month, $end_day, $end_year);

// If we're using periods then the endtime we've been returned by the form is actually
// the beginning of the last period in the booking (it's more intuitive for users this way)
// so we need to add on 60 seconds (1 period)
if ($enable_periods) {
  $end_time = $end_time + 60;
}

// Round down the starttime and round up the endtime to the nearest slot boundaries
// (This step is probably unnecessary now that MRBS always returns times aligned
// on slot boundaries, but is left in for good measure).
$start_first_slot = get_start_first_slot($start_month, $start_day, $start_year);
$start_time = round_t_down($start_time, 1, $start_first_slot);
$start_first_slot = get_start_first_slot($end_month, $end_day, $end_year);
$end_time = round_t_up($end_time, 1, $start_first_slot);

// If they asked for 0 minutes, and even after the rounding the slot length is still
// 0 minutes, push that up to 1 resolution unit.
if ($end_time == $start_time) {
  $end_time += $resolution;
}

if (!isset($rep_type)) {
  $rep_type = RepeatRule::NONE;
}

if (!isset($rep_day)) {
  $rep_day = array();
}

// Get the repeat details
$repeat_rule = new RepeatRule();
$repeat_rule->setType($rep_type ?? RepeatRule::NONE);

if ($repeat_rule->getType() != RepeatRule::NONE) {
  $repeat_rule->setInterval($rep_interval);
  if ($repeat_rule->getType() == RepeatRule::MONTHLY) {
    $repeat_rule->setMonthlyType($month_type);
    if ($repeat_rule->getMonthlyType() == RepeatRule::MONTHLY_ABSOLUTE) {
      $repeat_rule->setMonthlyAbsolute($month_absolute);
    } else {
      $repeat_rule->setMonthlyRelative($month_relative);
    }
  }
  if (isset($rep_end_date)) {
    $repeat_end_date = DateTime::createFromFormat('Y-m-d', $rep_end_date);
    if ($repeat_end_date === false) {
      throw new Exception("Could not create repeat end date");
    }
    $repeat_end_date->setTime(intval($start_seconds / SECONDS_PER_HOUR), intval(($start_seconds % SECONDS_PER_HOUR) / 60));
    $repeat_rule->setEndDate($repeat_end_date);
  }

  if ($repeat_rule->getType() == RepeatRule::WEEKLY) {
    // If no repeat day has been set, then set a default repeat day
    // as the day of the week of the start of the period
    $repeat_rule->setDays((count($rep_day) > 0) ? $rep_day : array(date('w', $start_time)));
  }

  // Make sure that the starttime coincides with a repeat day.  In
  // other words make sure that the first starttime defines an actual
  // entry.   We need to do this because if we are going to construct an iCalendar
  // object, RFC 5545 demands that the start time is the first event of
  // a series.  ["The "DTSTART" property for a "VEVENT" specifies the inclusive
  // start of the event.  For recurring events, it also specifies the very first
  // instance in the recurrence set."]

  // Get the first entry in the series and make that the start time
  $reps = $repeat_rule->getRepeatStartTimes($start_time, 1);

  if (count($reps) > 0) {
    $duration = $end_time - $start_time;
    $duration -= cross_dst($start_time, $end_time);
    $start_time = $reps[0];
    $end_time = $start_time + $duration;
    $start_day = (int)date('j', $start_time);
    $start_month = (int)date('n', $start_time);
    $start_year = (int)date('Y', $start_time);
  }
}


//// If the old sticky room is one of the rooms requested for booking, then don't change the sticky room.
//// Otherwise change the sticky room to be one of the new rooms.
if (!in_array($room, $rooms)) {
  $room = $rooms[0];
}
// Find the corresponding area
$area = mrbsGetRoomArea($room);

// Now construct the new query string


// Check to see whether this is a repeat booking and if so, whether the user
// is allowed to make/edit repeat bookings.   (The edit_entry form should
// prevent you ever getting here, but this check is here as a safeguard in
// case someone has spoofed the HTML)
if (isset($rep_type) && ($rep_type != RepeatRule::NONE) &&
  !is_book_admin($rooms) &&
  !empty($auth['only_admin_can_book_repeat'])) {
  ApiHelper::fail(get_vocab("no_access_to_entry"), ApiHelper::NO_ACCESS_TO_ENTRY);
}


// (4) Assemble the booking data
// -----------------------------

// Assemble an array of bookings, one for each room
$bookings = array();
foreach ($rooms as $room_id) {
  // Ignore rooms for which the user doesn't have write access
  if (!getWritable($create_by, $room_id)) {
    continue;
  }

  $booking = array();
  $booking['create_by'] = $create_by;
  $booking['modified_by'] = (!empty($id)) ? $mrbs_username : '';
  $booking['name'] = $name;
  $booking['book_by'] = $book_by;
  $booking['type'] = $type;
  $booking['description'] = $description;
  $booking['room_id'] = $room_id;
  $booking['start_time'] = $start_time;
  $booking['end_time'] = $end_time;
  $ical_uid = generate_global_uid($name);
  $booking['ical_uid'] = $ical_uid;
  if (!empty($id)) {
    $ical_sequence = db()->query("SELECT ical_sequence FROM " . _tbl("entry") . " WHERE id = ?", array($id))->next_row_keyed()['ical_sequence'] + 1;
  } else {
    $ical_sequence = 1;
  }
  $booking['ical_sequence'] = $ical_sequence;
  $booking['ical_recur_id'] = $ical_recur_id;
  $booking['allow_registration'] = $allow_registration;
  $booking['registrant_limit'] = $registrant_limit;
  $booking['registrant_limit_enabled'] = $registrant_limit_enabled;
  $booking['registration_opens'] = (isset($registration_opens)) ? $registration_opens : null;
  $booking['registration_opens_enabled'] = $registration_opens_enabled;
  $booking['registration_closes'] = (isset($registration_closes)) ? $registration_closes : null;
  $booking['registration_closes_enabled'] = $registration_closes_enabled;
  $booking['repeat_rule'] = $repeat_rule;

  // Do the custom fields
  foreach ($custom_fields as $key => $value) {
    $booking[$key] = $value;
  }

  // Set the various statuses as appropriate
  // (Note: the statuses fields are the only ones that can differ by room)

  // Privacy status
  $booking['private'] = (bool)$isprivate;

  // If we are using booking approvals then we need to work out whether the
  // status of this booking is approved.   If the user is allowed to approve
  // bookings for this room, then the status will be approved, since they are
  // in effect immediately approving their own booking.  Otherwise the booking
  // will need to approved.
  $booking['awaiting_approval'] = ($approval_enabled && !is_book_admin($room_id));

  // Confirmation status
  $booking['tentative'] = ($confirmation_enabled && !$confirmed);

  $bookings[] = $booking;
}

//$just_check = $is_ajax && !$commit;
$this_id = (!empty($id)) ? $id : null;
$send_mail = !$no_mail && need_to_send_mail();
try {
  // Wrap the editing process in a transaction, because we'll want to roll back the edit if the
  // deletion of the old booking fails.  This could happen, for example, if
  //    (a) somebody else has already edited the booking and the original booking no longer exists; or
  //    (b) if there's some other problem, eg the database user hasn't been granted DELETE rights, in which
  //        case we would be left with two overlapping bookings.
  db()->begin();
  $transaction_ok = true;


  $result = mrbsMakeBookings($bookings, $this_id, $just_check, $skip, $original_room_id, $send_mail, $edit_series);
  // Notify the third-party Calendar service that a meeting has been created
  if (!$just_check && $result['valid_booking'] && empty($id)) {
    if ($result["new_details"][0]['id']) {
      foreach ($result["new_details"] as $d) {
        if ($edit_series) {
          CalendarServerManager::createRepeatMeeting($result["new_details"][0]['id'], $rep_end_date);
        } else {
          CalendarServerManager::createMeeting($d['id']);
        }
      }
    }
  }
  // If we weren't just checking and this was a successful booking and
  // we were editing an existing booking, then delete the old booking
  if (!$just_check && $result['valid_booking'] && !empty($id)) {
    // Notify the third-party Calendar service that a meeting has been updated
    if ($result["new_details"]) {
      foreach ($result["new_details"] as $d) {
        if ($edit_series) {
          // Actually delete old Calendar Item
          CalendarServerManager::deleteMeeting($id);
          // Wait Exchange Server to delete old one
          sleep(1);
          // Then create a new one
          CalendarServerManager::createRepeatMeeting($result["new_details"][0]['id'], $rep_end_date);
        } else
          CalendarServerManager::updateMeeting($d['id']);
      }
    }
    $transaction_ok = mrbsDelEntry($id, $edit_series, true);

  }

  if ($transaction_ok) {
    db()->commit();
  } else {
    db()->rollback();
    if (!$just_check && $result['valid_booking'] && empty($id)) {
      if ($result["new_details"]) {
        foreach ($result["new_details"] as $d) {
          if ($edit_series) {
            $fetch = db()->query("SELECT id FROM " . _tbl("entry") . " WHERE repeat_id = ?", array($d['id']));
            while ($row = $fetch->next_row_keyed()) {
              CalendarServerManager::deleteMeeting($row['id']);
            }
          } else {
            CalendarServerManager::deleteMeeting($d['id']);
          }
        }
      }
    }
    CalendarServerManager::createMeeting($id);
    ApiHelper::fail(get_vocab("no_access_no_policy"), ApiHelper::NO_ACCESS_NO_POLICY);
  }

} catch (\Exception $e) {
  ApiHelper::fail("", ApiHelper::UNKNOWN_ERROR);
}
if ($result['valid_booking']) {
  if ($result['new_details'][0]['id'] != 0) {
    if (!empty($result['conflicts'])) {
      foreach ($result['conflicts'] as $conflict)
        $data1["conflicts"][] = $conflict;
    }
    $entries_id = array_column($result['new_details'], 'id');
    $query_sql = "SELECT id, start_time, end_time, entry_type, room_id, create_by, name, type, description, book_by FROM " . _tbl("entry");
    if ($edit_series){
      $query_sql .= " WHERE repeat_id = ?";
    }
    else {
      $query_sql .= " WHERE id = ?";
    }
    $query_result = db()->query($query_sql, $entries_id);
    $data1["entries"] = $query_result->all_rows_keyed();
    ApiHelper::success($data1);
  } else {
    ApiHelper::fail(get_vocab("repeat_entry_conflict"), ApiHelper::REPEAT_ENTRY_CONFLICT);
  }
} else if ($result['new_details'][0]['id'] != 0) {
  ApiHelper::fail(get_vocab("entry_conflict"), ApiHelper::ENTRY_CONFLICT, $result['conflicts']);
} else {
  ApiHelper::fail(get_vocab("entry_conflict"), ApiHelper::ENTRY_CONFLICT, $result['conflicts']);
}
