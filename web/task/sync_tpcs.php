<?php

use MRBS\CalendarServer\CalendarServerManager;
use MRBS\DBHelper;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

require_once dirname(__DIR__) . "/defaultincludes.inc";

require_once dirname(__DIR__) . "/functions_table.inc";
require_once dirname(__DIR__) . "/mrbs_sql.inc";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

global $thirdCalendarService, $exchange_server, $exchange_sync_interval;

$tag = "[sync_exchange] ";
$areas = \MRBS\get_area_names();


while (true) {
  foreach ($areas as $id => $areaName) {
    $area = \MRBS\get_area_details($id);
    \MRBS\log_i($tag, "start handle area: " . count($area));
    $rooms = \MRBS\get_rooms($id);

    foreach ($rooms as $room) {
      try {
        \MRBS\log_i($tag, "start handle room: " . $room['id']);
        $fmtChangeList = array(
          "create" => array(),
          "update" => array(),
          "delete" => array(),
        );
        foreach ($thirdCalendarService as $serviceName => $config) {
          if ($area[$config["switch"]] == 1) {
            try {
              $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
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
            } catch (Exception $e) {
              \MRBS\log_i($tag, $e->getMessage());
              \MRBS\log_i($tag, $e->getTraceAsString());
            }
          }
        }
        foreach ($fmtChangeList["delete"] as $delete) {
          foreach ($thirdCalendarService as $serviceName => $config) {
            if ($area[$config["switch"]] != 1)
              continue;
            if ($delete["from"] == $serviceName) {
              continue;
            }
            $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
            $connector->deleteMeeting($delete["data"]);
          }
        }
        foreach ($fmtChangeList["create"] as $create) {
          if (!empty($create['data']['repeat_rule'])) {
            $reps = $create['data']['repeat_rule']->getRepeatStartTimes($create['data']['start_time']);
            $one = null;
            foreach ($reps as $rep) {
              $start = $rep;
              $end = $rep + $create['data']['duration'] * 60;
              $one = DBHelper::one(\MRBS\_tbl("entry"), "room_id = {$create['data']['room_id']} AND ((start_time <= {$start} AND end_time > {$start}) OR (start_time < {$end} AND end_time >= {$end}) OR (start_time >= {$start} AND start_time < {$end}) OR (end_time >= {$end} AND end_time < {$end}))");
              if (!empty($one)) {
                foreach ($thirdCalendarService as $serviceName => $config) {
                  if ($area[$config["switch"]] != 1)
                    continue;
                  $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
                  if ($create["from"] == $serviceName) {
                    $connector->declineMeeting($create, "conflict with " . $one['name'] . " , id " . $one['id']);
                  }
                }
                break;
              }
            }
            if (!empty($one)) {
              continue;
            }
            $result = \MRBS\mrbsCreateRepeatingEntrys($create['data']);
            if ($result['id'] == 0) {
              foreach ($thirdCalendarService as $serviceName => $config) {
                $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
                $connector->declineMeeting($create, "Recurring Meeting create too many meetings or create no meetings");
              }

            } else {
              foreach ($thirdCalendarService as $serviceName => $config) {
                if ($area[$config["switch"]] != 1)
                  continue;
                $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
                if ($create["from"] == $serviceName) {
                  $connector->acceptMeeting($create, "");
                } else
                  $connector->createMeeting($create["data"]);
              }
            }
          } else {
            $success = DBHelper::insert(\MRBS\_tbl("entry"), $create["data"]) ?? false;
            foreach ($thirdCalendarService as $serviceName => $config) {
              if (!$success && $create["from"] == $serviceName) {
                $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
                $connector->declineMeeting($create, "DB Failed");
                break;
              } else if (!$success && $create["from"] != $serviceName) {
                continue;
              }
              if ($area[$config["switch"]] != 1)
                continue;
//            if ($create["from"] == $serviceName) {
//              continue;
//            }
              $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
              if ($create["from"] == $serviceName) {
                $connector->acceptMeeting($create, "");
              } else
                $connector->createMeeting($create["data"]);
            }
          }
        }
        foreach ($fmtChangeList["update"] as $update) {
          $id = $update["data"]["id"];
          unset($update["data"]["id"]);
          DBHelper::update(\MRBS\_tbl("entry"), $update["data"], array("id" => $id));
          foreach ($thirdCalendarService as $serviceName => $config) {
            if ($area[$config["switch"]] != 1)
              continue;
            if ($update["from"] == $serviceName) {
              continue;
            }
            $connector = CalendarServerManager::getServer($config, $exchange_server, $area['timezone'], $room);
            $connector->updateMeeting($update["data"]);
          }
        }
        $roomId = $room["id"];
        $createCount = count($fmtChangeList["create"]);
        $updateCount = count($fmtChangeList["update"]);
        $deleteCount = count($fmtChangeList["delete"]);
        \MRBS\log_i($tag, "end handle room:  $roomId. create: $createCount, update: $updateCount, delete: $deleteCount");
      } catch (Exception $e) {
        \MRBS\log_i($tag, $e->getMessage());
        \MRBS\log_i($tag, $e->getTraceAsString());
      }
    }
  }
  \MRBS\log_i($tag, "done!");
  sleep($exchange_sync_interval ?? 10);
}
