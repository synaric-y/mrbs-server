<?php

namespace MRBS;


use Redis;

class RedisConnect
{
  private static $redis;

  /**
   * @throws \RedisException
   */
  private function __construct()
  {
  }

  private static function newInstance(): void
  {
    global $redis_host, $redis_port;

    if (!empty(self::$redis[$redis_host . ":" . (string)$redis_port])){
      return;
    }
    $file = fopen("./mutex.lock", "w+");
    if (flock($file, LOCK_EX)){
      try{
        if (empty(self::$redis[$redis_host . ":" . (string)$redis_port])) {
          self::$redis = new Redis();
          self::$redis->connect($redis_host, $redis_port);
        }
      } catch (\RedisException $e) {
      } finally {
        flock($file, LOCK_UN);
        fclose($file);
      }
    }
  }

  public static function setex($key, $value, $expire = 0)
  {
    if (empty(self::$redis)){
      self::newInstance();
    }
    self::$redis->setex($key, $expire, $value);
  }

  public static function set($key, $value)
  {
    if (empty(self::$redis)){
      self::newInstance();
    }
    self::$redis->set($key, $value);
  }

  public static function get($key){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->get($key);
  }

  public static function del($key){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->del($key);
  }

  public static function getKeys($pattern = '*'){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->keys($pattern);
  }

  public static function clear(){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->flushALL();
  }


  //TODO temporarily not provide select, because RedisConnect cannot be created and redis
  //  connect is the only one, change db may cause other function failed to use redis
//  public static function selectDB($database){
//    if (empty(self::$redis)){
//      self::newInstance();
//    }
//    return self::$redis->select($database);
//  }

  public static function zADD($name, $member, $score){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->zADD($name, $score, $member);
  }

  public static function zREM($name, $member){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->zREM($name, $member);
  }

  public static function zRange($name, $begin, $end, $option = ["withscore" => false]){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->zRange($name, $begin, $end, $option);

  }

  public static function zRangeByScore($name, $begin, $end, $option = ["withscore" => false]){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->zRangeByScore($name, $begin, $end, $option);
  }

  public static function zScore($name, $member){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->zScore($name, $member);
  }

  public static function zRank($name, $member){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->zRank($name, $member);
  }

  public static function zRemRangeByScore($name, $begin, $end){
    if (empty(self::$redis)){
      self::newInstance();
    }
    return self::$redis->zRemRangeByScore($name, $begin, $end);
  }
}
