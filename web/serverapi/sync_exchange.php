<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once "../defaultincludes.inc";
require_once "../functions_table.inc";
require_once "../mrbs_sql.inc";


use MRBS\CalendarServer\ExchangeCalendarServerConnector;

ini_set('display_errors', 1);            //错误信息
ini_set('display_startup_errors', 1);    //php启动错误信息
error_reporting(E_ALL);

$tag = "[sync_exchange] ";
$areas = \MRBS\get_area_names();

foreach ($areas as $id => $areaName) {
  $area = \MRBS\get_area_details($id);
  echo $tag, "start handle area: " . json_encode($area), PHP_EOL;
  $rooms = \MRBS\get_rooms($id);

  foreach ($rooms as $room) {
    echo $tag, "start handle room: " . json_encode($room), PHP_EOL;
    $fmtChangeList = array(
      "create" => array(),
      "update" => array(),
      "delete" => array(),
    );
    if ($area["use_exchange"] == 1) {
      $connector = new ExchangeCalendarServerConnector(
        $area["exchange_server"],
        $room["exchange_username"],
        $room["exchange_password"],
        $area["timezone"]
      );
      $connector->setRoom($room);
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
}


echo "done!", PHP_EOL;

