<?php
namespace MRBS;

$origin_arr = [
  'https://meeting-manage-dev.businessconnectchina.com:11443',
  'http://localhost:5173',
  'http://172.16.89.161:83',
  'http://172.16.89.91:83',
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

include_once "./defaultincludes.inc";
include_once "./functions_table.inc";
include_once "./mrbs_sql.inc";

function FD($key)
{
  return $_POST[$key];
}

$act = $_GET["act"];

include_once "./" . $act . ".php";
