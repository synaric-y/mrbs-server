<?php


declare(strict_types=1);

namespace MRBS;

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

$vars = array(
  "use_wxwork",
  "use_exchange",
  "exchange_server",
  "corpid",
  "secret",
  "agentid",
  "call_back_domain",
  "redis_host",
  "redis_post",
  "redis_password",
  "AD_server",
  "AD_post",
  "AD_base_dn",
  "AD_username",
  "AD_password",
  "AD_interval_date",
  "exchange_sync_type",
  "exchange_sync_interval",
  "logo_dir",
  "app_logo_dir",
  "time_type",
  "now_version",
  "show_book",
  "show_meeting_name",
  "temporary_meeting",
  "resolution",
  "company_name",
  "init_status",
  "server_address",
  "theme_type"
);


$result = db() -> query("SELECT * FROM " . _tbl("system_variable")) -> next_row_keyed();

foreach ($vars as $var) {
  if (empty($_POST[$var])){
    unset($result[$var]);
  }
}

ApiHelper::success($result);