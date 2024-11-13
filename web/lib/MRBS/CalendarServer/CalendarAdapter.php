<?php

namespace MRBS\CalendarServer;

use Cassandra\Date;
use DateTime;
use garethp\ews\API\Type\CalendarItemType;
use garethp\ews\API\Type\EndDateRecurrenceRangeType;
use garethp\ews\API\Type\RecurrenceType;
use garethp\ews\API\Type\WeeklyRecurrencePatternType;
use MRBS\RepeatRule;
use function MRBS\_tbl;
use function MRBS\generate_global_uid;
use function MRBS\get_vocab;
use MRBS\DBHelper;

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
    $repeat_rule->setType(RepeatRule::NONE);
    if ($this->mode == $this::$MODE_UPDATE) {
      $result["modified_by"] = "admin";
    }
    if ($this->mode == $this::$MODE_ADD) {
      $email = $calendarItem->getOrganizer()->getMailbox()->getEmailAddress();
      $create_by = DBHelper::one(_tbl("users"), ["email" => $email]);
      $result["create_by"] = $create_by ?? "exchange";
      if (trim($calendarItem->getOrganizer()->getMailbox()->getName()) == trim($calendarItem->getSubject())) {
        $result["name"] = $calendarItem->getSubject() ? get_vocab("ic_xs_meeting", $calendarItem->getSubject()) : "Unknown Meeting";
      } else {
        $result["name"] = $calendarItem->getSubject() ?? "Unknown Meeting";
      }
      $result["description"] = "";
      $result["book_by"] = $calendarItem->getOrganizer()->getMailbox()->getName() ?? "Unknown";
      $result["type"] = "I";
      $result["status"] = 0;
      $result["ical_uid"] = generate_global_uid($result["name"]);
      $result["allow_registration"] = $allow_registration_default ? 1 : 0;
      $result["registrant_limit"] = $registrant_limit_default;
      $result["registrant_limit_enabled"] = $registrant_limit_enabled_default ? 1 : 0;
      $result["registration_opens"] = $registration_opens_default;
      $result["registration_opens_enabled"] = $registration_opens_enabled_default ? 1 : 0;
      $result["registration_closes"] = $registration_closes_default;
      $result["registration_closes_enabled"] = $registration_closes_enabled_default ? 1 : 0;
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

  public function wxworkBookToCalendar($book, $room, $oldData = null): array
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
      $result["registrant_limit_enabled"] = $registrant_limit_enabled_default ? 1 : 0;
      $result["registration_opens"] = $registration_opens_default;
      $result["registration_opens_enabled"] = $registration_opens_enabled_default ? 1 : 0;
      $result["registration_closes"] = $registration_closes_default;
      $result["registration_closes_enabled"] = $registration_closes_enabled_default ? 1 : 0;
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

  public function exchangeCalendarToRecurringEntry(CalendarItemType $calendarItem, RepeatRule $rep_rule, $room, $oldData = null): array
  {
    global $allow_registration_default, $registrant_limit_default, $registrant_limit_enabled_default;
    global $registration_opens_default, $registration_opens_enabled_default, $registration_closes_default;
    global $registration_closes_enabled_default;

    $result = array();
    $result["start_time"] = $this->iOSTimeToTimeStamp($calendarItem->getFirstOccurrence()->getStart());
    $result["end_time"] = $this->iOSTimeToTimeStamp($calendarItem->getFirstOccurrence()->getEnd());
    $result["entry_type"] = 0;
    $result["room_id"] = $room["id"];
//    $result["skip"] = false;
//    $result["edit_series"] = false;
    $repeat_rule = $rep_rule;
    if ($this->mode == $this::$MODE_UPDATE) {
      $result["modified_by"] = "admin";
    }
    if ($this->mode == $this::$MODE_ADD) {
      $email = $calendarItem->getOrganizer()->getMailbox()->getEmailAddress();
      $create_by = DBHelper::one(_tbl("users"), ["email" => $email]);
      $result["create_by"] = $create_by ? $create_by['name'] : "exchange";
      $result["name"] = $calendarItem->getSubject() ? get_vocab("ic_xs_meeting", $calendarItem->getSubject()) : "Unknown Meeting";
      $result["description"] = "";
      $result["book_by"] = $calendarItem->getOrganizer()->getMailbox()->getName() ?? "Unknown";
      $result["type"] = "I";
      $result["status"] = 0;
      $result["ical_uid"] = generate_global_uid($result["name"]);
      $result["allow_registration"] = $allow_registration_default ? 1 : 0;
      $result["registrant_limit"] = $registrant_limit_default;
      $result["registrant_limit_enabled"] = $registrant_limit_enabled_default ? 1 : 0;
      $result["registration_opens"] = $registration_opens_default;
      $result["registration_opens_enabled"] = $registration_opens_enabled_default ? 1 : 0;
      $result["registration_closes"] = $registration_closes_default;
      $result["registration_closes_enabled"] = $registration_closes_enabled_default ? 1 : 0;
      $result["exchange_id"] = $calendarItem->getItemId()->getId();
      $result["exchange_key"] = $calendarItem->getItemId()->getChangeKey();
      $result["create_source"] = "exchange";
      $result["repeat_rule"] = $repeat_rule;
      $result["duration"] = ($this->iOSTimeToTimeStamp($calendarItem->getEnd()) - $this->iOSTimeToTimeStamp($calendarItem->getStart())) / 60;
      $result["dur_units"] = "minutes";
      $result["private"] = false;
      $result["awaiting_approval"] = false;
      $result["tentative"] = true;
      $result["ical_sequence"] = 1;
      $result["ical_recur_id"] = "";
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

  public function entryToExchangeCalendarRepeat($entry, $end_date)
  {
    $start = new DateTime();
    $start->setTimestamp($entry['start_time']);
    $end = new DateTime();
    $end->setTimestamp($entry['end_time']);

    for ($i = 0; $i < strlen($entry['rep_opt']); $i++) {
      if ($entry['rep_opt'][$i] == '1') {
        switch ($i) {
          case 0:
            $days[] = "Sunday";
            break;
          case 1:
            $days[] = "Monday";
            break;
          case 2:
            $days[] = "Tuesday";
            break;
          case 3:
            $days[] = "Wednesday";
            break;
          case 4:
            $days[] = "Thursday";
            break;
          case 5:
            $days[] = "Friday";
            break;
          case 6:
            $days[] = "Saturday";
            break;
        }
      }
    }

    $result = array();
    $start = new DateTime();
    $end = new DateTime();
    $start->setTimestamp($entry["start_time"]);
    $end->setTimestamp($entry["end_time"]);

    $result["Subject"] = $entry["name"];
    $result["Start"] = $start->format('c');
    $result["End"] = $end->format('c');
    if ($this->mode == $this::$MODE_ADD) {
      $result["StartDate"] = $start->format('Y-m-d');
    }
    $result["Recurrence"] = array(
      "WeeklyRecurrence" => array(
        "DaysOfWeek" => $days,
        "Interval" => $entry['rep_interval']
      ),
      "EndDateRecurrence" => array(
        "StartDate" => $start->format('Y-m-d'),
        "EndDate" => $end_date
      )
    );

    return $result;
  }
}
