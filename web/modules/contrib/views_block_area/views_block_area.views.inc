<?php

/**
 * @file
 * Provide views data for views_block_area module.
 */

/**
 * Implements hook_views_data().
 */
function views_block_area_views_data() {
  $data = [];

  $data['views']['views_block_area'] = [
    'title' => t('Block area'),
    'help' => t('Insert a block inside an area.'),
    'area' => [
      'id' => 'views_block_area',
    ],
  ];

  return $data;
}
