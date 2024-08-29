<?php

namespace MRBS;

use JetBrains\PhpStorm\NoReturn;

class ApiHelper
{

  static function value($key)
  {
    return $_POST[$key];
  }

  static function success($data)
  {
    $rt = array(
      "code" => 0,
      "msg" => "ok",
      "data" => $data
    );
    echo json_encode($rt);
    exit();
  }

  static function fail($msg = "", $code = -1)
  {
    $rt = array(
      "code" => $code,
      "msg" => $msg,
      "data" => null
    );
    echo json_encode($rt);
    exit();
  }
}
