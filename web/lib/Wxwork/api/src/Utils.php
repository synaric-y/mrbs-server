<?php




function get_access_token(string $corpid, string $secret)
{
  $redis = \MRBS\RedisConnect::newInstance();
  $string = hash("MD5", $corpid . $secret);
  $access_token = $redis->get($string);
  if (!empty($access_token)) return $access_token;
  $file = fopen("./mutex_lock.lock", "w+");
  if (flock($file, LOCK_EX)) {
    if ($redis->get($string)) {
      try{
        $result = refresh_access_token($corpid, $secret);
        if($result === false || $result === -1) return $result;
      }catch (\Exception $e){
        return -1;
      } finally {
        flock($file, LOCK_UN);
        fclose($file);
      }
    }
  }
  return $access_token;
}

function refresh_access_token(string $corpid, string $secret)
{
  $redis = \MRBS\RedisConnect::newInstance();
  $string = hash("MD5", $corpid . $secret);
  try {
    $url = HttpUtils::MakeUrl(
      "/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$secret}");

    $jspRawStr = HttpUtils::httpGet($url);
    if (empty($jspRawStr)) return false;
    $data = json_decode($jspRawStr, true);
    $errCode = intval($data['errcode']);
    if ($errCode != 0) {
      return $errCode;
    } else {
      $access_token = $data['access_token'];
      $redis->set($string, $access_token, ['ex' => 7000]);
    }
  } catch (RedisException $e) {
    return -1;
  }
  return true;
}

function get_user_info(string $code)
{
  $redis = \MRBS\RedisConnect::newInstance();

}
