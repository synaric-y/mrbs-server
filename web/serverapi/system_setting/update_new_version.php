<?php


declare(strict_types=1);

namespace MRBS;

use ZipArchive;

/*
 * upload the device interface code with version
 */

if (!checkAuth()) {
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2) {
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESS_DENIED);
}

$version = $_REQUEST['version'];
$result = db()->query1("SELECT COUNT(*) FROM " . _tbl("version") . " WHERE version = ?", array($version));
if ($result > 0) {
  ApiHelper::fail(get_vocab("version_exists"), ApiHelper::VERSION_EXISTS);
}

$file = $_FILES['file'];

if ($file['error'] === UPLOAD_ERR_OK) {
  $name = $file['name'];
  $fileInfo = pathinfo($name);
  if (strtolower($fileInfo['extension']) !== 'zip') {
    ApiHelper::fail(get_vocab("unsupport_file_type"), ApiHelper::UNSUPPORT_FILE_TYPE);
  }
  $zip = new ZipArchive;
  // Check version name
  if ($zip->open($file['tmp_name']) === TRUE) {
    $root_name = $zip->getNameIndex(0);
    $root_name = trim($root_name, '/');
    if (preg_match('/^\d+(\.\d+){0,2}$/', $root_name) != 1) {
      ApiHelper::fail(get_vocab("invalid_file_name"), ApiHelper::INVALID_FILE_NAME);
    }
    $zip->close();
    $version = $root_name;
  } else {
    ApiHelper::fail(get_vocab("unsupport_file_type"), ApiHelper::UNSUPPORT_FILE_TYPE);
  }

  // file will be placed here
  $dir = dirname(__DIR__, 3) . "/display/";
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  } else {
    deleteDirectory($dir . "/" . $version);
  }
  $dir .= "/" . $version . ".zip";
  if (move_uploaded_file($file['tmp_name'], $dir)) {
    $zip = new ZipArchive;
    if ($zip->open($dir) === TRUE) {
      $zip->extractTo(dirname($dir));
      $zip->close();
    } else {
      ApiHelper::fail(get_vocab("unable_to_open_zip"), ApiHelper::UNABLE_TO_OPEN_ZIP);
    }
  } else {
    ApiHelper::fail(get_vocab("fail_to_upload"), ApiHelper::FAIL_TO_UPLOAD);
  }

  db()->command("DELETE * FROM " . _tbl("version") . " WHERE version = ?",array($version));
  db()->command("INSERT IGNORE INTO " . _tbl("version") . "(version, publish_time) VALUES (?, ?)", array($version, time()));

  ApiHelper::success(null);
}


ApiHelper::fail(get_vocab("fail_to_upload"), ApiHelper::FAIL_TO_UPLOAD);
