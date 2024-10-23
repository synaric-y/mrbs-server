<?php
declare(strict_types=1);
namespace MRBS;

// Check the CSRF token.
//Form::checkToken();
//
//// Check the user is authorised for this page
//checkAuthorised(this_page());

if (!checkAuth()){
  setcookie("session_id", "", time() - 3600, "/web/");
  ApiHelper::fail(get_vocab("please_login"), ApiHelper::PLEASE_LOGIN);
}

if (getLevel($_SESSION['user']) < 2){
  ApiHelper::fail(get_vocab("no_right"), ApiHelper::ACCESSDENIED);
}

// Get non-standard form variables
$form_vars = array(
  'new_area'         => 'int',
  'old_area'         => 'int',
  'room_name'        => 'string',
  'sort_key'         => 'string',
  'room_disabled'    => 'string',
  'old_room_name'    => 'string',
  'description'      => 'string',
  'capacity'         => 'int',
  'room_admin_email' => 'string',
  'invalid_types'    => 'array',
  'exchange_username'=> 'string',
  'exchange_password'=> 'string',
  'wxwork_mr_id'     => 'string',
  'custom_html'      => 'string'
);

$room = $_POST['room'];
$group_ids = $_POST["group_ids"] ?? null;

foreach($form_vars as $var => $var_type)
{
//  $$var = get_form_var($var, $var_type);
  $$var = $_POST[$var] ?? null;
  if (($var_type == 'bool') || ($$var !== null))
  {
    switch ($var_type)
    {
      case 'array':
        $$var = (array) $$var;
        break;
      case 'bool':
        $$var = (bool) $$var;
        break;
      case 'decimal':
        // This isn't a very good sanitisation as it will let through thousands separators and
        // also multiple decimal points.  It needs to be improved, but care needs to be taken
        // over, for example, whether a comma should be allowed for a decimal point.  So for
        // the moment it errs on the side of letting through too much.
        $$var = filter_var($$var, FILTER_SANITIZE_NUMBER_FLOAT,
          FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
        if ($value === '')
        {
          $$var = null;
        }
        break;
      case 'int':
        $$var = ($$var === '') ? null : intval($$var);
        break;
      default:
        break;
    }
  }
  // Trim the strings and truncate them to the maximum field length
  if (is_string($$var))
  {
    $$var = trim($$var);
    $$var = truncate($$var, "room.$var");
  }

}

// Get the information about the fields in the room table
$fields = db()->field_info(_tbl('room'));

// Get any custom fields
foreach($fields as $field)
{

  switch($field['nature'])
  {
    case 'character':
      $type = 'string';
      break;
    case 'integer':
      // Smallints and tinyints are considered to be booleans
      $type = (isset($field['length']) && ($field['length'] <= 2)) ? 'string' : 'int';
      break;
    // We can only really deal with the types above at the moment
    default:
      $type = 'string';
      break;
  }
  $var = VAR_PREFIX . $field['name'];
//  $$var = get_form_var($var, $type);
  $$var = $_POST[$var] ?? null;
  if (($type == 'int') && ($$var === ''))
  {
    unset($$var);
  }
  // Turn checkboxes into booleans
  if (($field['nature'] == 'integer') &&
      isset($field['length']) &&
      ($field['length'] <= 2))
  {
    $$var = (empty($$var)) ? 0 : 1;
  }

  // Trim any strings and truncate them to the maximum field length
  if (is_string($$var) && ($field['nature'] != 'decimal'))
  {
    $$var = trim($$var);
    $$var = truncate($$var, 'room.' . $field['name']);
  }
}

if (empty($capacity))
{
  $capacity = 0;
}else if ($capacity > 1000){
  ApiHelper::fail(get_vocab("capacity_too_large"), ApiHelper::CAPACITY_TOO_LARGE);
}

// UPDATE THE DATABASE
// -------------------

// Initialise the error array
$errors = array();

// Clean up the address list replacing newlines by commas and removing duplicates
if (!empty($room_admin_email))
  $room_admin_email = clean_address_list($room_admin_email);
// Validate email addresses
if (!validate_email_list($room_admin_email) && !empty($room_admin_email))
{
  ApiHelper::fail(get_vocab("invalid_email"), ApiHelper::INVALID_EMAIL);
}

// Make sure the invalid types exist
if (isset($booking_types))
{
  $invalid_types = array_intersect($invalid_types, $booking_types);
}
else
{
  $invalid_types = array();
}


if (empty($errors))
{
  // Used purely for the syntax_casesensitive_equals() call below, and then ignored
  $sql_params = array();

  // Start a transaction
  db()->begin();

  // Check the new area still exists
  $sql = "SELECT id
            FROM " . _tbl('area') . "
           WHERE id=?
           LIMIT 1
      FOR UPDATE";  // lock this row

  if (db()->query1($sql, array($new_area)) < 1)
  {
    db()->rollback();
    ApiHelper::fail(get_vocab("invalid_area"), ApiHelper::INVALID_AREA);
  }
  // If so, check that the room name is not already used in the area
  // (only do this if you're changing the room name or the area - if you're
  // just editing the other details for an existing room we don't want to reject
  // the edit because the room already exists!)
  elseif ( (($new_area != $old_area) || ($room_name != $old_room_name))
          && db()->query1("SELECT id
                                 FROM " . _tbl('room') . "
                                WHERE room_name=:room_name
                                  AND area_id=:area_id
                                LIMIT 1
                           FOR UPDATE", array(":room_name" => $room_name, ":area_id" => $new_area)) > 0)
  {
    db()->rollback();
    ApiHelper::fail(get_vocab("invalid_room_name"));
  }
  // If everything is still OK, update the database
  else
  {
    // Convert booleans into 0/1 (necessary for PostgreSQL)
    $room_disabled = (!empty($room_disabled)) ? 1 : 0;
    $sql = "UPDATE " . _tbl('room') . " SET ";
    $sql_params = array();
    $assign_array = array();
    foreach ($fields as $field)
    {
      if ($field['name'] != 'id')  // don't do anything with the id field
      {
        switch ($field['name'])
        {
          // first of all deal with the standard MRBS fields
          case 'area_id':
            $assign_array[] = "area_id=?";
            $sql_params[] = $new_area;
            break;
          case 'disabled':
            $assign_array[] = "disabled=?";
            $sql_params[] = $room_disabled;
            break;
          case 'room_name':
            $assign_array[] = "room_name=?";
            $sql_params[] = $room_name;
            break;
          case 'sort_key':
            $assign_array[] = "sort_key=?";
            $sql_params[] = $sort_key;
            break;
          case 'description':
            $assign_array[] = "description=?";
            $sql_params[] = $description;
            break;
          case 'capacity':
            $assign_array[] = "capacity=?";
            $sql_params[] = $capacity;
            break;
          case 'room_admin_email':
            $assign_array[] = "room_admin_email=?";
            $sql_params[] = $room_admin_email;
            break;
          case 'invalid_types':
            $assign_array[] = "invalid_types=?";
            $sql_params[] = json_encode($invalid_types);
            break;
          case 'custom_html':
            $assign_array[] = "custom_html=?";
            $sql_params[] = $custom_html;
            break;
          case 'exchange_username':
            $assign_array[] = "exchange_username=?";
            $sql_params[] = $exchange_username;
            break;
          case 'exchange_password':
            $assign_array[] = "exchange_password=?";
            $sql_params[] = $exchange_password;
            break;
          case 'wxwork_mr_id':
            $assign_array[] = "wxwork_mr_id=?";
            $sql_params[] = $wxwork_mr_id;
            break;
          // then look at any user defined fields
          default:
            $var = VAR_PREFIX . $field['name'];
            switch ($field['nature'])
            {
              case 'integer':
                if (!isset($$var) || ($$var === ''))
                {
                  // Try and set it to NULL when we can because there will be cases when we
                  // want to distinguish between NULL and 0 - especially when the field
                  // is a genuine integer.
                  $$var = ($field['is_nullable']) ? null : 0;
                }
                break;
              default:
                // Do nothing
                break;
            }
            $assign_array[] = db()->quote($field['name']) . "=?";
            $sql_params[] = $$var;
            break;
        }
      }
    }

    $sql .= implode(",", $assign_array) . " WHERE id=?";
    $sql_params[] = $room;
    db()->command($sql, $sql_params);
    db()->command("DELETE FROM " . _tbl("room_group") . " WHERE room_id = ?", array($room));
    if (!empty($group_ids)){
      $sql = "INSERT INTO " . _tbl("room_group") . "(room_id, group_id) VALUES ";
      $params = array();
      foreach ($group_ids as $group_id) {
        $sql .= "(?, ?),";
        $params[] = $room;
        $params[] = $group_id;
      }
      $sql = substr($sql, 0, -1);
      db()->command($sql, $params);
    }
    // Commit the transaction
    db()->commit();

    // Go back to the admin page (for the new area)
    ApiHelper::success(null);
  }

}
