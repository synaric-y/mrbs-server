<?php

global $exchange_server;

require_once dirname(__DIR__, 3) . "/vendor/autoload.php";
require_once dirname(__DIR__, 2) . "/defaultincludes.inc";

use garethp\ews\API;
use garethp\ews\API\Message\SyncFolderItemsResponseMessageType;
use garethp\ews\API\Type\CalendarItemType;
use MRBS\DBHelper;
use MRBS\Intl\IntlDateFormatter;
use MRBS\RepeatRule;
use function MRBS\_tbl;
use function MRBS\get_vocab;
use MRBS\ApiHelper;

/*
 * though getting calendar item to check if the exchange server can be connected
 */

$server_address = $_POST['server_address'] ?? $exchange_server;
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

try {
//    $api = API::withUsernameAndPassword($exchange_server, "Room.Dev3", "JI%oSlfOS9");
  $api = API::withUsernameAndPassword($server_address, $username, $password);
  $calendar = $api->getCalendar();
  $calendar->getCalendarItems();
  ApiHelper::success(null);
} catch (GuzzleHttp\Exception\ConnectException|SoapFault|InvalidArgumentException $e) {
  ApiHelper::fail(get_vocab("exchange_disconnect"), ApiHelper::UNKNOWN_ERROR);
} catch(API\Exception\UnauthorizedException $e){
  if(empty($username) && empty($password))
    ApiHelper::success(null);
  else
    ApiHelper::fail(get_vocab("invalid_username_or_password"), ApiHelper::INVALID_USERNAME_OR_PASSWORD);
}
