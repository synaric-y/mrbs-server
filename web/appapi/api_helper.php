<?php

namespace MRBS;

class ApiHelper
{

  public const  SUCCESS = 0;
  public const PLEASE_LOGIN = -99;
  public const ACCESSDENIED = -98;
  public const UNKOWN_ERROR = -97;
  public const ALREADY_LOGIN = 1;
  public const INVALID_USERNAME_OR_PASSWORD = -2;
  public const INVALID_START_TIME = -3;
  public const INVALID_END_TIME = -4;
  public const EDIT_ENTRY_NOT_EXIST = -5;
  public const NO_ACCESS_TO_ENTRY = -6;
  public const REPEAT_ENTRY_CONFLICT = -7;
  public const ENTRY_CONFLICT = -8;
  public const WRONG_TYPE = -9;
  public const EMPTY_NAME = -10;
  public const AREA_NOT_EXIST = -11;
  public const NO_ROOM_IN_AREA = -12;
  public const ROOM_NOT_EXIST = -13;
  public const ENTRY_IN_ROOM = -14;
  public const ROOM_IN_AREA = -15;
  public const FAIL_TO_DELETE_ENTRY = -16;
  public const DEVICE_NOT_EXIST = -17;
  public const USER_NOT_EXIST = -18;
  public const EDIT_WITHOUT_ID = -19;
  public const NAME_NOT_UNIQUE = -20;
  public const DELETE_YOURSELF = -21;
  public const NO_ADMIN = -22;
  public const SEARCH_WITHOUT_ID = -23;
  public const NO_ACCESS_NO_POLICY = -24;
  public const AREA_DISABLED = -25;
  public const ROOM_DISABLED = -26;
  public const INVALID_CONFIRM = -27;
  public const INVALID_RESOLUTION = -28;
  public const INVALID_ROOM_NAME = -29;
  public const INVALID_AREA = -30;
  public const INVALID_TYPES = -31;
  public const EXPIRED_END_TIME = -32;
  public const CAPACITY_TOO_LARGE = -33;
  public const NO_RIGHT = -98;
  public const AREA_OR_ROOM_DISABLED = -35;
  public const MISSING_PARAMETERS = -36;
  public const INVALID_EMAIL = -37;
  public const INVALID_REP_INTERVAL = -38;
  public const MISSING_MANDATORY_FIELD = -39;
  public const TYPE_RESERVED_FOR_ADMINS = -40;
  public const PASSWORDS_NOT_EQ = -41;
  public const ENTRY_NOT_EXIST = -42;
  public const INVALID_CODE = -43;
  public const FAIL_TO_CREATE_USER = -44;
  static function value($key)
  {
    return $_POST[$key];
  }

  static function success($data)
  {
    $rt = array(
      "code" => 0,
      "msg" => get_vocab("success"),
      "data" => $data
    );
    echo json_encode($rt);
    exit();
  }

  static function fail($msg = "", $code = -1, $data = null)
  {
    $rt = array(
      "code" => $code,
      "msg" => $msg,
      "data" => $data
    );
    echo json_encode($rt);
    exit();
  }
}
