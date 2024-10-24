<?php
declare(strict_types=1);
namespace MRBS;

/*
 * useless file
 */

$area_id = intval($_POST['area_id']);

$rooms = get_rooms($area_id);

ApiHelper::success($rooms);
