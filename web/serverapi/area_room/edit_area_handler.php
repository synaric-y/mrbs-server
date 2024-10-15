<?php
declare(strict_types=1);

namespace MRBS;

/*
 * 用于编辑会议的接口
 * @Params
 * area：区域id
 * sort_key：排序值
 * area_name：区域名称
 * area_disabled：区域是否禁用
 * area_timezone：区域时区，TODO 暂时未启用
 * area_admin_email：区域管理员电子邮箱
 * area_start_first_slot：区域
 */

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

// Get non-standard form variables
$form_vars = array(
  'sort_key' => 'string',
  'area_name' => 'string',
  'area_disabled' => 'string',
  'area_timezone' => 'string',
  'area_admin_email' => 'string',
  'area_start_first_slot' => 'string',
  'area_start_last_slot' => 'string',
  'area_res_mins' => 'int',
  'area_def_duration_mins' => 'int',
  'area_def_duration_all_day' => 'string',
  'area_min_create_ahead_enabled' => 'string',
  'area_min_create_ahead_value' => 'int',
  'area_min_create_ahead_units' => 'string',
  'area_max_create_ahead_enabled' => 'string',
  'area_max_create_ahead_value' => 'int',
  'area_max_create_ahead_units' => 'string',
  'area_min_delete_ahead_enabled' => 'string',
  'area_min_delete_ahead_value' => 'int',
  'area_min_delete_ahead_units' => 'string',
  'area_max_delete_ahead_enabled' => 'string',
  'area_max_delete_ahead_value' => 'int',
  'area_max_delete_ahead_units' => 'string',
  'area_max_duration_enabled' => 'string',
  'area_max_duration_periods' => 'int',
  'area_max_duration_value' => 'int',
  'area_max_duration_units' => 'string',
  'area_private_enabled' => 'string',
  'area_private_default' => 'int',
  'area_private_mandatory' => 'string',
  'area_private_override' => 'string',
  'area_approval_enabled' => 'string',
  'area_reminders_enabled' => 'string',
  'area_enable_periods' => 'string',
  'area_periods' => 'array',
  'area_confirmation_enabled' => 'string',
  'area_confirmed_default' => 'string',
  'area_default_type' => 'string',
  'area_times_along_top' => 'string',
);

$area = $_POST['area'];
foreach ($form_vars as $var => $var_type) {
  $$var = $_POST[$var] ?? null;
  if (($var_type == 'bool') || ($$var !== null)) {
    switch ($var_type) {
      case 'array':
        $$var = (array)$$var;
        break;
      case 'bool':
        $$var = (bool)$$var;
        break;
      case 'decimal':
        // This isn't a very good sanitisation as it will let through thousands separators and
        // also multiple decimal points.  It needs to be improved, but care needs to be taken
        // over, for example, whether a comma should be allowed for a decimal point.  So for
        // the moment it errs on the side of letting through too much.
        $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT,
          FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
        if ($value === '') {
          $value = null;
        }
        break;
      case 'int':
        $$var = ($$var === '') ? null : intval($$var);
        break;
      case 'string':
        $$var = (string) $$var;
        break;
      default:
        break;
    }
  }
//  // Trim the strings and truncate them to the maximum field length
  if (is_string($$var)) {
    $$var = trim($$var);
    $$var = truncate($$var, "area.$var");
  }
}

if (!isset($area_default_type))
{
  $area_default_type = $area_defaults['default_type'];
}



// Get the max_per_interval form variables
foreach ($interval_types as $interval_type)
{
  $var = "area_max_per_$interval_type";
//  $$var = get_form_var($var, 'int');
  if (isset($_POST[$var]))
    $$var = intval($_POST[$var]);
  $var = "area_max_per_{$interval_type}_enabled";
//  $$var = get_form_var($var, 'string');
  if (isset($_POST[$var]))
    $$var = (string) $_POST[$var] ?? null;
  $var = "area_max_secs_per_$interval_type";
//  $$var = get_form_var($var, 'int');
  if (isset($_POST[$var]))
    $$var = intval($_POST[$var]);
  $var = "area_max_secs_per_{$interval_type}_units";
//  $$var = get_form_var($var, 'string');
  if (isset($_POST[$var]))
    $$var = (string) $_POST[$var] ?? null;
  $var = "area_max_secs_per_{$interval_type}_enabled";
//  $$var = get_form_var($var, 'string');
  if (isset($_POST[$var]))
    $$var = (string) $_POST[$var] ?? null;
}

// UPDATE THE DATABASE
// -------------------

if (empty($area))
{
  throw new \Exception('$area is empty');
}

// Initialise the error array
$errors = array();

// Check the name hasn't been used in another area
$id = get_area_id($area_name);
if (isset($id) && ($id != $area))
{
  ApiHelper::fail(get_vocab("area_not_exist"), ApiHelper::AREA_NOT_EXIST);
}

// Clean up the address list replacing newlines by commas and removing duplicates
$area_admin_email = clean_address_list($area_admin_email);
// Validate email addresses
if (!validate_email_list($area_admin_email))
{
  ApiHelper::fail(get_vocab("invalid_email"), ApiHelper::INVALID_EMAIL);
}

// Check that the time formats are correct (hh:mm).  They should be, because
// the HTML5 element or polyfill will force them to be, but just in case ...
// (for example if we are relying on a polyfill and JavaScript is disabled)

if (!preg_match(REGEX_HHMM, $area_start_first_slot) ||
    !preg_match(REGEX_HHMM, $area_start_last_slot))
{
  ApiHelper::fail(get_vocab("invalid_start_time"), ApiHelper::INVALID_START_TIME);
}
else
{
  // Get morningstarts and eveningends
  list($area_morningstarts, $area_morningstarts_minutes) = explode(':', $area_start_first_slot);
  list($area_eveningends, $area_eveningends_minutes) = explode(':', $area_start_last_slot);

  // Convert the book ahead times into seconds
  fromTimeString($area_min_create_ahead_value, $area_min_create_ahead_units);
  fromTimeString($area_max_create_ahead_value, $area_max_create_ahead_units);
  fromTimeString($area_min_delete_ahead_value, $area_min_delete_ahead_units);
  fromTimeString($area_max_delete_ahead_value, $area_max_delete_ahead_units);

  fromTimeString($area_max_duration_value, $area_max_duration_units);

  // If we are using periods, round these down to the nearest whole day
  // (anything less than a day is meaningless when using periods)
  if ($area_enable_periods)
  {
    $vars = array('area_min_create_ahead_value',
                  'area_max_create_ahead_value',
                  'area_min_delete_ahead_value',
                  'area_max_delete_ahead_value');

    foreach ($vars as $var)
    {
      if (isset($$var))
      {
        $$var -= $$var % SECONDS_PER_DAY;
      }
    }
  }

  // Convert booleans into 0/1 (necessary for PostgreSQL)
  $vars = array(
      'area_disabled',
      'area_def_duration_all_day',
      'area_min_create_ahead_enabled',
      'area_max_create_ahead_enabled',
      'area_min_delete_ahead_enabled',
      'area_max_delete_ahead_enabled',
      'area_max_duration_enabled',
      'area_private_enabled',
      'area_private_default',
      'area_private_mandatory',
      'area_approval_enabled',
      'area_reminders_enabled',
      'area_enable_periods',
      'area_confirmation_enabled',
      'area_confirmed_default',
      'area_times_along_top'
    );

  foreach ($interval_types as $interval_type)
  {
    $vars[] = "area_max_per_{$interval_type}_enabled";
    $vars[] = "area_max_secs_per_{$interval_type}_enabled";
  }

  foreach ($vars as $var)
  {
    $$var = (!empty($$var) && $$var == '1') ? 1 : 0;
  }


  if (!$area_enable_periods)
  {
    // Avoid divide by zero errors
    if ($area_res_mins == 0)
    {
      ApiHelper::fail(get_vocab("invalid_resolution"), ApiHelper::INVALID_RESOLUTION);
    }
    else
    {
      // Check morningstarts, eveningends, and resolution for consistency
      $start_first_slot = ($area_morningstarts*60) + $area_morningstarts_minutes;   // minutes
      $start_last_slot  = ($area_eveningends*60) + $area_eveningends_minutes;       // minutes

      // If eveningends is before morningstarts then it's really on the next day
      if (hm_before(array('hours' => $area_eveningends, 'minutes' => $area_eveningends_minutes),
                    array('hours' => $area_morningstarts, 'minutes' => $area_morningstarts_minutes)))
      {
        $start_last_slot += MINUTES_PER_DAY;
      }

      $start_difference = ($start_last_slot - $start_first_slot);         // minutes

      if ($start_difference%$area_res_mins != 0)
      {
        ApiHelper::fail(get_vocab("invalid_resolution"), ApiHelper::INVALID_RESOLUTION);
      }
    }
  }
}

// Everything is OK, update the database

$sql = "UPDATE " . _tbl('area') . " SET ";
$sql_params = array();
$assign_array = array();
$assign_array[] = "area_name=?";
$sql_params[] = $area_name;
$assign_array[] = "sort_key=?";
$sql_params[] = $sort_key;
$assign_array[] = "disabled=?";
$sql_params[] = $area_disabled;
$assign_array[] = "timezone=?";
$sql_params[] = $area_timezone;
$assign_array[] = "area_admin_email=?";
$sql_params[] = $area_admin_email;

if (isset($custom_html))
{
  // The custom HTML field won't be present if it has been
  // disabled in the config file
  $assign_array[] = "custom_html=?";
  $sql_params[] = $custom_html;
}

if (!$area_enable_periods)
{
  $assign_array[] = "resolution=?";
  $sql_params[] = $area_res_mins * 60;
  if (!$area_def_duration_all_day)
  {
    // If the default duration is all day, then this value will have
    // been disabled on the form, so don't change it.
    $assign_array[] = "default_duration=?";
    $sql_params[] = $area_def_duration_mins * 60;
  }
  $assign_array[] = "default_duration_all_day=?";
  $sql_params[] = $area_def_duration_all_day;
  $assign_array[] = "morningstarts=?";
  $sql_params[] = $area_morningstarts;
  $assign_array[] = "morningstarts_minutes=?";
  $sql_params[] = $area_morningstarts_minutes;
  $assign_array[] = "eveningends=?";
  $sql_params[] = $area_eveningends;
  $assign_array[] = "eveningends_minutes=?";
  $sql_params[] = $area_eveningends_minutes;
}

// only update the min and max *_ahead_secs fields if the form values
// are set;  they might be NULL because they've been disabled by JavaScript
$assign_array[] = "min_create_ahead_enabled=?";
$sql_params[] = $area_min_create_ahead_enabled;
$assign_array[] = "max_create_ahead_enabled=?";
$sql_params[] = $area_max_create_ahead_enabled;
$assign_array[] = "min_delete_ahead_enabled=?";
$sql_params[] = $area_min_delete_ahead_enabled;
$assign_array[] = "max_delete_ahead_enabled=?";
$sql_params[] = $area_max_delete_ahead_enabled;
$assign_array[] = "max_duration_enabled=?";
$sql_params[] = $area_max_duration_enabled;

if (isset($area_min_create_ahead_value))
{
  $assign_array[] = "min_create_ahead_secs=?";
  $sql_params[] = $area_min_create_ahead_value;
}
if (isset($area_max_create_ahead_value))
{
  $assign_array[] = "max_create_ahead_secs=?";
  $sql_params[] = $area_max_create_ahead_value;
}
if (isset($area_min_delete_ahead_value))
{
  $assign_array[] = "min_delete_ahead_secs=?";
  $sql_params[] = $area_min_delete_ahead_value;
}
if (isset($area_max_delete_ahead_value))
{
  $assign_array[] = "max_delete_ahead_secs=?";
  $sql_params[] = $area_max_delete_ahead_value;
}
if (isset($area_max_duration_value))
{
  $assign_array[] = "max_duration_secs=?";
  $sql_params[] = $area_max_duration_value;
  $assign_array[] = "max_duration_periods=?";
  $sql_params[] = $area_max_duration_periods;
}

foreach($interval_types as $interval_type)
{
  $var = "max_per_{$interval_type}_enabled";
  $area_var = "area_" . $var;
  $assign_array[] = "$var='" . $$area_var . "'";

  $var = "max_per_$interval_type";
  $area_var = "area_" . $var;
  if (isset($$area_var))
  {
    // only update these fields if they are set;  they might be NULL because
    // they have been disabled by JavaScript
    $assign_array[] = "$var=?";
    $sql_params[] = $$area_var;
  }

  // Now do the max_secs variables (limits on the total length of bookings)
  $var = "max_secs_per_{$interval_type}_enabled";
  $area_var = "area_" . $var;
  $assign_array[] = "$var='" . $$area_var . "'";

  $var = "max_secs_per_$interval_type";
  $area_var = "area_" . $var;

  if (isset($$area_var))
  {
    // only update these fields if they are set;  they might be NULL because
    // they have been disabled by JavaScript
    // Need to convert back into seconds
    $units_var = "area_max_secs_per_{$interval_type}_units";
    fromTimeString($$area_var, $$units_var);
    $assign_array[] = "$var=?";
    $sql_params[] = $$area_var;
  }
}

$assign_array[] = "private_enabled=?";
$sql_params[] = $area_private_enabled;
$assign_array[] = "private_default=?";
$sql_params[] = $area_private_default;
$assign_array[] = "private_mandatory=?";
$sql_params[] = $area_private_mandatory;
$assign_array[] = "private_override=?";
$sql_params[] = $area_private_override;
$assign_array[] = "approval_enabled=?";
$sql_params[] = $area_approval_enabled;
$assign_array[] = "reminders_enabled=?";
$sql_params[] = $area_reminders_enabled;
$assign_array[] = "enable_periods=?";
$sql_params[] = $area_enable_periods;
$assign_array[] = "periods=?";
$sql_params[] = json_encode($area_periods);
$assign_array[] = "confirmation_enabled=?";
$sql_params[] = $area_confirmation_enabled;
$assign_array[] = "confirmed_default=?";
$sql_params[] = $area_confirmed_default;
$assign_array[] = "default_type=?";
$sql_params[] = $area_default_type;
$assign_array[] = "times_along_top=?";
$sql_params[] = $area_times_along_top;

$sql .= implode(",", $assign_array) . " WHERE id=?";
$sql_params[] = $area;

$areaExist = db() -> query1("SELECT COUNT(*) FROM " . _tbl("area") . " WHERE id = ?", array($area));
if ($areaExist > 0) {
  db()->command($sql, $sql_params);
  $success = true;
}else{
  $success = false;
}
if ($success){
  ApiHelper::success(null);
}else{
  ApiHelper::fail(get_vocab("area_not_exist"), ApiHelper::AREA_NOT_EXIST);
}
