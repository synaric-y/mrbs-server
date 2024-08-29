<?php
declare(strict_types=1);
namespace MRBS;

require_once "../defaultincludes.inc";
require_once "../mrbs_sql.inc";
require_once "api_helper.php";

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$area_id = intval($data['area_id']);

$rooms = get_rooms($area_id);

ApiHelper::success($rooms);
