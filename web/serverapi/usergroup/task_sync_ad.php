<?php

namespace MRBS\Ldap;

require dirname(__DIR__, 3) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . "/defaultincludes.inc";
require_once dirname(__DIR__, 2) . "/functions_table.inc";
require_once dirname(__DIR__, 2) . "/mrbs_sql.inc";

ini_set('display_errors', 1);            //错误信息
ini_set('display_startup_errors', 1);    //php启动错误信息
error_reporting(E_ALL);

use Exception;
use LdapRecord\Container;
use LdapRecord\Connection;
use LdapRecord\Models\Entry;
use LdapRecord\Models\ActiveDirectory\User;
use LdapRecord\Models\ActiveDirectory\Group;
use LdapRecord\Models\Collection;
use MRBS\DBHelper;
use function MRBS\_tbl;
use function MRBS\db;
use function MRBS\generate_global_uid;


syncAD();

function syncAD()
{
  $TAG = "[ad_connect] ";
  $CREATE_SOURCE = "ad";
  $SYNC_CODE = md5(uniqid('', true));
  echo $TAG, "start sync-----------------------------";

  $config = DBHelper::one(_tbl("system_variable"), "1=1");
  if (empty($config)) {
    echo $TAG, "config is empty, stop sync", PHP_EOL;
    return;
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
  $entries = Group::query()->paginate();
  $result = $entries->all();
  foreach ($result as $group) {
    $fmtGroup = handleGroup($group, $totalGroup);
    if (!empty($fmtGroup)) {
      $fmtGroupList[$fmtGroup['third_id']] = $fmtGroup;
    }
  }
  echo $TAG, "handle group: ", $totalGroup, PHP_EOL;

  // 2.Query Users
  $totalUser = 0;
  $fmtUserList = [];
  $entries = User::query()
    ->select(['name', 'mail', 'title', 'userAccountControl'])
    ->where([
      ['objectClass', '=', 'person'],
      ['mail', '*']
    ])
    ->paginate();
  $userList = $entries->all();
  foreach ($userList as $p) {
    try {
      $fmtUser = handleUser($p, $totalUser, $fmtGroupList);
      if (!empty($fmtUser)) {
        $fmtUserList[] = $fmtUser;
      }

    } catch (Exception $e) {
      echo $TAG, $e->getMessage(), PHP_EOL;
    }
  }

  $localGroupList = [];
  // 3.Merge Groups
  foreach ($fmtGroupList as $remoteGroup) {
    // Query exist data
    try {
      $thirdId = $remoteGroup['third_id'];
      if (empty($thirdId)) {
        continue;
      }
      $localGroup = DBHelper::one(_tbl("user_group"), "third_id = '$thirdId'");
      if (empty($localGroup)) {
        // Insert new data, since third_id not exists
        $insertGroup = array_merge($remoteGroup);
        unset($insertGroup['_third_parent_id']);
        $insertGroup['source'] = $CREATE_SOURCE;
        $insertGroup['sync_state'] = 1;
        $insertGroup['last_sync_time'] = time();
        $insertGroup['sync_version'] = $SYNC_CODE;

        DBHelper::insert(_tbl("user_group"), $insertGroup);
        $insertId = DBHelper::insert_id(_tbl("user_group"), "id");
        $insertGroup['localId'] = $insertId;
        $localGroupList[$insertGroup['third_id']] = $insertGroup;
      } else {
        // Merge and update existing data
        $updateGroup = array_merge($remoteGroup);
        unset($updateGroup['_third_parent_id']);
        $updateGroup['source'] = $CREATE_SOURCE;
        $updateGroup['sync_state'] = 1;
        $insertGroup['last_sync_time'] = time();
        $insertGroup['sync_version'] = $SYNC_CODE;

        DBHelper::update(_tbl("user_group"), $updateGroup, "third_id = '$thirdId'");
        $localGroupList[$localGroup['third_id']] = $localGroup;
      }
    } catch (Exception $e) {
      echo $TAG, $e->getMessage(), PHP_EOL;
    }
  }

  // 4.Merge Users
  $localUserList = [];
  foreach ($fmtUserList as $remoteUser) {
    $thirdId = $remoteUser['third_id'];
    if (empty($thirdId)) {
      continue;
    }
    $localUser = DBHelper::one(_tbl("users"), "third_id = '$thirdId'");
    if (empty($localUser)) {
      $insertUser = array_merge($remoteUser);
      unset($insertUser['_third_parent_id']);
      $insertUser['source'] = $CREATE_SOURCE;
      $insertUser['sync_state'] = 1;
      $insertUser['last_sync_time'] = time();
      $insertUser['sync_version'] = $SYNC_CODE;
      $insertUser['password'] = $config['default_password_hash'];

      DBHelper::insert(_tbl("users"), $insertUser);
      $insertId = DBHelper::insert_id(_tbl("users"), "id");
      $insertUser['localId'] = $insertId;
      $localUserList[$insertUser['third_id']] = $insertUser;
    } else {
      $updateUser = array_merge($remoteUser);
      unset($updateUser['_third_parent_id']);
      $updateUser['source'] = $CREATE_SOURCE;
      $updateUser['sync_state'] = 1;
      $updateUser['last_sync_time'] = time();
      $updateUser['sync_version'] = $SYNC_CODE;

      DBHelper::update(_tbl("users"), $updateUser, "third_id = '$thirdId'");
      $localUserList[$localUser['third_id']] = $localUser;
    }
  }

  // 5.Resolve user-group group-group relationship
  DBHelper::delete(_tbl("g2g_map"), "source = '$CREATE_SOURCE'");
  DBHelper::delete(_tbl("u2g_map"), "source = '$CREATE_SOURCE'");

  foreach ($localGroupList as $key => $localGroup) {
    try {
      $parentNodeList = [];
      recursiveGetParentList($localGroupList, $localGroup, $parentNodeList, 0);
      if (empty($parentNodeList)) {
        continue;
      }
      foreach ($parentNodeList as $pNode) {
        $insertG2G = array();
        $insertG2G['group_id'] = $localGroup['id'];
        $insertG2G['parent_id'] = $pNode['node']['id'];
        $insertG2G['deep'] = $pNode['deep'];
        $insertG2G['source'] = $CREATE_SOURCE;
        DBHelper::insert(_tbl("g2g_map"), $insertG2G);
      }
    } catch (Exception $e) {
      echo $TAG, $e->getMessage(), PHP_EOL;
    }
  }

  foreach ($localUserList as $key => $localUser) {
    try {
      $parentNodeList = [];
      recursiveGetParentList($localUserList, $localUser, $parentNodeList, 0);
      if (empty($parentNodeList)) {
        continue;
      }
      foreach ($parentNodeList as $pNode) {
        $insertU2G = array();
        $insertU2G['user_id'] = $localUser['id'];
        $insertU2G['parent_id'] = $pNode['node']['id'];
        $insertU2G['deep'] = $pNode['deep'];
        $insertU2G['source'] = $CREATE_SOURCE;
        DBHelper::insert(_tbl("u2g_map"), $insertU2G);
      }
    } catch (Exception $e) {
      echo $TAG, $e->getMessage(), PHP_EOL;
    }
  }

  echo $TAG, "handle user: ", $totalUser, PHP_EOL;
  echo $TAG, "end sync-------------------------------";
}

function recursiveGetParentList($nodeMap, $node, $resultList, $deep)
{
  $pidString = $node['third_parent_id'];
  $pidList = explode(",", $pidString);
  if (empty($pidList)) {
    return array();
  }
  if ($deep != 0) {
    $resultList[] = array(
      'deep' => 1,
      'node' => $node
    );
  }
  foreach ($pidList as $pid) {
    if (empty($pid)) {
      continue;
    }
    $parentNode = $nodeMap[$pid];
    if (empty($parentNode)) {
      continue;
    } else {
      recursiveGetParentList($nodeMap, $parentNode, $resultList, $deep + 1);
    }
  }
}

function handleUser(User $user, &$total, array $originGroupList)
{
  global $TAG;
  $result = array();
  $result['third_id'] = GUIDtoStr($user->getObjectGuid());
  $result['name'] = $user->getName();
  $result['email'] = $user->getAttribute('mail')[0];
//  $result['title'] = $user->getAttribute('title')[0];

  $groupList = $user->groups()->getResults()->all();
  $parentGroup = [];
  foreach ($groupList as $group) {
    $groupId = GUIDtoStr($group->getObjectGuid());
    if (empty($originGroupList[$groupId])) {
      $gn = $group->getName();
      echo $TAG, "unexpected group: $groupId, name: $gn", PHP_EOL;
    }
    $parentGroup[] = $groupId;
  }
  $result['third_parent_id'] = join(',', $parentGroup);
  $result['_third_parent_id'] = $parentGroup;
  $result['disabled'] = $user->isDisabled() || $user->isDeleted() ? 1 : 0;
  $total += 1;

  return $result;
}

function handleGroup(Group $group, &$totalGroup)
{
  $result = array();
  $result['name'] = $group->getName();
  $result['third_id'] = GUIDtoStr($group->getObjectGuid());
  $parent = array();

  $groupList = $group->groups()->getResults()->all();
  if (!empty($groupList)) {
    foreach ($groupList as $p) {
      $parent[] = GUIDtoStr($p->getObjectGuid());
    }
  }
  $result['third_parent_id'] = join(',', $parent);
  $result['_third_parent_id'] = $parent;
  $result['disabled'] = $group->isDeleted() ? 1 : 0;

  $totalGroup += 1;
  return $result;
}

function GUIDtoStr($binary_guid)
{
  $unpacked = unpack('Va/v2b/n2c/Nd', $binary_guid);
  return sprintf('%08X-%04X-%04X-%04X-%04X%08X', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
}
