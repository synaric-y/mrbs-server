<?php

namespace MRBS\Ldap;

class SyncLDAPResult
{
  public $group_insert = 0;
  public $group_update = 0;
  public $group_delete = 0;
  public $group_unbind = 0;

  public $user_insert = 0;
  public $user_update = 0;
  public $user_delete = 0;
  public $user_unbind = 0;
}
