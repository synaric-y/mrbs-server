<?php
namespace MRBS\CalendarServer;

use DateTime;
use garethp\ews\API\Type\CalendarItemType;
use function MRBS\generate_global_uid;
use function MRBS\get_vocab;

class CalendarAdapter
{

  public static $MODE_ADD = 0;
  public static $MODE_UPDATE = 1;

  private $room;
  private $mode;

  public function __construct($room, $mode)
  {
    $this->room = $room;
    $this->mode = $mode;
  }

  public function exchangeCalendarToCalendar(CalendarItemType $calendarItem, $oldData): array
  {
    global $allow_registration_default, $registrant_limit_default, $registrant_limit_enabled_default;
    global $registration_opens_default, $registration_opens_enabled_default, $registration_closes_default;
    global $registration_closes_enabled_default;

    $result = array();
    $result["start_time"] = $this->iOSTimeToTimeStamp($calendarItem->getStart());
    $result["end_time"] = $this->iOSTimeToTimeStamp($calendarItem->getEnd());
    $result["entry_type"] = 0;
    $result["room_id"] = $this->room["id"];
    if ($this->mode == $this::$MODE_UPDATE) {
      $result["modified_by"] = "admin";
    }
    if ($this->mode == $this::$MODE_ADD) {
      $result["create_by"] = "admin";
      $result["name"] = $calendarItem->getSubject() ? get_vocab("ic_xs_meeting", $calendarItem->getSubject()) : "Unknown Meeting";
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
      $result["create_source"] = "exchange";
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
}
