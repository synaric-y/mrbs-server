<?php
declare(strict_types=1);
namespace MRBS;

require_once "../defaultincludes.inc";
require_once "../mrbs_sql.inc";
require_once "api_helper.php";

$area_id = 2;

$rooms = get_rooms($area_id);

ApiHelper::success($rooms);
