<?php

/**
 * @file
 * Views hooks.
 */

use Drupal\views_year_filter\Plugin\views\filter\ViewsYearFilterDate;
use Drupal\views_year_filter\Plugin\views\filter\ViewsYearFilterDatetime;

/**
 * Implements hook_views_plugins_filter_alter().
 */
function views_year_filter_views_plugins_filter_alter(&$info) {
  if (isset($info['date'])) {
    $info['date']['class'] = ViewsYearFilterDate::class;
  }
  if (isset($info['datetime'])) {
    $info['datetime']['class'] = ViewsYearFilterDatetime::class;
  }
}
