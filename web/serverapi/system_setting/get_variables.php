<?php


declare(strict_types=1);

namespace MRBS;

/*
 * get variables when the variables be set 1
 */

//if (!checkAuth()){
//  setcookie("session_id", "", time() - 3600, "/web/");
//  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
//}

$vars = array(
  "use_wxwork",
  "use_exchange",
  "Exchange_server",
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
  "Exchange_sync_type",
  "Exchange_sync_interval",
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
  "theme_type",
  "wx_appid",
  "wx_secret"
);

$must_login_vars = array(
  "use_wxwork",
  "use_exchange",
  "Exchange_server",
  "corpid",
  "secret",
  "agentid",
//  "call_back_domain",
  "redis_host",
  "redis_post",
  "redis_password",
  "AD_server",
  "AD_post",
  "AD_base_dn",
  "AD_username",
  "AD_password",
  "AD_interval_date",
  "Exchange_sync_type",
  "Exchange_sync_interval",
//  "logo_dir",
//  "app_logo_dir",
  "time_type",
  "now_version",
  "show_book",
  "show_meeting_name",
  "temporary_meeting",
  "resolution",
//  "company_name",
//  "init_status",
//  "server_address",
  "theme_type",
  "wx_appid",
  "wx_secret"
);


$result = db()->query("SELECT * FROM " . _tbl("system_variable"))->next_row_keyed();
$must_login = false;
foreach ($vars as $var) {
  if (empty($_POST[$var])) {
    unset($result[$var]);
  } else if (in_array($var, $must_login_vars)) {
    $must_login = true;
  }
  if (getLevel($_SESSION['user']) < 2) {
    if (!in_array($var, ['logo_dir', 'app_logo_dir', 'company_name', 'server_address'])) {
      unset($result[$var]);
    }
  }
}
if ($must_login) {
  if (!checkAuth()) {
    setcookie("session_id", "", time() - 3600, "/web/");
    ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
  }
}


ApiHelper::success($result);
