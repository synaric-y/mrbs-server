<?php

global $exchange_server;

require_once dirname(__DIR__, 3) . "/vendor/autoload.php";

use garethp\ews\API;
use garethp\ews\API\Message\SyncFolderItemsResponseMessageType;
use garethp\ews\API\Type\CalendarItemType;
use MRBS\DBHelper;
use MRBS\Intl\IntlDateFormatter;
use MRBS\RepeatRule;
use function MRBS\_tbl;
use function MRBS\get_vocab;
use MRBS\ApiHelper;

try{
    $api = API::withUsernameAndPassword($exchange_server, "Room.Dev3", "JI%oSlfOS9");
    $calendar = $api->getCalendar();
    $calendar->getCalendarItems();
    ApiHelper::success(null);
}catch(GuzzleHttp\Exception\ConnectException $e){
    ApiHelper::fail(get_vocab("exchange_disconnect"), ApiHelper::UNKNOWN_ERROR);
}
