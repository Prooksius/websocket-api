<?php

namespace WebsocketApi;

use WebsocketApi\mysql\MySqlConnection;
use Workerman\Connection\TcpConnection;

class RequestDispatcher
{

  private $db = null;

  private $handlers = [
    'bot' => 'WebsocketApi\handlers\Bot',
    'country' => 'WebsocketApi\handlers\Country',
    'customer' => 'WebsocketApi\handlers\Customer',
  ];

  private $requests_per_minute = 200;

  private $handlersArr = [];
  private $requests = [];
  private $exeeds = [];
  private $bans = [];

  public function __construct( string $db_host, int $db_port, string $db_name, string $db_user, string $db_password)
  {
    $this->db = new MySqlConnection($db_host, $db_port, $db_user, $db_password, $db_name); 
  }

  private function cleanData($data) {
      if (is_array($data)) {
          foreach ($data as $key => $value) {
              unset($data[$key]);

              $data[self::cleanData($key)] = $this->cleanData($value);
          }
      } else {
          $data = htmlspecialchars($data, ENT_COMPAT, 'UTF-8');
      }

      return $data;
  }

  private function checkRequestsPerMinute(TcpConnection $connection) 
  {
    $id = $connection->getRemoteAddress();

    if (!isset($this->requests[$id])) {
      $this->requests[$id] = array();
    }
    
    // Add current token
    $this->requests[$id][] = time();

    // Expire old tokens
    while (reset($this->requests[$id]) < time() - 60) {
      array_shift($this->requests[$id]);
    }

    if (count($this->requests[$id]) > $this->requests_per_minute) {
      if (isset($this->exeeds[$id])){
        $this->exeeds[$id]++;
        if ($this->exeeds[$id] > 10) {
          $this->bans[$id] = time();
        }
      } else {
        $this->exeeds[$id] = 1;
      }
      $connection->close('Количество запросов в минуту (' . $this->requests_per_minute . ') превышено');
    }
  }

  public function initHandlersArray(TcpConnection $connection) 
  {
    $id = $connection->getRemoteAddress();

    if (isset($this->bans[$id])) {
      if (time() - $this->bans[$id] < 600) {
        $connection->close('Вы заблокированы за превышение максимального количества запросов в минуту');
      } else {
        unset($this->bans[$id]);
      }
    }

    $this->destroyHandlersArray($connection);
    $this->handlersArr[$id] = [];
  }

  public function destroyHandlersArray(TcpConnection $connection) 
  {
    $id = $connection->getRemoteAddress();

    if (empty($this->handlersArr[$id]) || !is_array($this->handlersArr[$id])) {
      return; 
    }

    foreach (array_keys($this->handlersArr[$id]) as $handler_name) {
      unset($this->handlersArr[$id][$handler_name]);
    }
    unset($this->handlersArr[$id]);
  }

  public function destroyAllHandlersArray() 
  {
    foreach (array_keys($this->handlersArr) as $id) {
      foreach (array_keys($this->handlersArr[$id]) as $handler_name) {
        unset($this->handlersArr[$id][$handler_name]);
      }
      unset($this->handlersArr[$id]);
    }
  }

  public function dispatch(TcpConnection $connection, array $request)
  {

    $this->checkRequestsPerMinute($connection);

    $request = $this->cleanData($request);

    $id = $connection->getRemoteAddress();

    $handler_name = !empty($request['entity']) ? $request['entity'] : '';
    if (!$handler_name || !isset($this->handlers[$handler_name])) {
      return [
        'entity' => $handler_name,
        'status' => 'error',
        'message' => 'entity not found',
      ];
    }

    if (empty($this->handlersArr[$id][$handler_name])) {
      $handler_class = $this->handlers[$handler_name];
      $this->handlersArr[$id][$handler_name] = new $handler_class($this->db);
    }

    return $this->handlersArr[$id][$handler_name]->handle($request);
  }
}