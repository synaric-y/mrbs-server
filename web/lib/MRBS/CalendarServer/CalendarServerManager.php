<?php

namespace MRBS\CalendarServer;

use function MRBS\get_area_details;
use function MRBS\get_entry_by_id;
use function MRBS\get_room_details;

class CalendarServerManager
{

  public static function getServer($config, $area, $room)
  {
    $connectorName = $config["connector"];
    return new $connectorName(
      $area,
      $room,
    );
  }

  public static function createMeeting($id)
  {
    global $thirdCalendarService;
    $entry = get_entry_by_id($id);
    foreach ($thirdCalendarService as $serviceName => $config) {
      if ($config["sync"] == "two-way") {
        $room = get_room_details($entry["room_id"]);
        $area = get_area_details($room["area_id"]);
        $connector = CalendarServerManager::getServer($config, $area, $room);
        $connector->createMeeting($entry);
      }
    }
  }

  public static function deleteMeeting($id)
  {
    global $thirdCalendarService;
    $entry = get_entry_by_id($id);
    foreach ($thirdCalendarService as $serviceName => $config) {
      if ($config["sync"] == "two-way") {
        $room = get_room_details($entry["room_id"]);
        $area = get_area_details($room["area_id"]);
        $connector = CalendarServerManager::getServer($config, $area, $room);
        $connector->deleteMeeting($entry);
      }
    }
  }

  // Notify third-party services to update meetings.
  public static function updateMeeting($id)
  {
    global $thirdCalendarService;
    $entry = get_entry_by_id($id);
    foreach ($thirdCalendarService as $serviceName => $config) {
      if ($config["sync"] == "two-way") {
        $room = get_room_details($entry["room_id"]);
        $area = get_area_details($room["area_id"]);
        $connector = CalendarServerManager::getServer($config, $area, $room);
        $connector->updateMeeting($entry);
      }
    }
  }
}
