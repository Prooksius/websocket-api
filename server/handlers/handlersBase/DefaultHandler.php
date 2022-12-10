<?php

namespace WebsocketApi\handlers\handlersBase;

use WebsocketApi\mysql\MySqlConnection;

class DefaultHandler
{
  protected $entity = "";

  public $db = null;

  protected $user = null;
  
  public function __construct(MySqlConnection $db)
  {
    $this->db = $db;
  }

  public function handle(array $request)
  {

    $method = !empty($request['method']) ? $request['method'] : 'method_not_found';
    $params = !empty($request['params']) ? $request['params'] : [];
    $token  = !empty($request['token'])  ? $request['token']  : '';

    echo 'Method: ' . $method . "\r\n";

    $payload_data = [
      'entity' => $this->entity,
      'method' => $method,
    ];

    if (!method_exists($this, $method)) {
      $payload_data['status'] = 'error';
      $payload_data['message'] = 'method not found';
      return $payload_data;
    }

    try {
      $this->authorization($method, $token);
      $data = $this->$method($params, $token);
      $payload_data['status'] = 'success';
      $payload_data['data'] = $data;
    } catch (\Exception $e) {
      $payload_data['status'] = 'error';
      $payload_data['message'] = $e->getMessage();
    }

    return $payload_data;
  }

  protected function authorization(string $method, string $token)
  {
    return true;
  }
}