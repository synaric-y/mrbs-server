<?php

namespace MRBS\CalendarServer;

use DateInterval;
use DateTime;
use DateTimeZone;
use garethp\ews\API;
use garethp\ews\API\Message\SyncFolderItemsResponseMessageType;
use garethp\ews\API\Type\CalendarItemType;
use MRBS\DBHelper;
use MRBS\Intl\IntlDateFormatter;
use function MRBS\_tbl;
use function MRBS\get_vocab;

// A Connector that synchronizes bidirectionally with MicroSoft Exchange.
class ExchangeCalendarServerConnector implements AbstractCalendarServerConnector
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

  private function getCalendar()
  {
    if (empty($this->api)) {
      $this->api = API::withUsernameAndPassword($this->server, $this->account, $this->password);
    }
    return $this->api->getCalendar();
  }

  public function pullCalendarUpdate()
  {
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
    if (!empty($calendarItemList)) {
      if (!is_array($calendarItemList)) {
        $calendarItemList = array($calendarItemList);
      }
      echo $this::$TAG, "-----------------------------", PHP_EOL;
      echo $this::$TAG, "| print queried calendar", PHP_EOL;
      echo $this::$TAG, "-----------------------------", PHP_EOL;
      foreach ($calendarItemList as $ci) {
        $this->printCalenderItem($ci);
      }
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
    $syncState = $changesSinceLsatCheck->getSyncState();
    echo $this::$TAG, "new syncState = $syncState", PHP_EOL;
    DBHelper::update(_tbl("room"), array("exchange_sync_state" => $syncState), array("id" => $this->room["id"]));
  }

  private function handleChangeList(SyncFolderItemsResponseMessageType $changesSinceLsatCheck)
  {
    if (empty($changesSinceLsatCheck->getChanges())) {

      $this->updateSyncState($changesSinceLsatCheck);
      return null;
    }
    try {
      // handle delete
      $delete = $changesSinceLsatCheck->getChanges()->getDelete();
      if (!empty($delete)) {
        if (!is_array($delete)) {
          $delete = array($delete);
        }
        foreach ($delete as $deleteItem) {
          $di = $deleteItem->getItemId()->getId();
          $this->fmtChangeList["delete"][] = array("exchange_id" => $di);
          DBHelper::delete(\MRBS\_tbl("entry"), array("exchange_id" => $di));
        }
      }
      // handle create
      $create = $changesSinceLsatCheck->getChanges()->getCreate();
      if (!empty($create)) {
        if (!is_array($create)) {
          $create = array($create);
        }
        foreach ($create as $createItem) {
          $ci = $createItem->getCalendarItem();
          $this->handleMeetingCreate($ci);
        }
      }
      // handle update
      $update = $changesSinceLsatCheck->getChanges()->getUpdate();
      if (!empty($update)) {
        if (!is_array($update)) {
          $update = array($update);
        }
        foreach ($update as $updateItem) {
          $ui = $updateItem->getCalendarItem();
          $this->handleMeetingUpdate($ui);
        }
      }
    } catch (\Exception $e) {
      echo $this::$TAG, $e->getMessage();
      echo $this::$TAG, $e->getTraceAsString();
    }
    $this->updateSyncState($changesSinceLsatCheck);

    return $this->fmtChangeList;
  }

  private function handleMeetingCreate(CalendarItemType $ci)
  {
    if ($ci->getMyResponseType() != "Tentative") {
      return;
    }
    $exchangeId = $ci->getItemId()->getId();
    $queryOne = DBHelper::one(_tbl("entry"), "exchange_id = '$exchangeId'");
    if (!empty($queryOne)) {
      return;
    }

    // determine if there are conflicting meetings
    $roomId = $this->room["id"];
    $start = new DateTime($ci->getStart());
    $start->setTimezone(new DateTimeZone($this->timezone));
    $end = new DateTime($ci->getEnd());
    $end->setTimezone(new DateTimeZone($this->timezone));
    $startTime = $start->getTimestamp();
    $endTime = $end->getTimestamp();
    $qSQL = "room_id = $roomId and
    ($startTime >= start_time and $startTime < end_time)
    or ($endTime > start_time and $endTime <= end_time)
    or ($startTime <= start_time and $endTime >= end_time)
    ";

    $queryOne = DBHelper::one(_tbl("entry"), $qSQL);
    if (!empty($queryOne)) {
      $start->setTimestamp($queryOne["start_time"]);
      $end->setTimestamp($queryOne["end_time"]);
      $startText = $start->format("Y-m-d H:i");
      $endText = $end->format("Y-m-d H:i");
      $conflictDetail = get_vocab("ic_meeting_decline_conflict", "$startText - $endText");
      $declineReason = get_vocab("ic_meeting_decline", $conflictDetail);
      try {
        $this->getCalendar()->declineMeeting($ci->getItemId(), $declineReason);
      } catch (\Exception $e) {
        echo $this::$TAG, $e->getMessage();
        echo $this::$TAG, $e->getTraceAsString();
      }
      $conflictId = $queryOne["id"];
      echo $this::$TAG, "conflict meeting: meeting request($startTime - $endTime) is conflict with $conflictId";

      return;
    }

    $adapter = new CalendarAdapter(CalendarAdapter::$MODE_ADD);
    $this->fmtChangeList["create"][] = $adapter->exchangeCalendarToEntry($ci, $this->room);
    try {
      $this->getCalendar()->acceptMeeting($ci->getItemId(), "");
    } catch (\Exception $e) {
//      echo $this::$TAG, $e->getMessage();
//      echo $this::$TAG, $e->getTraceAsString();
    }
  }

  private function handleMeetingUpdate(CalendarItemType $ui)
  {
    // After the meeting is updated, it will revert back to the Tentative state
    if ($ui->getMyResponseType() != "Tentative") {
      return;
    }
    $exchangeId = $ui->getItemId()->getId();
    $queryOne = DBHelper::one(_tbl("entry"), "exchange_id = '$exchangeId'");
    if (empty($queryOne)) {
      return;
    }

    $adapter = new CalendarAdapter(CalendarAdapter::$MODE_UPDATE);
    $this->fmtChangeList["update"][] = $adapter->exchangeCalendarToEntry($ui, $this->room, $queryOne);

    try {
      $this->getCalendar()->acceptMeeting($ui->getItemId(), "");
    } catch (\Exception $e) {
//      echo $this::$TAG, $e->getMessage();
//      echo $this::$TAG, $e->getTraceAsString();
    }
  }

  function createMeeting($entry)
  {
    $id = $entry["id"];
    $adapter = new CalendarAdapter(CalendarAdapter::$MODE_ADD);
    $exchangeCalendar = $adapter->entryToExchangeCalendar($entry);
    try {
      $createdItemIds = $this->getCalendar()->createCalendarItems($exchangeCalendar);

      if ($createdItemIds) {
        $exchange_id = $createdItemIds[0]->getId();
        $exchange_key = $createdItemIds[0]->getChangeKey();
        DBHelper::update(_tbl("entry"), array("exchange_id" => $exchange_id, "exchange_key" => $exchange_key), "id = $id");
      }
    } catch (\Exception $e) {

    }
    echo $this::$TAG, "createMeeting: $id", PHP_EOL;
  }

  function deleteMeeting($entry)
  {
    if (empty($entry["exchange_id"]) || empty($entry["exchange_key"])) {
      return;
    }
    $id = $entry["id"];
    $itemId = new API\Type\ItemIdType();
    $itemId->setId($entry["exchange_id"]);
    $itemId->setChangeKey($entry["exchange_key"]);
    try {
      $this->getCalendar()->deleteCalendarItem($itemId);
    } catch (\Exception $e) {

    }
    echo $this::$TAG, "deleteMeeting: $id", PHP_EOL;
  }

  function updateMeeting($entry)
  {
    if (empty($entry["exchange_id"]) || empty($entry["exchange_key"])) {
      return;
    }
    $id = $entry["id"];
    $itemId = new API\Type\ItemIdType();
    $itemId->setId($entry["exchange_id"]);
    $itemId->setChangeKey($entry["exchange_key"]);

    $adapter = new CalendarAdapter(CalendarAdapter::$MODE_UPDATE);
    $exchangeCalendar = $adapter->entryToExchangeCalendar($entry);
    try {
      $updateItems = $this->getCalendar()->updateCalendarItem($itemId, $exchangeCalendar);
      $newItemId = $updateItems[0]->getItemId();
      $exchange_id = $newItemId->getId();
      $exchange_key = $newItemId->getChangeKey();
      DBHelper::update(_tbl("entry"), array("exchange_id" => $exchange_id, "exchange_key" => $exchange_key), "id = $id");
    } catch (\Exception $e) {

    }
  }
}
