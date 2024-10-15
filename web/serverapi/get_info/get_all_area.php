<?php


declare(strict_types=1);

namespace MRBS;

$is_charge = $_POST["is_charge"] ?? null;
$battery_level = $_POST["battery_level"] ?? null;
$device_id = $_POST["device_id"] ?? null;

if (!empty($is_charge) || !empty($battery_level)) {
  if (!empty($device_id)) {
    $sql = "UPDATE " . _tbl("device") . " SET ";
    if (!empty($battery_level) && empty($is_charge)) {
      $sql .= "battery_level = ? WHERE device_id = ?";
      db()->command($sql, [$battery_level, $device_id]);
    }else if (empty($battery_level) && !empty($is_charge)) {
      $sql .= "is_charge = ? WHERE device_id = ?";
      db()->command($sql, [$is_charge, $device_id]);
    }else {
      $sql .= "battery_level = ?, is_charge = ? WHERE device_id = ?";
      db()->command($sql, [$battery_level, $is_charge, $device_id]);
    }
  }
}

$result = db() -> query("SELECT id, area_name FROM " . _tbl("area"));
if ($result -> count() == 0) {
  ApiHelper::fail(get_vocab("area_not_exist"), ApiHelper::AREA_NOT_EXIST);
}

ApiHelper::success($result->all_rows_keyed());
