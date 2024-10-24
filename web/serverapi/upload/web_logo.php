<?php


declare(strict_types=1);

/*
 * upload the logo of website
 */
namespace MRBS;

use ZipArchive;

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$file = $_FILES['logo'];

if($file['error'] === UPLOAD_ERR_OK){
  $name = $file['name'];
  $fileInfo = pathinfo($name);
  if (strtolower($fileInfo['extension']) !== 'jpg' && strtolower($fileInfo['extension']) !== 'png'){
    ApiHelper::fail(get_vocab("unsupport_file_type"), ApiHelper::UNSUPPORT_FILE_TYPE);
  }
  $dir = dirname(__DIR__, 3) . "\\logo\\" . $_SESSION['user'] ;
  if (!is_dir($dir)) {
    mkdir($dir, 0755, TRUE);
  }
  $dir .= "\\logo." . strtolower($fileInfo['extension']);
  if (move_uploaded_file($file['tmp_name'], $dir)){
    db()->command("UPDATE " . _tbl("system_variable") . " SET logo_dir = ?", array("/logo/" . $_SESSION['user'] . "/logo." . strtolower($fileInfo['extension'])));
    ApiHelper::success(null);
  }else{
    ApiHelper::fail(get_vocab("fail_to_upload"), ApiHelper::FAIL_TO_UPLOAD);
  }
}

ApiHelper::fail(get_vocab("fail_to_upload"), ApiHelper::FAIL_TO_UPLOAD);
