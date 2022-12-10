<?php

namespace WebsocketApi\handlers;

use WebsocketApi\handlers\handlersBase\EntityHandler;

class Bot extends EntityHandler
{
  protected $entity = 'bot';
  protected $list_default_sort = 'sort_order';
  protected $list_default_order = 'ASC';
  protected $list_default_limit = 10;

  protected function getRowsCount()
  {
    return $this->db->select('COUNT(*) AS count')
      ->from('bot')
      ->where('status = 1')
      ->row()['count'];
  }

  protected function getRowsList(array $params)
  {
    $rows = $this->db->select('b.*, bd.*')
      ->from('bot b')
      ->leftJoin('bot_desc bd', 'bd.bot_id = b.id AND bd.language_id = :language_id')
      ->where('b.status = 1')
      ->orderBy([
        $params['sort'] => $params['order'], 
        'id' => 'ASC']
      )
      ->limit($params['limit'])
      ->offset($params['start'])
      ->bindValues(['language_id' => (string)$params['language_id']])
      ->query();

    return $rows;
  }
}