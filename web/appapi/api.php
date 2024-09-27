<?php
namespace MRBS;

//header("Content-Type:text/html;charset=utf-8"); //输出格式
header("Content-Type: application/json");
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Access-Control-Allow-Origin: *");
ini_set('display_errors', 1);            //错误信息
ini_set('display_startup_errors', 1);    //php启动错误信息
error_reporting(E_ALL);

include_once dirname(__DIR__, 1) . "/defaultincludes.inc";
include_once dirname(__DIR__, 1) ."/functions_table.inc";
include_once dirname(__DIR__, 1) ."/mrbs_sql.inc";
include_once __DIR__ . "/api_helper.php";
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

function FD($key)
{
  return $_POST[$key];
}

$act = $_GET["act"];

include_once "./" . $act . ".php";
