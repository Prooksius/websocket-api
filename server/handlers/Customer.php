<?php

namespace WebsocketApi\handlers;

use WebsocketApi\handlers\handlersBase\PrivateHandler;

class Customer extends PrivateHandler
{
  protected $entity = 'customer';

  protected $authMethods = ['identity'];
  
  protected function getUser(int $user_id)
  {

    $customer = $this->db->select()
      ->from('customer')
      ->where('id = :user_id AND status = 1')
      ->bindValues(['user_id' => $user_id])
      ->row();

    unset($customer['password_hash']);

    return $customer;
  }

  public function login(array $params)
  {
		if (!isset($params['login']) || !isset($params['password'])) {
      throw new \Exception('login и password обязательны');
    }

    $user = $this->db->select()
      ->from('customer')
      ->where('login = :login')
      ->bindValues(['login' => $params['login']])
      ->row();

    if (!$user || !$this->validatePassword($params['password'], $user['password_hash'])) {
      throw new \Exception('неверные login или password');
    };

    echo 'user_id: ' . $user['id'] . "\r\n";
    return [
      'token' => $this->issueAccessToken((int)$user['id']),
    ];
  }

  public function register(array $params)
  {
		if (!isset($params['login']) || !isset($params['password'])) {
      throw new \Exception('login и password обязательны');
    }
		if (!isset($params['name'])) {
      throw new \Exception('Представьтесь, пожалуйста');
    }
		if (!isset($params['email'])) {
      throw new \Exception('Укажите ваш email, пожалуйста');
    }

    $user = $this->db->select()
      ->from('customer')
      ->where('login = :login')
      ->bindValues(['login' => $params['login']])
      ->row();

    if ($user) {
      throw new \Exception('Пользователь с таким login уже существует');
    };

    $user = $this->db->select()
      ->from('customer')
      ->where('email = :email')
      ->bindValues(['email' => $params['email']])
      ->row();

    if ($user) {
      throw new \Exception('Пользователь с таким email уже существует');
    };

    $insert_id = $this->db->insert('customer')->cols([
      'login' => $params['login'], 
      'name' => $params['name'], 
      'email' => $params['email'], 
      'status' => 1,
      'created_at' => time(),
      'updated_at' => time(),
      'password_hash' => $this->generatePasswordHash($params['password']) 
    ])->query();

    echo 'New user_id: ' . $insert_id . "\r\n";

    return [
      'message' => 'Регистрация успешна. Теперь вы можете войти в свой аккаунт.',
    ];
  }

  public function identity(array $params)
  {
    return [
      'user' => $this->user,
    ];
  }
}