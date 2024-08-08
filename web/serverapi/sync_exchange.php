<?php

require_once dirname(__DIR__, 2) .'/vendor/autoload.php';

use garethp\ews\API;

ini_set('display_errors', 1);            //错误信息
ini_set('display_startup_errors', 1);    //php启动错误信息
error_reporting(E_ALL);



$api = API::withUsernameAndPassword('mail2013.businessconnectchina.com', 'Room.A', 'Ycu9cZe@Lt');
echo "ok", PHP_EOL;
$calendar = $api->getCalendar();

//Get all items from midday today
//$items = $calendar->getCalendarItems('12:00 PM');
$items = $calendar->getCalendarItems('08/08/2024', '08/10/2024');

$calendarItemList = $items->getItems()->getCalendarItem();
if (is_array($calendarItemList)) {
  foreach ($calendarItemList as $ci) {
    handleCalenderItem($ci);
  }
} else {
  if ($calendarItemList) {
    handleCalenderItem($calendarItemList);
  }
}

//$changes = $calendar->listChanges();
$syncState = "H4sIAAAAAAAEAGNgYGcAAotqE0tHE2NTA0ddZ3NHC10TR2djXSdnJ2ddNyMnC2cnczdLU1OD2vBgveDKvOTgksSSVOfEvMSiSgYr0nW65eekpBZ5pjBYkq43LLWoODM/j8GaaK3+QMuKS4JSk1Mzy1JTQjJzU0nwrU9icYlnXnFJYl5yqncqKb71zS9K9SxJzS32zwtOLSpLLSLByXDfhgNxUW5iUTYklrgYGISA0tDwAxkOUskgCJQyAGI9kBr+plvJFvPuu63fd4u77037MUaGs+vYX2YfO+bRWbqr8vemV84gVYy8DAxMDHwMzCAON0OG/ubFDdvveDAIMTAy8AIx0LqpQBlfxwBPX0c/kCIGN1O3MLByNBAAxHJI/DwglsaiDh2gOysILMoIJqdiyLKCJZ0y2AIYJBkYGgBPg7YytwIAAA==";

//Get a list of changes since we last asked for them
$changesSinceLsatCheck = $calendar->listChanges($syncState);

echo "done!", PHP_EOL;

function handleCalenderItem(API\Type\CalendarItemType $ci)
{
  echo "-----------------------------", PHP_EOL;
  echo "uid:", $ci->getUID(), PHP_EOL;
  echo "itemId:", $ci->getItemId()->getId(), PHP_EOL;
  echo "organizer:", $ci->getOrganizer()->getMailbox()->getName(), PHP_EOL;
  echo "dateTimestamp:", formatIOSTime($ci->getDateTimeStamp()), PHP_EOL;
  echo "start:", formatIOSTime($ci->getStart()), PHP_EOL;
  echo "end:", formatIOSTime($ci->getEnd()), PHP_EOL;
  echo "location:", $ci->getLocation(), PHP_EOL;
  echo "isMeeting:", $ci->isMeeting(), PHP_EOL;
  echo "isCancelled:", $ci->isCancelled(), PHP_EOL;
  echo "isRecurring:", $ci->isRecurring(), PHP_EOL;
  echo "myResponseType:", $ci->getMyResponseType(), PHP_EOL;  // Tentative Accept Decline
}

function formatIOSTime($time)
{
  $dateTime = new DateTime($time);
  $timeZone = new DateTimeZone('Asia/Shanghai');
  $dateTime->setTimeZone($timeZone);
  return $dateTime->format('Y-m-d H:i:s');
}

