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
use MRBS\RepeatRule;
use function MRBS\_tbl;
use function MRBS\get_vocab;
use function MRBS\mrbsCreateRepeatingEntrys;

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


  public function __construct($server, $timezone, $room)
  {
    $this->server = $server;
    $this->account = $room["exchange_username"];
    $this->password = $room["exchange_password"];
    $this->timezone = $timezone;
    $this->room = $room;
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
    \MRBS\log_i($this::$TAG, "getCalendarItems: $searchCalendarStart ~ $searchCalendarEnd");

    $calendar = $this->getCalendar();
    $items = $calendar->getCalendarItems($searchCalendarStart, $searchCalendarEnd);
    $calendarItemList = $items->getItems()->getCalendarItem();
    // get recent change list
    $changesSinceLsatCheck = $calendar->listChanges($this->room["exchange_sync_state"] ?? null);
    if (!empty($calendarItemList)) {
      if (!is_array($calendarItemList)) {
        $calendarItemList = array($calendarItemList);
      }
//      \MRBS\log_i($this::$TAG, "-----------------------------");
//      \MRBS\log_i($this::$TAG, "| print queried calendar");
//      \MRBS\log_i($this::$TAG, "-----------------------------");
//      foreach ($calendarItemList as $ci) {
//        $this->printCalenderItem($ci);
//      }
    }

    return $this->handleChangeList($changesSinceLsatCheck);
  }

  private function printCalenderItem(API\Type\CalendarItemType $ci)
  {
    \MRBS\log_i($this::$TAG, "-----------------------------");
    \MRBS\log_i($this::$TAG, "itemId:", $ci->getItemId()->getId());
    \MRBS\log_i($this::$TAG, "organizer:", $ci->getOrganizer()->getMailbox()->getName());
    \MRBS\log_i($this::$TAG, "start:", $this->formatIOSTime($ci->getStart()));
    \MRBS\log_i($this::$TAG, "end:", $this->formatIOSTime($ci->getEnd()));
    \MRBS\log_i($this::$TAG, "myResponseType:", $ci->getMyResponseType());  // values: Tentative/Accept/Decline
//    \MRBS\log_write($this::$TAG, "dateTimestamp:", $this->formatIOSTime($ci->getDateTimeStamp()));
//    \MRBS\log_write($this::$TAG, "lastModifiedTime:", $this->formatIOSTime($ci->getLastModifiedTime()));
//    \MRBS\log_write($this::$TAG, "location:", $ci->getLocation());
//    \MRBS\log_write($this::$TAG, "isMeeting:", $ci->isMeeting());
//    \MRBS\log_write($this::$TAG, "isCancelled:", $ci->isCancelled());
//    \MRBS\log_write($this::$TAG, "isRecurring:", $ci->isRecurring());
    \MRBS\log_i($this::$TAG, "-----------------------------");
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
    \MRBS\log_i($this::$TAG, "new syncState = $syncState");
    DBHelper::update(_tbl("room"), array("exchange_sync_state" => $syncState), array("id" => $this->room["id"]));
  }

  private function handleChangeList(SyncFolderItemsResponseMessageType $changesSinceLsatCheck)
  {
    if (empty($changesSinceLsatCheck->getChanges())) {
      \MRBS\log_i($this::$TAG, "empty change since last check, skip all");
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
          $entry = DBHelper::one(\MRBS\_tbl("entry"), "exchange_id = '$di' order by timestamp desc");
          $this->fmtChangeList["delete"][] = array("data" => $entry, "from" => "exchange");
          if (!empty($entry)) {
            DBHelper::delete(\MRBS\_tbl("entry"), array("exchange_id" => $di));
            DBHelper::delete(\MRBS\_tbl("repeat"), array("exchange_id" => $di));
          }
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
          $ci = $this -> getCalendar() -> getCalendarItem($ci->getItemId()->getId(), $ci->getItemId()->getChangeKey());
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
      \MRBS\log_i($this::$TAG, $e->getMessage());
      \MRBS\log_i($this::$TAG, $e->getTraceAsString());
    }
    $this->updateSyncState($changesSinceLsatCheck);

    return $this->fmtChangeList;
  }

  private function handleMeetingCreate(CalendarItemType $ci)
  {
    if ($ci->getMyResponseType() != "Tentative" && $ci->getMyResponseType() != "NoResponseReceived") {
      return;
    }
    if ($ci->getCalendarItemType() == "RecurringMaster") {
      $recurrence = $ci -> getRecurrence();
//      try {
//        $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
//      } catch (\Exception $e) {
//        \MRBS\log_i($this::$TAG, $e->getMessage());
//        \MRBS\log_i($this::$TAG, $e->getTraceAsString());
//      }
//      return;
      if ($recurrence->getWeeklyRecurrence() == null || $recurrence->getEndDateRecurrence() == null) {
        \MRBS\log_i($this::$TAG, "not support calendar type: RecurringMaster");
        try {
          $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
        } catch (\Exception $e) {
          \MRBS\log_i($this::$TAG, $e->getMessage());
          \MRBS\log_i($this::$TAG, $e->getTraceAsString());
        }
        return;
      } else {
        //TODO Weekly Recurrence
        $end_date_recurrence = $recurrence -> getEndDateRecurrence();
        $weekly_recurrence = $recurrence -> getWeeklyRecurrence();
        $days_of_week = $weekly_recurrence -> getDaysOfWeek();
        if ($end_date_recurrence !== null) {
          $start_time = new DateTime($end_date_recurrence->getStartDate());
          $end_time = $end_date_recurrence->getEndDate();
          switch (intval(date("w", $start_time -> getTimestamp()))) {
            case 0:
              if (!strpos($days_of_week, "Sunday")) {
                \MRBS\log_i($this::$TAG, "today of week should be include");
                try {
                  $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
                } catch (\Exception $e) {
                  \MRBS\log_i($this::$TAG, $e->getMessage());
                  \MRBS\log_i($this::$TAG, $e->getTraceAsString());
                }
                return;
              }
              break;
            case 1:
              if (!strpos($days_of_week, "Monday")) {
                \MRBS\log_i($this::$TAG, "today of week should be include");
                try {
                  $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
                } catch (\Exception $e) {
                  \MRBS\log_i($this::$TAG, $e->getMessage());
                  \MRBS\log_i($this::$TAG, $e->getTraceAsString());
                }
                return;
              }
              break;
            case 2:
              if (!strpos($days_of_week, "Tuesday")) {
                \MRBS\log_i($this::$TAG, "today of week should be include");
                try {
                  $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
                } catch (\Exception $e) {
                  \MRBS\log_i($this::$TAG, $e->getMessage());
                  \MRBS\log_i($this::$TAG, $e->getTraceAsString());
                }
                return;
              }
              break;
            case 3:
              if (!strpos($days_of_week, "Wednesday")) {
                \MRBS\log_i($this::$TAG, "today of week should be include");
                try {
                  $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
                } catch (\Exception $e) {
                  \MRBS\log_i($this::$TAG, $e->getMessage());
                  \MRBS\log_i($this::$TAG, $e->getTraceAsString());
                }
                return;
              }
              break;
            case 4:
              if (!strpos($days_of_week, "Thursday")) {
                \MRBS\log_i($this::$TAG, "today of week should be include");
                try {
                  $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
                } catch (\Exception $e) {
                  \MRBS\log_i($this::$TAG, $e->getMessage());
                  \MRBS\log_i($this::$TAG, $e->getTraceAsString());
                }
                return;
              }
              break;
            case 5:
              if (!strpos($days_of_week, "Friday")) {
                \MRBS\log_i($this::$TAG, "today of week should be include");
                try {
                  $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
                } catch (\Exception $e) {
                  \MRBS\log_i($this::$TAG, $e->getMessage());
                  \MRBS\log_i($this::$TAG, $e->getTraceAsString());
                }
                return;
              }
              break;
            case 6:
              if (!strpos($days_of_week, "Saturday")) {
                \MRBS\log_i($this::$TAG, "today of week should be include");
                try {
                  $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
                } catch (\Exception $e) {
                  \MRBS\log_i($this::$TAG, $e->getMessage());
                  \MRBS\log_i($this::$TAG, $e->getTraceAsString());
                }
                return;
              }
              break;
            default:
              \MRBS\log_i($this::$TAG, "today of week should be include");
              try {
                $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
              } catch (\Exception $e) {
                \MRBS\log_i($this::$TAG, $e->getMessage());
                \MRBS\log_i($this::$TAG, $e->getTraceAsString());
              }
              return;
          }
          $rep_rule = new \MRBS\RepeatRule();
          $rep_rule->setType(RepeatRule::WEEKLY);
          $e_days_of_week = $days_of_week;
          $days_of_week = array();
          if (strpos($e_days_of_week, "Sunday") !== false){
            $days_of_week[] = 0;
          }
          if (strpos($e_days_of_week, "Monday") !== false){
            $days_of_week[] = 1;
          }
          if (strpos($e_days_of_week, "Tuesday") !== false){
            $days_of_week[] = 2;
          }
          if (strpos($e_days_of_week, "Wednesday") !== false){
            $days_of_week[] = 3;
          }
          if (strpos($e_days_of_week, "Thursday") !== false){
            $days_of_week[] = 4;
          }
          if (strpos($e_days_of_week, "Friday") !== false){
            $days_of_week[] = 5;
          }
          if (strpos($e_days_of_week, "Saturday") !== false){
            $days_of_week[] = 6;
          }
          if (count($days_of_week) == 0){
            \MRBS\log_i($this::$TAG, "day of week should not be empty");
            try {
              $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
            } catch (\Exception $e) {
              \MRBS\log_i($this::$TAG, $e->getMessage());
              \MRBS\log_i($this::$TAG, $e->getTraceAsString());
            }
            return;
          }
          $rep_rule -> setDays($days_of_week);
          $rep_rule -> setEndDate(new \MRBS\DateTime($end_time));
          $rep_rule -> setInterval($weekly_recurrence->getInterval());
          $adapter = new CalendarAdapter(CalendarAdapter::$MODE_ADD);
          $this -> fmtChangeList['create'][] = array(
            "from" => "exchange",
            "data" => $adapter->exchangeCalendarToRecurringEntry($ci, $rep_rule, $this -> room),
            "item" => $ci
          );
          return;
        } else {
          \MRBS\log_i($this::$TAG, "temporarily not support NumberedRecurrence");
          try {
            $this->getCalendar()->declineMeeting($ci->getItemId(), get_vocab("ic_recurring_decline"));
          } catch (\Exception $e) {
            \MRBS\log_i($this::$TAG, $e->getMessage());
            \MRBS\log_i($this::$TAG, $e->getTraceAsString());
          }
          return;
        }
      }
    }
    $exchangeId = $ci->getItemId()->getId();
    // itemId maybe reused, so don't check itemId
//    $queryOne = DBHelper::one(_tbl("entry"), "exchange_id = '$exchangeId'");
//    if (!empty($queryOne)) {
//      \MRBS\log_i($this::$TAG, "duplicate exchange_id: $exchangeId");
//      return;
//    }

    // determine if there are conflicting meetings
    $roomId = $this->room["id"];
    $start = new DateTime($ci->getStart());
    $start->setTimezone(new DateTimeZone($this->timezone));
    $end = new DateTime($ci->getEnd());
    $end->setTimezone(new DateTimeZone($this->timezone));
    $startTime = $start->getTimestamp();
    $endTime = $end->getTimestamp();
    $qSQL = "room_id = $roomId and
    (($startTime >= start_time and $startTime < end_time)
    or ($endTime > start_time and $endTime <= end_time)
    or ($startTime <= start_time and $endTime >= end_time))
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
        \MRBS\log_i($this::$TAG, $e->getMessage());
        \MRBS\log_i($this::$TAG, $e->getTraceAsString());
      }
      $conflictId = $queryOne["id"];
      \MRBS\log_i($this::$TAG, "conflict meeting: meeting request($startTime - $endTime) is conflict with $conflictId");

      return;
    }

    $adapter = new CalendarAdapter(CalendarAdapter::$MODE_ADD);
    $this->fmtChangeList["create"][] = array(
      "from" => "exchange",
      "data" => $adapter->exchangeCalendarToEntry($ci, $this->room),
      "item" => $ci
    );
//    try {
//      echo $ci->getItemId()->getId() . "\n";
//      $this->getCalendar()->acceptMeeting($ci->getItemId(), "");
//      echo 1;
//      $this->getCalendar()->declineMeeting($ci->getItemId(), "test");
//    } catch (\Exception $e) {
//      \MRBS\log_write($this::$TAG, $e->getMessage();
//      \MRBS\log_write($this::$TAG, $e->getTraceAsString();
//      $this->getCalendar()->declineMeeting($ci->getItemId(), "test");
//      echo $e->getMessage();
//      echo $e->getTraceAsString();
//    }
  }

  private function handleMeetingUpdate(CalendarItemType $ui)
  {
    // After the meeting is updated, it will revert back to the Tentative state
    if ($ui->getMyResponseType() != "Tentative" && $ui->getMyResponseType() != "NoResponseReceived") {
      return;
    }
    if ($ui->getCalendarItemType() == "RecurringMaster") {
      try {
        $this->getCalendar()->declineMeeting($ui->getItemId(), get_vocab("ic_recurring_decline"));
      } catch (\Exception $e) {
        \MRBS\log_i($this::$TAG, $e->getMessage());
        \MRBS\log_i($this::$TAG, $e->getTraceAsString());
      }
      return;
    }
    $exchangeId = $ui->getItemId()->getId();
    $queryOne = DBHelper::one(_tbl("entry"), "exchange_id = '$exchangeId' order by timestamp desc ");
    if (empty($queryOne)) {
      return;
    }

    $adapter = new CalendarAdapter(CalendarAdapter::$MODE_UPDATE);
    $this->fmtChangeList["update"][] = array(
      "from" => "exchange",
      "data" => $adapter->exchangeCalendarToEntry($ui, $this->room, $queryOne)
    );

    try {
      $this->getCalendar()->acceptMeeting($ui->getItemId(), "");
    } catch (\Exception $e) {
//      \MRBS\log_write($this::$TAG, $e->getMessage();
//      \MRBS\log_write($this::$TAG, $e->getTraceAsString();
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
    \MRBS\log_i($this::$TAG, "createMeeting: $id");
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
    \MRBS\log_i($this::$TAG, "deleteMeeting: $id");
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

  public function declineMeeting(CalendarItemType $i, string $msg)
  {
    try {
      $this->getCalendar()->declineMeeting($i->getItemId(), $msg);
    } catch (\Exception $e) {
//      echo $e->getMessage();
//      echo $e->getTraceAsString();
    }
  }

  public function acceptMeeting(CalendarItemType $i, string $msg)
  {
    try {
      $this->getCalendar()->acceptMeeting($i->getItemId(), $msg);
    } catch (\Exception $e) {
//      echo $e->getMessage();
//      echo $e->getTraceAsString();
    }
  }

  public function createRepeatMeeting($entry, $end_date){
    $id = $entry["id"];
    $adapter = new CalendarAdapter(CalendarAdapter::$MODE_ADD);
    $exchangeCalendar = $adapter->entryToExchangeCalendarRepeat($entry, $end_date);
    try {
      $createdItemIds = $this->getCalendar()->createCalendarItems($exchangeCalendar);
      if ($createdItemIds) {
        $exchange_id = $createdItemIds[0]->getId();
        $exchange_key = $createdItemIds[0]->getChangeKey();
        DBHelper::update(_tbl("repeat"), array("exchange_id" => $exchange_id, "exchange_key" => $exchange_key), "id = $id");
      }
    } catch (\Exception $e) {

    }
    \MRBS\log_i($this::$TAG, "createRepeatingMeeting: $id");
  }
}


