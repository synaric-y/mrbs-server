<?php

namespace MRBS\CalendarServer;

use DateInterval;
use DateTime;
use DateTimeZone;
use garethp\ews\API;
use garethp\ews\API\Message\SyncFolderItemsResponseMessageType;
use MRBS\DBHelper;
use MRBS\Intl\IntlDateFormatter;
use function MRBS\_tbl;

class ExchangeCalendarServerConnector extends AbstractCalendarServerConnector
{

  static $TAG = "[ExchangeCalendarServerConnector] ";
  private $server;
  private $account;
  private $password;
  private $api;
  private $timezone;
  private $room;
  private $fmtChangeList = array(
    "create" => array(),
    "update" => array(),
    "delete" => array(),
  );


  public function __construct($server, $account, $password, $timezone)
  {
    $this->server = $server;
    $this->account = $account;
    $this->password = $password;
    $this->timezone = $timezone;
  }

  private function getCalendar() {
    if (empty($this->api)) {
      $this->api = API::withUsernameAndPassword($this->server, $this->account, $this->password);
    }
    return $this->api->getCalendar();
  }

  public function pullCalendarUpdate()
  {
    parent::pullCalendarUpdate();
    $now = new DateTime();
    $fmt = new IntlDateFormatter(
      'en_US',
      IntlDateFormatter::FULL,
      IntlDateFormatter::FULL,
      $this->timezone,
      IntlDateFormatter::GREGORIAN,
      'MM/dd/yyyy'

    );
    $oneWeekLater = new DateTime();
    $oneWeekLater->add(new DateInterval('P7D'));
    $searchCalendarStart = $fmt->format($now);
    $searchCalendarEnd = $fmt->format($oneWeekLater);
    echo $this::$TAG, "getCalendarItems: $searchCalendarStart ~ $searchCalendarEnd", PHP_EOL;

    $calendar = $this->getCalendar();
    $items = $calendar->getCalendarItems($searchCalendarStart, $searchCalendarEnd);
    $calendarItemList = $items->getItems()->getCalendarItem();

    // get recent change list
    $changesSinceLsatCheck = $calendar->listChanges($this->room["exchange_sync_state"] ?? null);
    if (empty($calendarItemList)) {
      $this->updateSyncState($changesSinceLsatCheck);
      return array();
    }
    if (!is_array($calendarItemList)) {
      $calendarItemList = array($calendarItemList);
    }
    echo $this::$TAG, "-----------------------------", PHP_EOL;
    echo $this::$TAG, "| print queried calendar", PHP_EOL;
    echo $this::$TAG, "-----------------------------", PHP_EOL;
    foreach ($calendarItemList as $ci) {
      $this->printCalenderItem($ci);
    }

    return $this->handleChangeList($changesSinceLsatCheck);
  }

  public function setRoom($room)
  {
    $this->room = $room;
  }

  private function printCalenderItem(API\Type\CalendarItemType $ci)
  {
    echo $this::$TAG, "-----------------------------", PHP_EOL;
    echo $this::$TAG, "itemId:", $ci->getItemId()->getId(), PHP_EOL;
    echo $this::$TAG, "organizer:", $ci->getOrganizer()->getMailbox()->getName(), PHP_EOL;
    echo $this::$TAG, "start:", $this->formatIOSTime($ci->getStart()), PHP_EOL;
    echo $this::$TAG, "end:", $this->formatIOSTime($ci->getEnd()), PHP_EOL;
    echo $this::$TAG, "myResponseType:", $ci->getMyResponseType(), PHP_EOL;  // values: Tentative/Accept/Decline
//    echo $this::$TAG, "dateTimestamp:", $this->formatIOSTime($ci->getDateTimeStamp()), PHP_EOL;
//    echo $this::$TAG, "lastModifiedTime:", $this->formatIOSTime($ci->getLastModifiedTime()), PHP_EOL;
//    echo $this::$TAG, "location:", $ci->getLocation(), PHP_EOL;
//    echo $this::$TAG, "isMeeting:", $ci->isMeeting(), PHP_EOL;
//    echo $this::$TAG, "isCancelled:", $ci->isCancelled(), PHP_EOL;
//    echo $this::$TAG, "isRecurring:", $ci->isRecurring(), PHP_EOL;
    echo $this::$TAG, "-----------------------------", PHP_EOL;
  }

  private function formatIOSTime($time)
  {
    $dateTime = new DateTime($time);
    $timeZone = new DateTimeZone($this->timezone);
    $dateTime->setTimeZone($timeZone);
    return $dateTime->format('Y-m-d H:i:s');
  }

  private function findItemIdById($id, $calendarItemList)
  {
    foreach ($calendarItemList as $ci) {
      if ($ci->getItemId()->getId() == $id) {
        return $ci->getItemId();
      }
    }

    return null;
  }

  private function updateSyncState(SyncFolderItemsResponseMessageType $changesSinceLsatCheck)
  {
//    $syncState = $changesSinceLsatCheck->getSyncState();
//    echo $this::$TAG, "new syncState = $syncState", PHP_EOL;
//    DBHelper::update(_tbl("room"), array("exchange_sync_state" => $syncState), array("id" => $this->room["id"]));
  }

  private function handleChangeList(SyncFolderItemsResponseMessageType $changesSinceLsatCheck)
  {
    if (empty($changesSinceLsatCheck->getChanges())) {
      $this->updateSyncState($changesSinceLsatCheck);
      return null;
    }
    try {
      // handle create
      $create = $changesSinceLsatCheck->getChanges()->getCreate();
      if (!empty($create)) {
        if (!is_array($create)) {
          $create = array($create);
        }
        foreach ($create as $createItem) {
          $searchResult = DBHelper::one(_tbl("entry"), array("exchange_id" => $createItem->getCalendarItem()->getItemId()->getId()), "id");
          if ($searchResult) {
            continue;
          }
          $ci = $createItem->getCalendarItem();
          $adapter = new CalendarAdapter($this->room, CalendarAdapter::$MODE_ADD);
          $this->fmtChangeList["create"][] = $adapter->exchangeCalendarToCalendar($ci);
//          try {
//            $this->getCalendar()->acceptMeeting($ci->getCalendarItem()->getItemId(), "OK");
//          } catch (\Exception $e) {
//            echo $this::$TAG, $e->getMessage();
//            echo $this::$TAG, $e->getTraceAsString();
//          }
        }
      }
      // handle update
      $update = $changesSinceLsatCheck->getChanges()->getUpdate();
      if (!empty($update)) {
        if (!is_array($update)) {
          $update = array($update);
        }
      }
      // handle delete
      $delete = $changesSinceLsatCheck->getChanges()->getDelete();
      if (!empty($delete)) {
        if (!is_array($delete)) {
          $delete = array($delete);
        }
      }
    } catch (\Exception $e) {
      echo $this::$TAG, $e->getMessage();
      echo $this::$TAG, $e->getTraceAsString();
    }
    $this->updateSyncState($changesSinceLsatCheck);

    return $this->fmtChangeList;
  }

//  function declineMeeting(\garethp\ews\CalendarAPI $calendar, $id, $calendarItemList): void
//  {
//    $itemId = findItemIdById($id, $calendarItemList);
//    try {
//      $calendar->declineMeeting($itemId, "此会议有冲突");
//    } catch (Exception $e) {
//    }
//    echo "declineMeeting: ", "$id", PHP_EOL;
//    try {
//      $calendar->deleteCalendarItem($itemId);
//    } catch (Exception $e) {
//    }
//  }
}
