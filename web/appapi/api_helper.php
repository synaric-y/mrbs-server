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

  static function fail($msg = "")
  {
    $rt = array(
      "code" => -1,
      "msg" => $msg,
      "data" => null
    );
    echo json_encode($rt);
    exit();
  }
}
