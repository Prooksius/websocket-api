<?php

require_once __DIR__ . '/vendor/autoload.php';

use WebsocketApi\WebsocketApiServer;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

$api_server = new WebsocketApiServer();

$api_server->config([

  //db connetion
  'db_host' => 'localhost',
  'db_port' => 3306,
  'db_name' => 'db_name',
  'db_user' => 'db_user',
  'db_password' => 'db_password',

  //websocket address and port
  'connection_address' => '0.0.0.0:2380',

  // ping_time in seconds
  'ping_time' => 20,
  
  // ssl
  //'local_cert' => '/home/admin/conf/web/ssl.proksi-design.ru.crt',
  //'local_pk' => '/home/admin/conf/web/ssl.proksi-design.ru.key',
  //'verify_peer' => false,
]);

$api_server->start();
