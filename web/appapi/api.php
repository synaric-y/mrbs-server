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

include_once "../defaultincludes.inc";
include_once "../functions_table.inc";
include_once "../mrbs_sql.inc";
include_once "./api_helper.php";

function FD($key)
{
  return $_POST[$key];
}

$act = $_GET["act"];

include_once "./" . $act . ".php";
