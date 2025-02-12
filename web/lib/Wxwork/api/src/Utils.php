<?php




function get_access_token(string $corpid, string $secret)
{
  $string = hash("MD5", $corpid . $secret);
  $access_token = \MRBS\RedisConnect::get($string);
  if (!empty($access_token)) return $access_token;
  $file = fopen("./mutex_lock.lock", "w+");
  if (flock($file, LOCK_EX)) {
    $access_token = \MRBS\RedisConnect::get($string);
    try{
      if (empty($access_token)) {
        $result = refresh_access_token($corpid, $secret);
        if ($result === false || $result === -1) return $result;
      } else {
        return $access_token;
      }
    }catch (\Exception $e){
      return -1;
    } finally {
      flock($file, LOCK_UN);
      fclose($file);
    }
  } else {
    \MRBS\log_by_name('wxwork_', 'flock failed!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
  }
  return $access_token;
}

function refresh_access_token(string $corpid, string $secret)
{
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
      \MRBS\RedisConnect::setex($string, $access_token, 7000);
    }
  } catch (RedisException $e) {
    return -1;
  }
  return 0;
}

function get_user_info(string $code)
{

}
