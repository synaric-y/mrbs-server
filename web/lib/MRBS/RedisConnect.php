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

  public static function newInstance()
  {
    global $redis_host, $redis_port;

    if (!empty(self::$redis)){
      return self::$redis;
    }
    $file = fopen("./mutex.lock", "w+");
    if (flock($file, LOCK_EX)){
      try{
        if (empty($redis)) {
          self::$redis = new Redis();
          self::$redis->connect($redis_host, $redis_port);
        }
      } finally {
        flock($file, LOCK_UN);
        fclose($file);
      }
    }
    return self::$redis;
  }
}
