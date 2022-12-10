<?php

namespace WebsocketApi;

use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;

class WebsocketApiServer
{
  private $config = [];

  private $timers = [];

  public function config(array $config = []) {
    $this->config = $config;

    if (
        empty($this->config['db_host']) || 
        empty($this->config['db_port']) ||
        empty($this->config['db_name']) ||
        empty($this->config['db_user']) ||
        empty($this->config['db_password'])
    ) {
      throw new \Exception('You have to specify full database connection data');  
    }

    $this->dispatcher = new RequestDispatcher(
      $this->config['db_host'],
      $this->config['db_port'],
      $this->config['db_name'],
      $this->config['db_user'],
      $this->config['db_password']
    );
  }

  public function start() {

    if (empty($this->config['connection_address']) && !strpos($this->config['connection_address'], ':')) {
      throw new \Exception('connection_address ws://address:port must be set in config');  
    }

    $context = [];

    if (!empty($this->config['ssl_cert'])) {
      $context = [
        'ssl' => [
          'local_cert'  => $this->config['local_cert'],
          'local_pk'    => $this->config['local_pk'],
          'verify_peer' => $this->config['verify_peer'],
        ],
      ];
    }

    // Create a Websocket server
    $notes_worker = new Worker('websocket://' . $this->config['connection_address'], $context);
    
    if (!empty($this->config['local_cert'])) {
      $notes_worker->transport = 'ssl';
    }

    // Emitted when new connection come
    $notes_worker->onConnect = function (TcpConnection $connection) {
      echo "New connection\n";

      $this->dispatcher->initHandlersArray($connection);

      $this->ping($connection);
    };

    // Emitted when data received
    $notes_worker->onMessage = function (TcpConnection $connection, $data) {
      $request = json_decode($data, true);

      echo 'Request: ' . $data . "\r\n";

      if (is_array($request)) {
        $payload_data = $this->dispatcher->dispatch($connection, $request);
      } else {
        $payload_data = [
          'error' => true,
          'message' => 'Request incorrect',
        ];
      }

      $connection->send(json_encode($payload_data, JSON_UNESCAPED_UNICODE));
    };

    // Emitted when connection closed
    $notes_worker->onClose = function (TcpConnection $connection) {
      echo "Connection closed\n";

      $this->dispatcher->destroyHandlersArray($connection);

      Timer::del($this->timers[$connection->id]);
    };

    // Run worker
    Worker::runAll();      
  }

  private function ping(TcpConnection $connection)
  {
    $time = isset($this->config['ping_time']) ? $this->config['ping_time'] : 20 ;
    $this->timers[$connection->id] = Timer::add($time, function() use ($connection) {
      echo "send ping to connection " . $connection->id . " \n";

      $connection->send(json_encode(["operation" => "ping"]));
    });
  }

  public function __destruct()
  {
    foreach (array_keys($this->timers) as $id) {
      Timer::del($this->timers[$id]);
    }

    $this->dispatcher->destroyAllHandlersArray();
  }
}
