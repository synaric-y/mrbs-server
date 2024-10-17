<?php

namespace MRBS;

use MRBS\LDAP\SyncADManager;

//if (!checkAuth()){
//  setcookie("session_id", "", time() - 3600, "/web/");
//  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
//}
//
//if (getLevel($_SESSION['user']) < 2){
//  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
//}

$sync_version = $_POST['sync_version'];

$manager = new SyncADManager();
$manager->syncAD($sync_version);

$result = array();
ApiHelper::success($result);

