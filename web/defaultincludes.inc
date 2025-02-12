<?php
declare(strict_types=1);
namespace MRBS;

define ('MRBS_MIN_PHP_VERSION', '7.2.0');

// Check PHP version
// Do it now before we start including code that might fail with a syntax error,
// for example if anonymous functions are being used.
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MRBS_MIN_PHP_VERSION) < 0)
{
  die("MRBS requires PHP " . MRBS_MIN_PHP_VERSION . " or above.   This server is running version " . PHP_VERSION . ".");
}

define('MRBS_ROOT',     __DIR__);                   // Root of MRBS installation

// We use require for some files rather than require_once because the values that
// are assigned to variables will change depending on the context in which the file
// is called.

require_once 'options_handler.php';
require_once 'lib/autoload.inc';
require_once 'grab_globals.inc.php';   // this must be included before mrbs_auth.inc (due to WordPress - see comment in file)
require_once 'systemdefaults.inc.php';
require_once 'areadefaults.inc.php';
require_once 'functions_error.inc';
require_once 'config.inc.php';
require_once 'site_config.inc';
require_once 'internalconfig.inc.php';
require_once 'functions_global.inc';
require_once 'functions.inc';
require_once 'language.inc';
require_once 'theme.inc';
require_once 'dbsys.inc';
require_once 'mrbs_auth.inc';
require_once 'init.inc';
require_once 'upgrade.inc';
require 'standard_vars.inc.php';
require_once 'serverapi/check.php';
require_once 'redis_keys.php';

