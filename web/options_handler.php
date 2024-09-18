<?php

/*
 * 全局入口函数，用于处理OPTIONS请求以及拒绝所有非POST请求
 * 对于所有的POST请求，将所有的POST请求体解析至超全局参数$_POST中
 */

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
//  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  header('Access-Control-Max-Age: 86400');
  header('Access-Control-Allow-Credentials: true');
  http_response_code(200);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_GET["act"] != 'wxwork_login') {
  // 设置 HTTP 状态码为 405 Method Not Allowed
  header('HTTP/1.1 405 Method Not Allowed');
  // 指定允许的请求方法
  header('Allow: POST');
  // 终止脚本执行
  exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$_POST = $data;
