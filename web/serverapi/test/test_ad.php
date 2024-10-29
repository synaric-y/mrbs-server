<?php

namespace MRBS;

use LdapRecord\Connection;

/*
 * Test AD connection.
 * @Param
 * server:        Server name of the AD.
 * port:          Port of the server.
 * base_dn:       A 'Distinguished Name' is a string based identifier in LDAP that is used to indicate hierarchy.
 * username:      To connect to your LDAP server, a username and password is required to be able to query and run operations on your server(s).
 * password:      To connect to your LDAP server, a username and password is required to be able to query and run operations on your server(s).
 * @Return
 * code == 0 means success, otherwise failed.
 */

$server = $_POST['server'];
$port = $_POST['port'];
$base_dn = $_POST['base_dn'];
$username = $_POST['username'];
$password = $_POST['password'];

$connection = new Connection([
  'hosts' => [$server],
  'port' => $port,
  'base_dn' => $base_dn,
  'username' => $username,
  'password' => $password,
]);

try {
  $connection->connect();

  ApiHelper::success(array());
} catch (\LdapRecord\Auth\BindException $e) {
  $error = $e->getDetailedError();
  ApiHelper::fail($error->getDiagnosticMessage(), ApiHelper::LDAP_CONNECT_ERROR);
}

