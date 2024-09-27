<?php
declare(strict_types=1);
namespace MRBS;

// Gets the standard variables of $day, $month, $year, $area and $room
// Checks that they are valid and assigns sensible defaults if not

// Get the standard form variables

require_once "defaultincludes.inc";

global $db_host, $db_password, $db_login;

$variables = db() -> query("SELECT * FROM " . _tbl("system_variable")) -> next_row_keyed();

$db_host = $variables['mysql_host'];
$db_password = $variables['mysql_password'];
$db_login = $variables['mysql_user'];
$use_wxwork = $variables['use_wxwork'];
$use_exchange = $variables['use_exchange'];
$exchange_server = $variables['exchange_server'];
$corpid = $variables['corpid'];
$secret = $variables['secret'];
$agentid = $variables['agentid'];
$default_password_hash = $variables['default_password_hash'];
$call_back_domain = $variables['call_back_domain'];
$redis_host = $variables['redis_host'];
$redis_port = $variables['redis_port'];
$db_port = $variables['mysql_port'];
