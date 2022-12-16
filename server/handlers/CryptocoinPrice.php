<?php

namespace WebsocketApi\handlers;

use WebsocketApi\handlers\handlersBase\EntityHandler;

class CryptocoinPrice extends EntityHandler
{
  protected $entity = 'cryptocoin_price';
  protected $list_default_sort = 'created_at';
  protected $list_default_order = 'DESC';
  protected $list_default_limit = 10;

  protected function getRowsCount()
  {
    return $this->db->select('COUNT(*) AS count')
      ->from('cryptocoin_price')
      ->row()['count'];
  }

  protected function getRowsList(array $params): array
  {
    return $this->db
      ->select('*')
      ->from('cryptocoin_price')
      ->orderBy([
        $params['sort'] => $params['order'], 
        'id' => 'ASC']
      )
      ->limit((int)$params['limit'])
      ->offset((int)$params['start'])
      ->query();
  }
}