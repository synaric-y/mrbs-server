<?php

namespace MRBS;

class DBHelper
{

  static public function update($table, $data, $where)
  {
    $tempWhere = '';
    if (is_array($where)) {
      $tempWhere = '';
      foreach ($where as $key => $value) {

        $tempWhere .= empty($tempWhere) ? "  `" . $key . "`= '" . $value . "' " : " and `" . $key . "`= '" . $value . "' ";

      }
    } elseif (empty($where)) {
      $tempWhere = '';
    } else {
      $tempWhere = '  ' . $where;
    }

    if (is_array($data)) {
      if (count($data) == 0) {
        return false;
      }
    } else {
      if (empty($data)) {
        return false;
      }
    }
    try {
      $sqlud = '';
      if (is_string($data)) {
        $sqlud = $data . ' ';
      } else {
        foreach ($data as $key => $value) {
          // $sqlud .= "`$key`"."= '".$value."',";

          $sqlud .= "`$key`" . "= '" . $value . "',";


        }
      }
      $sql = "update `" . $table . "` set " . substr($sqlud, 0, -1) . " where " . $tempWhere;
      db()->command($sql);
    } catch (Exception $e) {
      echo $e->getTraceAsString(), PHP_EOL;
      return false;
    }

    return true;
  }

  static public function one($table, $where, $select = "*")
  {
    $tempWhere = '';
    if (is_array($where)) {
      $tempWhere = '';
      foreach ($where as $key => $value) {

        $tempWhere .= empty($tempWhere) ? "  `" . $key . "`= '" . $value . "' " : " and `" . $key . "`= '" . $value . "' ";

      }
    } elseif (empty($where)) {
      $tempWhere = '';
    } else {
      $tempWhere = '  ' . $where;
    }

    $sql = "select " . $select . " from `" . $table . "` where " . $tempWhere;
    $res = db()->query($sql);
    return ($res->count() == 0) ? null : $res->next_row()[0];
  }
}
