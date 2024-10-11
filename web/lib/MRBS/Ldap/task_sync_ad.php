<?php
namespace MRBS\Ldap;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

use Exception;
use LdapRecord\Container;
use LdapRecord\Connection;
use LdapRecord\Models\Entry;
use LdapRecord\Models\ActiveDirectory\User;
use LdapRecord\Models\ActiveDirectory\Group;
use LdapRecord\Models\Collection;
use MRBS\DBHelper;
use function MRBS\_tbl;
use function MRBS\generate_global_uid;

$TAG = "[ad_connect] ";
$CREATE_SOURCE = "ad";
$SYNC_CODE = md5(uniqid('', true));
echo $TAG, "start sync-----------------------------";

$config = DBHelper::one(_tbl("config"), "1=1");

$connection = new Connection([
  'hosts' => ['172.16.88.180'],
  'port' => 389,
  'base_dn' => 'OU=BCC,DC=businessconnectchina,DC=com',
  'username' => 'CN=meet.ldap,OU=LDAP,DC=businessconnectchina,DC=com',
  'password' => '9CiJT@K8%3',
]);

// Add the connection into the container:
Container::addConnection($connection);

// 1.Query Groups
$totalGroup = 0;
$fmtGroupList = [];
try {
  $entries = Group::query()->paginate();
  $result = $entries->all();
  foreach ($result as $group) {
    $fmtGroup = handleGroup($group, $totalGroup);
    if (!empty($fmtGroup)) {
      $fmtGroupList[$fmtGroup['third_id']] = $fmtGroup;
    }
  }
} catch (Exception $e) {
  echo $TAG, $e->getMessage(), PHP_EOL;
}
echo $TAG, "handle group: ", $totalGroup, PHP_EOL;

// 2.Query Users
$totalUser = 0;
$fmtUserList = [];
try {
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
} catch (Exception $e) {
  echo $TAG, $e->getMessage(), PHP_EOL;
}

// 3.Merge Groups
foreach ($fmtGroupList as $remoteGroup) {
  // Query exist data
  try {
    $thirdId = $remoteGroup['third_id'];
    $localGroup = DBHelper::one(_tbl("user_group"), "third_id = '$thirdId'");
    if (empty($localGroup)) {
      // Insert new data, since third_id not exists
      $insertGroup = array_merge($remoteGroup);
      $insertGroup['source'] = $CREATE_SOURCE;
      $insertGroup['sync_state'] = 1;
      $insertGroup['last_sync_time'] = time();
      $insertGroup['sync_version'] = $SYNC_CODE;

      DBHelper::insert(_tbl("user_group"), $insertGroup);
    } else {
      // Merge and update existing data

    }
  } catch (Exception $e) {
    echo $TAG, $e->getMessage(), PHP_EOL;
  }
}

// 4.Merge Users

// 5.Resolve user-group group-group relationship

echo $TAG, "handle user: ", $totalUser, PHP_EOL;
echo $TAG, "end sync-------------------------------";


function handleUser(User $user, &$total, array $originGroupList)
{
  global $TAG;
  $result = array();
  $result['third_id'] = GUIDtoStr($user->getObjectGuid());
  $result['name'] = $user->getName();
  $result['mail'] = $user->getAttribute('mail')[0];
  $result['title'] = $user->getAttribute('title')[0];

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
  $result['disabled'] = $group->isDeleted() ? 1 : 0;

  $totalGroup += 1;
  return $result;
}

function GUIDtoStr($binary_guid)
{
  $unpacked = unpack('Va/v2b/n2c/Nd', $binary_guid);
  return sprintf('%08X-%04X-%04X-%04X-%04X%08X', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
}
