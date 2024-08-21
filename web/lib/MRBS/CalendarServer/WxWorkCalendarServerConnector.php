<?php

namespace MRBS\CalendarServer;

use ApprovalDataList;
use MRBS\DBHelper;
use function MRBS\_tbl;
use function MRBS\log_i;

include_once(dirname(__DIR__, 2) . "/Wxwork/api/src/CorpAPI.class.php");
include_once(dirname(__DIR__, 2) . "/Wxwork/api/src/CorpAPI.class.php");

// A Connector that synchronizes bidirectionally with WxWork.
class  WxWorkCalendarServerConnector implements AbstractCalendarServerConnector
{

  static $TAG = "[WxWorkCalendarServerConnector] ";
  // 获取房间列表
  private static $MEETING_ROOM_LIST = "/cgi-bin/oa/meetingroom/list?access_token=ACCESS_TOKEN";
  // 查询会议室的预定信息
  private static $GET_BOOKING_INFO = "/cgi-bin/oa/meetingroom/get_booking_info?access_token=ACCESS_TOKEN";
  // 强制取消预定
  private static $CANCEL_BOOK = "/cgi-bin/oa/meetingroom/cancel_book?access_token=ACCESS_TOKEN";
  // 预约会议
  private static $BOOK = "/cgi-bin/oa/meetingroom/book?access_token=ACCESS_TOKEN";

  private static $API;
  private $room;
  private $timezone;
  private $fmtChangeList = array(
    "create" => array(),
    "update" => array(),
    "delete" => array(),
  );

  public function __construct($area, $room)
  {
    if (empty(WxWorkCalendarServerConnector::$API)) {
      WxWorkCalendarServerConnector::$API = new \CorpAPI(
        $area["wxwork_corpid"],
        $area["wxwork_secret"]
      );
    }
    $this->room = $room;
  }

  function pullCalendarUpdate()
  {
    $roomId = $this->room["id"];
//    WxWorkCalendarServerConnector::$API->_HttpCall($this::$BOOK, "POST", array(
//      "meetingroom_id" => 6,
//      "subject" => "周会",
//      "start_time" => 1723806000,
//      "end_time" => 1723809600,
//      "booker" => "synaric.yong@bcc.global"
//    ));
//    echo json_encode(WxWorkCalendarServerConnector::$API->rspJson), PHP_EOL;

    $meetingRoomId = $roomId["wxwork_mr_id"];
    WxWorkCalendarServerConnector::$API->_HttpCall($this::$GET_BOOKING_INFO, "POST", array(
      "meetingroom_id" => $meetingRoomId,
      "start_time" => strtotime("today midnight"),
      "end_time" => strtotime("tomorrow -1 second"),
    ));
    log_i($this::$TAG, "room $meetingRoomId result " . json_encode(WxWorkCalendarServerConnector::$API->rspJson));
    $roomBookingListResult = WxWorkCalendarServerConnector::$API->rspJson;
    if (empty($roomBookingListResult) || $roomBookingListResult["errcode"] != 0) {
      log_i($this::$TAG, $this::$GET_BOOKING_INFO . " errcode = " . $roomBookingListResult["errcode"]);
      return;
    }
    if (empty($roomBookingListResult["booking_list"])) {
      return;
    }
    $currentScheduleList = $roomBookingListResult["booking_list"][0]["schedule"];
    $lastScheduleList = [];
    if (!empty($this->room["wxwork_sync_state"])) {
      $syncState = DBHelper::one(_tbl("wxwork_sync"), "room_id = $roomId");
      if (!empty($syncState) && !empty($syncState["schedule_list"])) {
        $lastScheduleList = json_decode($syncState["schedule_list"], true);
      }
    }

    // diff $currentScheduleList and $lastScheduleList
    $diffResult = $this->diffArray($lastScheduleList, $currentScheduleList, "booking_id");
    $newBookIds = $diffResult[0];
    $delBookIds = $diffResult[1];

    // handle delete
    foreach ($lastScheduleList as $book) {
      if (in_array($book["booking_id"], $delBookIds)) {
        $this->handleMeetingDelete($book);
      }
    }

    // handle create
    foreach ($currentScheduleList as $book) {
      if (in_array($book["booking_id"], $newBookIds)) {
        $this->handleMeetingCreate($book);
      }
    }

    $this->updateSyncState($currentScheduleList);
  }

  private function handleMeetingCreate($book)
  {
    // determine if there are conflicting meetings
    $roomId = $this->room["id"];
    $startTime = $book["start_time"];
    $endTime = $book["end_time"];
    $qSQL = "room_id = $roomId and
    (($startTime >= start_time and $startTime < end_time)
    or ($endTime > start_time and $endTime <= end_time)
    or ($startTime <= start_time and $endTime >= end_time))
    ";

    $queryOne = DBHelper::one(_tbl("entry"), $qSQL);
    if (!empty($queryOne)) {
      WxWorkCalendarServerConnector::$API->_HttpCall($this::$CANCEL_BOOK, "POST", array(
        "meetingroom_id" => $book["meetingroom_id"],
      ));
      return;
    }
    $adapter = new CalendarAdapter(CalendarAdapter::$MODE_ADD);
    $this->fmtChangeList["create"][] = array(
      "from" => "wxwork",
      "data" => $adapter->wxworkBookToCalendar($book, $this->room)
    );
  }

  private function handleMeetingDelete($book)
  {
    $entry = DBHelper::one(\MRBS\_tbl("entry"), array("wxwork_bid" => $book["booking_id"]));
    $this->fmtChangeList["delete"][] = array("data" => $entry, "from" => "wxwork");
    if (!empty($entry)) {
      DBHelper::delete(\MRBS\_tbl("entry"), array("wxwork_bid" => $book["booking_id"]));
    }
  }

  private function updateSyncState($currentScheduleList)
  {
    $roomId = $this->room["id"];
    $scheduleList = json_encode($currentScheduleList);
    $syncTime = time();
    $sql = "INSERT INTO " . _tbl("wxwork_sync") . "(room_id, schedule_list, sync_time)
          VALUES ($roomId, '$scheduleList', $syncTime)
          ON DUPLICATE KEY UPDATE schedule_list = $scheduleList, sync_time = $syncTime
    ";
    DBHelper::exec($sql);
    DBHelper::update(_tbl("room"), array("wxwork_sync_state" => $syncTime), array("id" => $this->room["id"]));
  }

  function createMeeting($entry)
  {
    // TODO: Implement createMeeting() method.
  }

  function deleteMeeting($entry)
  {
    // TODO: Implement deleteMeeting() method.
  }

  function updateMeeting($entry)
  {
    // TODO: Implement updateMeeting() method.
  }

  private function diffArray($array1, $array2, $key): array
  {
    $newItems = [];
    $deletedItems = [];

    $ids1 = array_column($array1, $key);
    $ids2 = array_column($array2, $key);
    $deletedItems = array_diff($ids1, $ids2);
    $newItems = array_merge(array_diff($ids2, $ids1), $deletedItems);

    return array(
      $newItems,
      $deletedItems
    );
  }

}
