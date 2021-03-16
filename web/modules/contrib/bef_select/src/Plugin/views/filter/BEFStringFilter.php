<?php

namespace Drupal\bef_select\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Replacement class for Views string filter.
 */
class BEFStringFilter extends StringFilter {

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();

    $operators['in'] = [
      'title' => $this->t('In (comma-separated)'),
      'short' => $this->t('IN'),
      'method' => 'opIn',
      'values' => 1,
    ];

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function operator() {
    $operator = parent::operator();

    if ($this->operator === 'in') {
      $operator = 'IN';
    }
    return $operator;
  }

  /**
   * Defines the "in" operator query.
   */
  protected function opIn($field) {
    $values = [];
    foreach (explode(',', $this->value) as $value) {
      $values[] = trim($value);
    }
    $this->query->addWhere($this->options['group'], $field, $values, $this->operator());
  }

}
