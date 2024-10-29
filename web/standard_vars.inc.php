<?php
declare(strict_types=1);
namespace MRBS;

// Gets the standard variables of $day, $month, $year, $area and $room
// Checks that they are valid and assigns sensible defaults if not

// Get the standard form variables

require_once "defaultincludes.inc";

$variables = db() -> query("SELECT * FROM " . _tbl("system_variable")) -> next_row_keyed();

$use_wxwork = $variables['use_wxwork'];
$use_exchange = $variables['use_exchange'];
$corpid = $variables['corpid'];
$secret = $variables['secret'];
$agentid = $variables['agentid'];
$default_password_hash = $variables['default_password_hash'];
$call_back_domain = $variables['call_back_domain'];
$redis_host = $variables['redis_host'];
$redis_port = $variables['redis_port'];
$redis_password = $variables['redis_password'];
$AD_server = $variables['AD_server'];
$AD_port = $variables['AD_port'];
$AD_base_dn = $variables['AD_base_dn'];
$AD_username = $variables['AD_username'];
$AD_password = $variables['AD_password'];
$AD_timely_sync = $variables['AD_timely_sync'];
$AD_interval_type = $variables['AD_interval_type'];
$AD_interval_time = $variables['AD_interval_time'];
$AD_interval_date = $variables['AD_interval_date'];
$exchange_server = $variables['Exchange_server'];
$Exchange_sync_type = $variables['Exchange_sync_type'];
$Exchange_sync_interval = $variables['Exchange_sync_interval'];
$logo_dir = $variables['logo_dir'];
$app_logo_dir = $variables['app_logo_dir'];
$now_version = $variables['now_version'];
$show_book = $variables['show_book'];
$show_meeting_name = $variables['show_meeting_name'];
$temporary_meeting = $variables['temporary_meeting'];
$fast_meeting_type = $variables['fast_meeting_type'];
$resolution = $variables['resolution'];
$company_name = $variables['company_name'];
$init_status = $variables['init_status'];
$time_type = intval($variables['time_type']);
$server_address = $variables['server_address'];

