<?php

namespace MRBS;

class DBHelper
{

  public static $TAG = "[DBHelper] ";

  static public function exec($sql)
  {
    try {
//      echo DBHelper::$TAG, $sql, PHP_EOL;
      db()->command($sql);
    } catch (\Exception $e) {
      echo $e->getMessage(), PHP_EOL;
    }
  }

  static public function query_array($sql)
  {
    try {
//      echo DBHelper::$TAG, $sql, PHP_EOL;
      return db()->query_array($sql);
    } catch (\Exception $e) {
      echo $e->getMessage(), PHP_EOL;
    }
    return array();
  }

  static public function query($sql)
  {
    try {
      $result = array();
      $r = db()->query($sql);
      while ($row = $r->next_row_keyed()) {
        $result[] = $row;
      }
      return $result;
    } catch (\Exception $e) {
      echo $e->getMessage(), PHP_EOL;
    }
    return array();
  }

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
//      echo DBHelper::$TAG, $sql, PHP_EOL;
      db()->command($sql);
    } catch (Exception $e) {
      echo $e->getMessage(), PHP_EOL;
      return false;
    }

    return true;
  }

  static public function delete($table, $where)
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

    try {
      $sql = "delete from `$table` where $tempWhere";
//      echo DBHelper::$TAG, $sql, PHP_EOL;
      db()->command($sql);
    } catch (\Exception $e) {
      echo $e->getMessage(), PHP_EOL;
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
//    echo DBHelper::$TAG, $sql, PHP_EOL;
    $res = db()->query($sql);
    return ($res->count() == 0) ? null : $res->next_row_keyed();
  }

  static public function insert($table, $row)
  {
    $fields = '';
    $values = '';
    foreach ($row as $key => $value) {
      $fields .= "`" . $key . "`,";
      $values .= "'" . addslashes($value) . "',";
    }
    $sql = "insert into `" . $table . "` (" . substr($fields, 0, -1) . ") values (" . substr($values, 0, -1) . ")";
    db()->command($sql);
    return true;
  }

  static public function insert_id($table, $col)
  {
    return db()->insert_id($table, $col);
  }
}
