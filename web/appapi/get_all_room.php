<?php
declare(strict_types=1);
namespace MRBS;

$area_id = 1;

$rooms = get_rooms($area_id);

ApiHelper::success($rooms);
