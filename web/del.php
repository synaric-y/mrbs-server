<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\ElementInputSubmit;
use MRBS\Form\Form;

require "defaultincludes.inc";


//function generate_no_form(int $room, int $area) : void
//{
//  $form = new Form(Form::METHOD_POST);
//
//  $attributes = array('action' => multisite('admin.php'));
//
//  $form->setAttributes($attributes);
//
//  // Hidden inputs
//  $hidden_inputs = array('area' => $area,
//                         'room' => $room);
//  $form->addHiddenInputs($hidden_inputs);
//
//  // The button
//  $element = new ElementInputSubmit();
//  $element->setAttribute('value', get_vocab("NO"));
//  $form->addElement($element);
//
//  $form->render();
//}
//
//
//function generate_yes_form(int $room, int $area) : void
//{
//  $form = new Form(Form::METHOD_POST);
//
//  $attributes = array('action' => multisite('del.php'));
//
//  $form->setAttributes($attributes);
//
//  // Hidden inputs
//  $hidden_inputs = array('type'    => 'room',
//                         'area'    => $area,
//                         'room'    => $room,
//                         'confirm' => '1');
//  $form->addHiddenInputs($hidden_inputs);
//
//  // The button
//  $element = new ElementInputSubmit();
//  $element->setAttribute('value', get_vocab("YES"));
//  $form->addElement($element);
//
//  $form->render();
//}


//// Check the CSRF token
//Form::checkToken();
//
//// Check the user is authorised for this page
//checkAuthorised(this_page());

// Get non-standard form variables
//$type = get_form_var('type', 'string');
//$confirm = get_form_var('confirm', 'string', null, INPUT_POST);

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$type = $data['type'];
$room = $data['room'];
$area = $data['area'];

//$context = array(
//    'view'      => $view,
//    'view_all'  => $view_all,
//    'year'      => $year,
//    'month'     => $month,
//    'day'       => $day,
//    'area'      => $area,
//    'room'      => $room ?? null
//  );

// This is gonna blast away something. We want them to be really
// really sure that this is what they want to do.
if ($type == "room")
{
  $n_entries = get_n_entries_by_room($room);
  // We are supposed to delete a room
  if ($n_entries === 0)
  {
    $limit = 20;
    $entries = get_entries_by_room($room, null, null, true, $limit);
    assert(count($entries) === 0);
    // They have confirmed it already, so go blast!
    db()->begin();
    try
    {
      // First take out all appointments for this room
      $sql = "DELETE FROM " . _tbl('entry') . " WHERE room_id=?";
      db()->command($sql, array($room));

      $sql = "DELETE FROM " . _tbl('repeat') . " WHERE room_id=?";
      db()->command($sql, array($room));

      // Now take out the room itself
      $sql = "DELETE FROM " . _tbl('room') . " WHERE id=?";
      db()->command($sql, array($room));
    }
    catch (DBException $e)
    {
      db()->rollback();
      throw $e;
    }

    db()->commit();

    // Go back to the admin page
    $response = array(
      "code" => 0,
      "message" => get_vocab("success")
    );
    echo json_encode($response);
    return;
  }
  else
  {
    $response = array(
      "code" => -1,
      "message" => get_vocab("entry_in_room")
    );
    echo json_encode($response);
    return;
  }
}

if ($type == "area")
{
  // We are only going to let them delete an area if there are
  // no rooms. its easier
  $sql = "SELECT COUNT(*)
            FROM " . _tbl('room') . "
           WHERE area_id=?";

  $n = db()->query1($sql, array($area));
  if ($n === 0)
  {
    // OK, nothing there, let's blast it away
    $sql = "DELETE FROM " . _tbl('area') . "
             WHERE id=?";

    db()->command($sql, array($area));

    // Redirect back to the admin page
    $response = array(
      "code" => 0,
      "message" => get_vocab("success")
    );
    echo json_encode($response);
    return;
  }
  else
  {
    // There are rooms left in the area
    $response = array(
      "code" => -1,
      "message" => get_vocab("room_in_area")
    );
    echo json_encode($response);
    return;
  }
}

throw new \Exception ("Unknown type");

