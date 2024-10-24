<?php


declare(strict_types=1);

namespace MRBS;

$device_id = $_POST['device_id'];

if(empty($device)){
  ApiHelper::fail(get_vocab("missing_parameters"), ApiHelper::MISSING_PARAMETERS);
}

db()->command("UPDATE" . _tbl("device") . " SET is_set=0, room_id=NULL WHERE device_id=?", array($device_id));

ApiHelper::success(null);
