<?php

namespace MRBS;

/*
 * This is a setup script to insert init data into DB.
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__) . "/defaultincludes.inc";
require_once dirname(__DIR__) . "/functions_table.inc";
require_once dirname(__DIR__) . "/mrbs_sql.inc";

$pwd = password_hash('123456', PASSWORD_DEFAULT);

db()->command("INSERT INTO mrbs_users (level, name, display_name, email, password_hash) values (2, 'admin', 'admin', '', '$pwd')");
db()->command("UPDATE mrbs_system_variable SET default_password_hash = '$pwd'");
