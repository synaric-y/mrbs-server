<?php

use MRBS\CalendarServer\CalendarServerManager;
use MRBS\CalendarServer\ExchangeCalendarServerConnector;
use MRBS\CalendarServer\WxWorkCalendarServerConnector;
use MRBS\DBHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__) . "/defaultincludes.inc";
require_once dirname(__DIR__) . "/functions_table.inc";
require_once dirname(__DIR__) . "/mrbs_sql.inc";


ini_set('display_errors', 1);            //错误信息
ini_set('display_startup_errors', 1);    //php启动错误信息
error_reporting(E_ALL);

global $thirdCalendarService;
$tag = "[sync_exchange] ";
$areas = \MRBS\get_area_names();

while (true) {
  foreach ($areas as $id => $areaName) {
    $area = \MRBS\get_area_details($id);
    \MRBS\log_write($tag, "start handle area: " . json_encode($area));
    $rooms = \MRBS\get_rooms($id);

    foreach ($rooms as $room) {
      try {
        \MRBS\log_write($tag, "start handle room: " . json_encode($room));
        $fmtChangeList = array(
          "create" => array(),
          "update" => array(),
          "delete" => array(),
        );
        foreach ($thirdCalendarService as $serviceName => $config) {
          if ($area[$config["switch"]] == 1) {
            $connector = CalendarServerManager::getServer($config, $area, $room);
            $changeList = $connector->pullCalendarUpdate();
            if (!empty($changeList["create"])) {
              $fmtChangeList["create"] = array_merge($fmtChangeList["create"], $changeList["create"]);
            }
            if (!empty($changeList["update"])) {
              $fmtChangeList["update"] = array_merge($fmtChangeList["update"], $changeList["update"]);
            }
            if (!empty($changeList["delete"])) {
              $fmtChangeList["delete"] = array_merge($fmtChangeList["delete"], $changeList["delete"]);
            }
          }
        }
        foreach ($fmtChangeList["delete"] as $delete) {
          foreach ($thirdCalendarService as $serviceName => $config) {
            if ($delete["from"] == $serviceName) {
              continue;
            }
            $connector = CalendarServerManager::getServer($config, $area, $room);
            $connector->deleteMeeting($delete["data"]);
          }
        }
        foreach ($fmtChangeList["create"] as $create) {
          DBHelper::insert(\MRBS\_tbl("entry"), $create["data"]);
          foreach ($thirdCalendarService as $serviceName => $config) {
            if ($create["from"] == $serviceName) {
              continue;
            }
            $connector = CalendarServerManager::getServer($config, $area, $room);
            $connector->createMeeting($create["data"]);
          }
        }
        foreach ($fmtChangeList["update"] as $update) {
          $id = $update["data"]["id"];
          unset($update["data"]["id"]);
          DBHelper::update(\MRBS\_tbl("entry"), $update["data"], array("id" => $id));
          foreach ($thirdCalendarService as $serviceName => $config) {
            if ($update["from"] == $serviceName) {
              continue;
            }
            $connector = CalendarServerManager::getServer($config, $area, $room);
            $connector->updateMeeting($update["data"]);
          }
        }
        $roomId = $room["id"];
        $createCount = count($fmtChangeList["create"]);
        $updateCount = count($fmtChangeList["update"]);
        $deleteCount = count($fmtChangeList["delete"]);
        \MRBS\log_write($tag, "end handle room:  $roomId. create: $createCount, update: $updateCount, delete: $deleteCount");
      } catch (Exception $e) {
        \MRBS\log_write($tag, $e->getMessage());
        \MRBS\log_write($tag, $e->getTraceAsString());
      }
    }
  }

  \MRBS\log_write($tag, "done!");
  sleep(10);
}



