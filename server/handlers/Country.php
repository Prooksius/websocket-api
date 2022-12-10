<?php

namespace WebsocketApi\handlers;

use WebsocketApi\handlers\handlersBase\EntityHandler;

class Country extends EntityHandler
{
  protected $entity = 'country';
  protected $list_default_sort = 'popularity';
  protected $list_default_order = 'DESC';
  protected $list_default_limit = 20;

  protected function getRowsCount()
  {
    return $this->db->select('COUNT(*) AS count')
      ->from('country')
      ->where('status = 1')
      ->row()['count'];
  }

  protected function getRowsList(array $params)
  {
    return $this->db
      ->select('c.*, cd.*')
      ->from('country c')
      ->leftJoin('country_desc cd', 'ON cd.country_id = c.id AND cd.language_id = :language_id')
      ->where('c.status = 1')
      ->orderBy([
        $params['sort'] => $params['order'], 
        'id' => 'ASC']
      )
      ->limit((int)$params['limit'])
      ->offset((int)$params['start'])
      ->bindValues(['language_id' => (string)$params['language_id']])
      ->query();
  }
}