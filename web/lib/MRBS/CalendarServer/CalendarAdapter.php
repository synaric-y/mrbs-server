<?php
namespace MRBS\CalendarServer;

use DateTime;
use garethp\ews\API\Type\CalendarItemType;
use MRBS\RepeatRule;
use function MRBS\_tbl;
use function MRBS\generate_global_uid;
use function MRBS\get_vocab;

class CalendarAdapter
{

  public static $MODE_ADD = 0;
  public static $MODE_UPDATE = 1;

  private $mode;

  public function __construct($mode)
  {
    $this->mode = $mode;
  }

  // Convert Exchange Calendar to Entry
  public function exchangeCalendarToEntry(CalendarItemType $calendarItem, $room, $oldData = null): array
  {
    global $allow_registration_default, $registrant_limit_default, $registrant_limit_enabled_default;
    global $registration_opens_default, $registration_opens_enabled_default, $registration_closes_default;
    global $registration_closes_enabled_default;

    $result = array();
    $result["start_time"] = $this->iOSTimeToTimeStamp($calendarItem->getStart());
    $result["end_time"] = $this->iOSTimeToTimeStamp($calendarItem->getEnd());
    $result["entry_type"] = 0;
    $result["room_id"] = $room["id"];
//    $result["skip"] = false;
//    $result["edit_series"] = false;
    $repeat_rule = new RepeatRule();
    $repeat_rule -> setType(RepeatRule::NONE);
    if ($this->mode == $this::$MODE_UPDATE) {
      $result["modified_by"] = "admin";
    }
    if ($this->mode == $this::$MODE_ADD) {
      $result["create_by"] = "admin";
      $result["name"] = $calendarItem->getSubject() ? get_vocab("ic_xs_meeting", $calendarItem->getSubject()) : "Unknown Meeting";
      $result["description"] = "";
      $result["book_by"] = $calendarItem->getOrganizer()->getMailbox()->getName() ?? "Unknown";
      $result["type"] = "I";
      $result["status"] = 0;
      $result["ical_uid"] = generate_global_uid($result["name"]);
      $result["allow_registration"] = $allow_registration_default ? 1 : 0;
      $result["registrant_limit"] = $registrant_limit_default;
      $result["registrant_limit_enabled"] = $registrant_limit_enabled_default  ? 1 : 0;
      $result["registration_opens"] = $registration_opens_default;
      $result["registration_opens_enabled"] = $registration_opens_enabled_default  ? 1 : 0;
      $result["registration_closes"] = $registration_closes_default;
      $result["registration_closes_enabled"] = $registration_closes_enabled_default  ? 1 : 0;
      $result["exchange_id"] = $calendarItem->getItemId()->getId();
      $result["exchange_key"] = $calendarItem->getItemId()->getChangeKey();
      $result["create_source"] = "exchange";
    } elseif ($this->mode == $this::$MODE_UPDATE) {
      $result["id"] = $oldData["id"];
      $result["ical_sequence"] = $oldData["ical_sequence"] + 1;
    }
//    $result["confirm"] = "";
//    $result["private"] = false;
//    $result["awaiting_approval"] = false;
//    $result["tentative"] = false;
    return $result;
  }

  // Convert Entry to Exchange Calendar
  public function entryToExchangeCalendar($entry)
  {
      $result = array();
      $start = new DateTime();
      $end = new DateTime();
      $start->setTimestamp($entry["start_time"]);
      $end->setTimestamp($entry["end_time"]);

      $result["Subject"] = $entry["name"];
      $result["Start"] = $start->format('c');
      $result["End"] = $end->format('c');

      return $result;
  }

  public function wxworkBookToCalendar($book, $room, $oldData = null) : array
  {
    global $allow_registration_default, $registrant_limit_default, $registrant_limit_enabled_default;
    global $registration_opens_default, $registration_opens_enabled_default, $registration_closes_default;
    global $registration_closes_enabled_default;

    $book["booker"] = empty($book["booker"]) ? "Unknown" : str_replace("@bcc.global", "", $book["booker"]);

    $result = array();
    $result["start_time"] = $book["start_time"];
    $result["end_time"] = $book["end_time"];
    $result["entry_type"] = 0;
    $result["room_id"] = $room["id"];
    if ($this->mode == $this::$MODE_UPDATE) {
      $result["modified_by"] = "admin";
    }
    if ($this->mode == $this::$MODE_ADD) {
      $result["create_by"] = "admin";
      $result["name"] = get_vocab("ic_xs_meeting", $book["booker"]);
      $result["description"] = "";
      $result["book_by"] = $book["booker"];
      $result["type"] = "I";
      $result["status"] = 0;
      $result["ical_uid"] = generate_global_uid($result["name"]);
      $result["allow_registration"] = $allow_registration_default ? 1 : 0;
      $result["registrant_limit"] = $registrant_limit_default;
      $result["registrant_limit_enabled"] = $registrant_limit_enabled_default  ? 1 : 0;
      $result["registration_opens"] = $registration_opens_default;
      $result["registration_opens_enabled"] = $registration_opens_enabled_default  ? 1 : 0;
      $result["registration_closes"] = $registration_closes_default;
      $result["registration_closes_enabled"] = $registration_closes_enabled_default  ? 1 : 0;
      $result["wxwork_bid"] = $book["booking_id"];
      $result["wxwork_sid"] = $book["schedule_id"];
      $result["create_source"] = "wxwork";
    } elseif ($this->mode == $this::$MODE_UPDATE) {
      $result["id"] = $oldData["id"];
      $result["ical_sequence"] = $oldData["ical_sequence"] + 1;
    }
    return $result;
  }

  private function iOSTimeToTimeStamp($time)
  {
    $dateTime = new DateTime($time);
    return $dateTime->getTimestamp();
  }

  public function exchangeCalendarToRecurringEntry(CalendarItemType $calendarItem, $room, $oldData = null) : array{




    return array();
  }
}
