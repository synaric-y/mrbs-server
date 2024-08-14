<?php

namespace MRBS\CalendarServer;

use function MRBS\get_area_details;
use function MRBS\get_entry_by_id;
use function MRBS\get_room_details;

class CalendarServerManager
{

  public static function createMeeting($id)
  {
    global $thirdCalendarService;
    $entry = get_entry_by_id($id);
    if ($thirdCalendarService["exchange"] && $thirdCalendarService["exchange"]["sync"] == "two-way") {
      $room = get_room_details($entry["room_id"]);
      $area = get_area_details($room["area_id"]);
      $connector = new ExchangeCalendarServerConnector(
        $area["exchange_server"],
        $room["exchange_username"],
        $room["exchange_password"],
        $area["timezone"]
      );
      $connector->createMeeting($entry);
    }
  }

  public static function deleteMeeting($id)
  {
    global $thirdCalendarService;
    $entry = get_entry_by_id($id);
    if ($thirdCalendarService["exchange"] && $thirdCalendarService["exchange"]["sync"] == "two-way") {
      $room = get_room_details($entry["room_id"]);
      $area = get_area_details($room["area_id"]);
      $connector = new ExchangeCalendarServerConnector(
        $area["exchange_server"],
        $room["exchange_username"],
        $room["exchange_password"],
        $area["timezone"]
      );
      $connector->deleteMeeting($entry);
    }
  }

  // Notify third-party services to update meetings.
  public static function updateMeeting($id)
  {
    global $thirdCalendarService;
    $entry = get_entry_by_id($id);
    if ($thirdCalendarService["exchange"] && $thirdCalendarService["exchange"]["sync"] == "two-way") {
      $room = get_room_details($entry["room_id"]);
      $area = get_area_details($room["area_id"]);
      $connector = new ExchangeCalendarServerConnector(
        $area["exchange_server"],
        $room["exchange_username"],
        $room["exchange_password"],
        $area["timezone"]
      );
      $connector->updateMeeting($entry);
    }
  }
}
