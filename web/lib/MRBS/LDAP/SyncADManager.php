<?php /** @noinspection SqlDialectInspection */

namespace MRBS\LDAP;

use Error;
use Exception;
use LdapRecord\Container;
use LdapRecord\Connection;
use LdapRecord\Models\ActiveDirectory\User;
use LdapRecord\Models\ActiveDirectory\Group;
use MRBS\DBHelper;
use MRBS\RedisConnect;
use MRBS\RedisKeys;
use function MRBS\_tbl;
use function MRBS\db;
use function MRBS\log_ad;
use function MRBS\resolve_user_group_count;

/**
 * Synchronize User and Group from AD(LDAP).
 */
class SyncADManager
{

  /*
   * Expire time for redis key which caches current task info
   */
  static $TASK_EXPIRE_SECONDS = 3600;
  /*
   * Batch count to report task progress
   */
  static $REPORT_INTERVAL = 200;
  /*
   * Record synchronization progress
   */
  private $progress = [];
  /*
   * Current synchronization batch version
   */
  private $sync_version = "";

  /**
   * Synchronize User and Group from AD(LDAP).
   */
  public function sync($sync_version) {
    try {
      $this->_syncAD($sync_version);
    } catch (\Throwable $e) {
      log_ad($e->getMessage());
      log_ad($e->getTraceAsString());

      $this->_reportFail();
    }
  }

  private function _syncAD($sync_version)
  {
    $CREATE_SOURCE = "ad";
    $this->sync_version = $sync_version ?? md5(uniqid('', true));
    $TABLE_GROUP = "user_group";
    $TABLE_USER = "users";
    $TABLE_U2G = "u2g_map";
    $TABLE_G2G = "g2g_map";

    // A runtime k-v map cache to save query times
    $localGroupList = [];
    $localUserList = [];
    $syncResult = new SyncLDAPResult();
    $this->_initProgress(8);

    log_ad("start sync-----------------------------");
    log_ad("start time: " . time());
    $config = DBHelper::one(_tbl("system_variable"), "1=1");
    if (empty($config)) {
      log_ad("config is empty, stop sync");
      return null;
    }

    $connection = new Connection([
      'hosts' => [$config['AD_server']],
      'port' => $config['AD_port'],
      'base_dn' => $config['AD_base_dn'],
      'username' => $config['AD_username'],
      'password' => $config['AD_password'],
    ]);

    // Add the connection into the container:
    Container::addConnection($connection);

    // 1.Query Groups
    $totalGroup = 0;
    $fmtGroupList = [];
    $entries = Group::query()->select(['name'])->paginate();
    $groupList = $entries->all();
    log_ad("query group: ", count($groupList));

    // 2.Query Users
    $totalUser = 0;
    $fmtUserList = [];
    $entries = User::query()
      ->select(['name', 'sAMAccountName', 'mail', 'title', 'userAccountControl'])
      ->where([
        ['objectClass', '=', 'person'],
        ['mail', '*']
      ])
      ->paginate();
    $userList = $entries->all();
    log_ad("query user: ", count($userList));


    // 3.Format Data
    $temp = 0;
    foreach ($groupList as $group) {
      $fmtGroup = $this->_handleGroup($group, $totalGroup);
      if (!empty($fmtGroup)) {
        $fmtGroupList[$fmtGroup['third_id']] = $fmtGroup;
      }
      $temp++;
      $this->_reportProgress(0, $temp, count($groupList));
    }
    $this->_reportProgress(0, count($groupList), count($groupList));
    log_ad("handle group: ", $totalGroup);

    $userAccountName = '';
    if (!empty($userList)) {
      $peek = $userList[0];
      if (!empty($peek['samaccountname'])) {
        $userAccountName = 'samaccountname';
      } else if (!empty($peek['mail'])) {
        $userAccountName = 'mail';
      } else {
        $userAccountName = 'name';
      }
    }
    $temp = 0;
    foreach ($userList as $p) {
      try {
        $option = array();
        $option['userAccountName'] = $userAccountName;
        $fmtUser = $this->_handleUser($p, $totalUser, $fmtGroupList, $option);
        if (!empty($fmtUser)) {
          $fmtUserList[] = $fmtUser;
        }

      } catch (Error $e) {
        log_ad($e->getMessage());
      }
      $temp++;
      $this->_reportProgress(1, $temp, count($userList));
    }
    $this->_reportProgress(1, count($userList), count($userList));
    log_ad("handle user: ", $totalUser);

    // 4.Merge Groups
    db()->begin();
    $temp = 0;
    foreach ($fmtGroupList as $remoteGroup) {
      $temp++;
      // Query exist data
      try {
        $thirdId = $remoteGroup['third_id'];
        if (empty($thirdId)) {
          continue;
        }
        $localGroup = DBHelper::one(_tbl($TABLE_GROUP), "third_id = '$thirdId' and source = '$CREATE_SOURCE'");

        $mergedGroup = array_merge($remoteGroup);
        unset($mergedGroup['_third_parent_id']);
        $mergedGroup['source'] = $CREATE_SOURCE;
        $mergedGroup['sync_state'] = 1;
        $mergedGroup['last_sync_time'] = time();
        $mergedGroup['sync_version'] = $this->sync_version;

        if (empty($localGroup)) {
          // Insert new data, since third_id not exists
          DBHelper::insert(_tbl($TABLE_GROUP), $mergedGroup);
          $insertId = DBHelper::insert_id(_tbl($TABLE_GROUP), "id");
          $mergedGroup['id'] = $insertId;
          $localGroupList[$mergedGroup['third_id']] = $mergedGroup;
          $syncResult->group_insert += 1;
        } else {
          // Merge and update existing data
          DBHelper::update(_tbl($TABLE_GROUP), $mergedGroup, "third_id = '$thirdId'  and source = '$CREATE_SOURCE'");
          $localGroupList[$localGroup['third_id']] = $localGroup;
          if ($mergedGroup['disabled'] != $localGroup['disabled']) {
            $syncResult->group_delete += 1;
          } else {
            $syncResult->group_update += 1;
          }
        }
      } catch (Error $e) {
        log_ad($e->getMessage());
      }
      $this->_reportProgress(2, $temp, count($fmtGroupList));
    }
    $this->_reportProgress(2, count($fmtGroupList), count($fmtGroupList));
    db()->commit();
    log_ad("merge groups: ", count($fmtGroupList));

    // 5.Merge Users
    db()->begin();
    $temp = 0;
    foreach ($fmtUserList as $remoteUser) {
      $temp++;
      $thirdId = $remoteUser['third_id'];
      if (empty($thirdId)) {
        continue;
      }
      $rName = $remoteUser['name'];
      $localUser = DBHelper::one(_tbl($TABLE_USER), "third_id = '$thirdId' or name = '$rName'");

      $mergedUser = array_merge($remoteUser);
      unset($mergedUser['_third_parent_id']);
      $mergedUser['source'] = $CREATE_SOURCE;
      $mergedUser['sync_state'] = 1;
      $mergedUser['level'] = 1;
      $mergedUser['last_sync_time'] = time();
      $mergedUser['sync_version'] = $this->sync_version;

      if (empty($localUser)) {
        $mergedUser['password_hash'] = $config['default_password_hash'];
        DBHelper::insert(_tbl($TABLE_USER), $mergedUser);
        $insertId = DBHelper::insert_id(_tbl($TABLE_USER), "id");
        $mergedUser['id'] = $insertId;
        $localUserList[$mergedUser['third_id']] = $mergedUser;
        $syncResult->user_insert += 1;
      } else {
        DBHelper::update(_tbl($TABLE_USER), $mergedUser, "third_id = '$thirdId' or name = '$rName'");
        $localUserList[$localUser['third_id']] = $localUser;
        if ($mergedUser['disabled'] != $localUser['disabled']) {
          $syncResult->user_delete += 1;
        } else {
          $syncResult->user_update += 1;
        }
      }

      $this->_reportProgress(3, $temp, count($fmtUserList));
    }
    $this->_reportProgress(3,  count($fmtUserList), count($fmtUserList));
    db()->commit();
    log_ad("merge users: ", count($fmtUserList));

    // 6.Resolve user-group and group-group relationship
    DBHelper::delete(_tbl($TABLE_G2G), "source = '$CREATE_SOURCE'");
    DBHelper::delete(_tbl($TABLE_U2G), "source = '$CREATE_SOURCE'");

    db()->begin();
    $temp = 0;
    foreach ($localGroupList as $key => $localGroup) {
      $temp++;
      try {
        $parentNodeList = [];
        $this->_recursiveGetParentList($localGroupList, $localGroup, $parentNodeList, 0);
        if (empty($parentNodeList)) {
          continue;
        }
//      log_ad("handle g2g: {$localGroup['name']}, parent: {$localGroup['third_parent_id']}";
        foreach ($parentNodeList as $pNode) {
          $insertG2G = array();
          $insertG2G['group_id'] = $localGroup['id'];
          $insertG2G['parent_id'] = $pNode['node']['id'];
          $insertG2G['deep'] = $pNode['deep'];
          $insertG2G['source'] = $CREATE_SOURCE;
          DBHelper::insert(_tbl($TABLE_G2G), $insertG2G);
        }
      } catch (Error $e) {
        log_ad($e->getMessage());
      }
      $this->_reportProgress(4, $temp, count($localGroupList));
    }
    $this->_reportProgress(4, count($localGroupList), count($localGroupList));
    db()->commit();
    log_ad("resolve g2g: ", count($localGroupList));

    db()->begin();
    $temp = 0;
    foreach ($localUserList as $key => $localUser) {
      $temp++;
      try {
        $parentNodeList = [];
        $this->_recursiveGetParentList($localGroupList, $localUser, $parentNodeList, 0);
        if (empty($parentNodeList)) {
          continue;
        }
        // log_ad("handle u2g: {$localUser['name']}, parent: {$localUser['third_parent_id']}");
        foreach ($parentNodeList as $pNode) {
          $insertU2G = array();
          $insertU2G['user_id'] = $localUser['id'];
          $insertU2G['parent_id'] = $pNode['node']['id'];
          $insertU2G['deep'] = $pNode['deep'];
          $insertU2G['source'] = $CREATE_SOURCE;
          DBHelper::insert(_tbl($TABLE_U2G), $insertU2G);
        }
      } catch (Error $e) {
        log_ad($e->getMessage());
      }
      $this->_reportProgress(5, $temp, count($localUserList));
    }
    $this->_reportProgress(5, count($localUserList), count($localUserList));
    db()->commit();
    log_ad("resolve u2g: ", count($localGroupList));

    // 7.Synchronize AD members into system-created groups
    $sysRelatedGroups = DBHelper::query("SELECT id, third_id FROM " . _tbl($TABLE_GROUP) . " WHERE sync_state = 1 AND source = 'system'");
    db()->begin();
    $temp = 0;
    foreach ($sysRelatedGroups as $sysRelatedGroup) {
      $temp++;
      $third_id = $sysRelatedGroup['third_id'];
      $targetGroup = $localGroupList[$third_id];
      if (empty($targetGroup)) {
        continue;
      }
      db()->command("DELETE FROM " . _tbl("u2g_map") . " WHERE parent_id = ? AND deep = 1 ", array($sysRelatedGroup['id']));
      $sql = "
        INSERT INTO "._tbl("u2g_map")." (user_id, parent_id, deep, source)
        SELECT DISTINCT user_id, ?, 1, 'system' FROM " . _tbl("u2g_map") . " WHERE parent_id = ?
      ";
      db()->command($sql, array($sysRelatedGroup['id'], $targetGroup['id']));

      $this->_reportProgress(6, $temp, count($sysRelatedGroups));
    }
    db()->commit();
    $this->_reportProgress(6, count($sysRelatedGroups), count($sysRelatedGroups));

    // 8.Resolve user count
    resolve_user_group_count();
    $this->_reportProgress(7, 1, 1);

    // 9.Query whether there are non-synchronized groups and users
    $usGroupResult = DBHelper::query_array("SELECT count(*) as count FROM " . _tbl($TABLE_GROUP) . " WHERE sync_state = 1 AND sync_version != '$this->sync_version'");
    $usUserResult = DBHelper::query_array("SELECT count(*) as count FROM " . _tbl($TABLE_USER) . " WHERE sync_state = 1 AND sync_version != '$this->sync_version'");
    $syncResult->group_unbind = $usGroupResult['count'] ?? 0;
    $syncResult->user_unbind = $usUserResult['count'] ?? 0;

    log_ad(json_encode($syncResult));
    log_ad("end time: " . time());
    log_ad("end sync-------------------------------");
    $this->_reportSuccess($syncResult);

    return $syncResult;
  }

  private function _recursiveGetParentList($nodeMap, $node, &$resultList, $deep)
  {
    $pidString = $node['third_parent_id'];
    $pidList = explode(",", $pidString);
    if ($deep != 0) {
      $resultList[] = array(
        'deep' => $deep,
        'node' => $node
      );
    }
    if (empty($pidString) || empty($pidList)) {
      $resultList[] = array(
        'deep' => $deep + 1,
        'node' => array(
          'id' => -1
        )
      );
      return;
    }
    foreach ($pidList as $pid) {
      if (empty($pid)) {
        continue;
      }
      $parentNode = $nodeMap[$pid];
      if (empty($parentNode)) {
        continue;
      } else {
        $this->_recursiveGetParentList($nodeMap, $parentNode, $resultList, $deep + 1);
      }
    }
  }

  private function _handleUser(User $user, &$total, array $originGroupList, $option)
  {
    $userAccountName = $option['userAccountName'];

    $result = array();
    $result['third_id'] = $this->_GUIDtoStr($user->getObjectGuid());
    $result['name'] = $user->getAttribute($userAccountName)[0];
    $result['display_name'] = $user->getName();
    $result['email'] = $user->getAttribute('mail')[0];
//  $result['title'] = $user->getAttribute('title')[0];

    $groupList = $user->groups()->getResults()->all();
    $parentGroup = [];
    foreach ($groupList as $group) {
      $groupId = $this->_GUIDtoStr($group->getObjectGuid());
      if (empty($originGroupList[$groupId])) {
        $gn = $group->getName();
        log_ad("unexpected group: $groupId, name: $gn");
      }
      $parentGroup[] = $groupId;
    }
    $result['third_parent_id'] = join(',', $parentGroup);
    $result['_third_parent_id'] = $parentGroup;
    $result['disabled'] = $user->isDisabled() || $user->isDeleted() ? 1 : 0;
    $total += 1;

    return $result;
  }

  private function _handleGroup(Group $group, &$totalGroup)
  {
    $result = array();
    $result['name'] = $group->getName();
    $result['third_id'] = $this->_GUIDtoStr($group->getObjectGuid());
    $parent = array();

    $groupList = $group->groups()->getResults()->all();
    if (!empty($groupList)) {
      foreach ($groupList as $p) {
        $parent[] = $this->_GUIDtoStr($p->getObjectGuid());
      }
    }
    $result['third_parent_id'] = join(',', $parent);
    $result['_third_parent_id'] = $parent;
    $result['disabled'] = $group->isDeleted() ? 1 : 0;

    $totalGroup += 1;
    return $result;
  }

  private function _GUIDtoStr($binary_guid)
  {
    $unpacked = unpack('Va/v2b/n2c/Nd', $binary_guid);
    return sprintf('%08X-%04X-%04X-%04X-%04X%08X', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
  }

  private function _initProgress($steps)
  {
    for ($i = 0; $i < $steps; $i++) {
      $this->progress[] = array(
        'total' => 0,
        'current' => 0
      );
    }
  }

  private function _reportProgress($step, $current, $total)
  {
    if (empty($this->progress[$step])) {
      $this->progress[] = array();
    }
    $p = $this->progress[$step];
    if (empty($p['total'])) {
      $this->progress[$step]['total'] = $total;
    }
    if (empty($p['current']) || $p['current'] == 0
      || $current - self::$REPORT_INTERVAL > $p['current']
      || $current == $total) {
      $this->progress[$step]['current'] = $current;
      $result = array(
        'sync_version' => $this->sync_version,
        'progress' => $this->progress,
        'complete' => 0
      );
      RedisConnect::setex(RedisKeys::$CURRENT_SYNC_AD_TASK, json_encode($result), self::$TASK_EXPIRE_SECONDS);
    }
  }

  private function _reportFail()
  {
    $task = RedisConnect::get(RedisKeys::$CURRENT_SYNC_AD_TASK);
    if (!empty($task)) {
      $task = json_decode($task, true);
      $task['complete'] = -1;
      RedisConnect::set(RedisKeys::$LAST_SYNC_AD, "" . time());
      RedisConnect::setex(RedisKeys::$CURRENT_SYNC_AD_TASK, json_encode($task), self::$TASK_EXPIRE_SECONDS);
    }
  }

  private function _reportSuccess($report)
  {
    $task = RedisConnect::get(RedisKeys::$CURRENT_SYNC_AD_TASK);
    if (!empty($task)) {
      $task = json_decode($task, true);
      $task['complete'] = 1;
      $task['report'] = $report;
      RedisConnect::set(RedisKeys::$LAST_SYNC_AD, "" . time());
      RedisConnect::setex(RedisKeys::$CURRENT_SYNC_AD_TASK, json_encode($task), self::$TASK_EXPIRE_SECONDS);
    }
  }
}
