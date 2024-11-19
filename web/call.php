<?php
namespace MRBS;

/*
 * Business logic entry
 */

$origin_arr = [
  'https://meeting-manage-dev.businessconnectchina.com:11443',
  'http://localhost:5173',
  'http://172.16.89.161:83',
  'http://172.16.89.91:81',
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $origin_arr)) {
  header('Access-Control-Allow-Origin:' . $origin);
}

header("Content-Type: application/json");
header("Cache-Control: no-cache");
header("Pragma: no-cache");
//header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTION');
ini_set('display_errors', 1);            //错误信息
ini_set('display_startup_errors', 1);    //php启动错误信息
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/vendor/autoload.php';
include_once "defaultincludes.inc";
include_once "functions_table.inc";
include_once "mrbs_sql.inc";

include_once "./serverapi/user_group/user_group_utils.php";



$act = $_GET["act"];

include_once "./serverapi/" . $act . ".php";
