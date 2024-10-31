<?php

namespace MRBS\CalendarServer;

class EmptyCalendarServerConnector implements AbstractCalendarServerConnector
{

  function pullCalendarUpdate()
  {
    return array();
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

  public function acceptMeeting($entry, string $msg)
  {
    // TODO: Implement acceptMeeting() method.
  }

  public function declineMeeting($entry, string $msg)
  {
    // TODO: Implement createRepeatMeeting() method.
  }
}
