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

$TAG = "[ad_connect] ";
echo $TAG, "start sync-----------------------------";

$connection = new Connection([
  'hosts' => ['172.16.88.180'],
  'port' => 389,
  'base_dn' => 'OU=BCC,DC=businessconnectchina,DC=com',
  'username' => 'CN=meet.ldap,OU=LDAP,DC=businessconnectchina,DC=com',
  'password' => '9CiJT@K8%3',
]);

// Add the connection into the container:
Container::addConnection($connection);

// Query Groups
$totalGroup = 0;
$fmtGroupList = [];
try {
  $entries = Group::query()->paginate(1000);
  $result = $entries->all();
  foreach ($result as $group) {
    $fmtGroup = handleGroup($group, $totalGroup);
    if (!empty($fmtGroup)) {
      $fmtGroupList[$fmtGroup['guid']] = $fmtGroup;
    }
  }
} catch (Exception $e) {
  echo $TAG, $e->getMessage(), PHP_EOL;
}
echo $TAG, "handle group: ", $totalGroup, PHP_EOL;

// Query Users
$totalUser = 0;
$fmtUserList = [];
try {
  $entries = User::query()
    ->select(['name', 'mail', 'title', 'userAccountControl'])
    ->in('OU=BCC,DC=businessconnectchina,DC=com')
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

echo $TAG, "handle user: ", $totalUser, PHP_EOL;
echo $TAG, "end sync-------------------------------";


function handleUser(User $user, &$total, array $originGroupList)
{
  global $TAG;
  $result = array();
  $result['guid'] = GUIDtoStr($user->getObjectGuid());
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
  $result['parent'] = join(',', $parentGroup);
  $result['disabled'] = $user->isDisabled();
  $result['is_delete'] = $user->isDeleted();
  $total += 1;

  return $result;
}

function handleGroup(Group $group, &$totalGroup)
{
  $result = array();
  $result['name'] = $group->getName();
  $result['guid'] = GUIDtoStr($group->getObjectGuid());
  $parent = array();

  $groupList = $group->groups()->getResults()->all();
  if (!empty($groupList)) {
    foreach ($groupList as $p) {
      $parent[] = GUIDtoStr($p->getObjectGuid());
    }
  }
  $result['parent'] = join(',', $parent);
  $result['is_delete'] = $group->isDeleted();

  $totalGroup += 1;
  return $result;
}

function GUIDtoStr($binary_guid)
{
  $unpacked = unpack('Va/v2b/n2c/Nd', $binary_guid);
  return sprintf('%08X-%04X-%04X-%04X-%04X%08X', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
}
