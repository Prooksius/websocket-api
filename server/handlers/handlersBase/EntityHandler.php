<?php

namespace WebsocketApi\handlers\handlersBase;

class EntityHandler extends PrivateHandler
{
  protected $entity = "";

  protected $list_default_sort = '';
  protected $list_default_order = '';
  protected $list_default_limit = 10;

  protected function list(array $params, string $token = '')
  {

    if (empty($params['sort'])) {
      $params['sort'] = $this->list_default_sort;
    }

    if (empty($params['order'])) {
      $params['order'] = $this->list_default_order;
    }

    if (empty($params['limit'])) {
      $params['limit'] = (int)$this->list_default_limit;
    }

    if (!isset($params['page'])) {
      $params['page'] = 1;
      $params['start'] = 0;
    } else {
      $params['start'] = ((int)$params['page'] - 1) * (int)$params['limit'];
    }

    $rows_count = $this->getRowsCount();

    $rows = [];
    if ($rows_count > 0) {
      $rows = $this->getRowsList($params);
    }

    $data = [
      'page' => (int)$params['page'],
      'limit' => (int)$params['limit'],
      'count' => $rows_count,
      'list' => $rows,
    ];

    return $data;
  }

  protected function getRowsCount()
  {
    return 0;
  }

  protected function getRowsList(array $params)
  {
    return [];
  }
}