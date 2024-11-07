<?php

namespace MRBS\CalendarServer;

use function MRBS\_tbl;
use function MRBS\db;
use function MRBS\get_area_details;
use function MRBS\get_entry_by_id;
use function MRBS\get_repeat;
use function MRBS\get_room_details;

class CalendarServerManager
{

  public static function getServer($config, $server, $timezone, $room)
  {
    global $exchange_server;
    $server = $exchange_server;
    $connectorName = $config["connector"];
    try {
      $connector = new $connectorName(
        $server,
        $timezone,
        $room,
      );
      return $connector;
    } catch (\Exception $e) {
      return new EmptyCalendarServerConnector();
    }
  }

  public static function createMeeting($id)
  {
    if (empty($id)) {
      return;
    }
    global $thirdCalendarService, $exchange_server;
    $entry = get_entry_by_id($id);
    foreach ($thirdCalendarService as $serviceName => $config) {
      if ($config["sync"] == "two-way") {
        $room = get_room_details($entry["room_id"]);
        $area = get_area_details($room["area_id"]);
        if ($area[$config['switch']] != 1)
          continue;
        $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
        $connector->createMeeting($entry);
      }
    }
  }

  public static function deleteMeeting($id)
  {
    if (empty($id)) {
      return;
    }
    global $thirdCalendarService, $exchange_server;
    $entry = get_entry_by_id($id);
    foreach ($thirdCalendarService as $serviceName => $config) {
      if ($config["sync"] == "two-way") {
        $room = get_room_details($entry["room_id"]);
        $area = get_area_details($room["area_id"]);
        if ($area[$config['switch']] != 1)
          continue;
        $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
        $connector->deleteMeeting($entry);
      }
    }
  }

  // Notify third-party services to update meetings.
  public static function updateMeeting($id)
  {
    if (empty($id)) {
      return;
    }
    global $thirdCalendarService, $exchange_server;
    $entry = get_entry_by_id($id);
    foreach ($thirdCalendarService as $serviceName => $config) {
      if ($config["sync"] == "two-way") {
        $room = get_room_details($entry["room_id"]);
        $area = get_area_details($room["area_id"]);
        if ($area[$config['switch']] != 1)
          continue;
        $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
        $connector->updateMeeting($entry);
      }
    }
  }

  public static function createRepeatMeeting($id, $end_date){
    if (empty($id)) {
      return;
    }
    global $thirdCalendarService, $exchange_server;
    $entry = get_repeat($id);
    foreach ($thirdCalendarService as $serviceName => $config) {
      if ($config["sync"] == "two-way") {
        $room = get_room_details($entry["room_id"]);
        $area = get_area_details($room["area_id"]);
        if ($area[$config['switch']] != 1)
          continue;
        $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
        $connector->createRepeatMeeting($entry, $end_date);
      }
    }
  }
}
