<?php


use MRBS\LDAP\SyncADManager;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__) . "/defaultincludes.inc";
require_once dirname(__DIR__) . "/functions_table.inc";
require_once dirname(__DIR__) . "/mrbs_sql.inc";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * A timing script for synchronizing AD(LDAP) data.
 * This script should be started by the timer and not called by the network request.
 */
$manager = new SyncADManager();
$manager->syncAD();
