<?php

namespace Drupal\views_year_filter\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Date;
use Drupal\views_year_filter\DateViewsTrait;

/**
 * Date/time views filter.
 *
 * Even thought dates are stored as strings, the numeric filter is extended
 * because it provides more sensible operators.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date_with_more_options")
 */
class ViewsYearFilterDate extends Date {

  use DateViewsTrait;

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (!$form_state->get('exposed')) {
      $form['value']['type'] = [
        '#type'          => 'radios',
        '#title'         => $this->t('Value type'),
        '#options'       => [
          'date'      => $this->t('A date in any machine readable format. CCYY-MM-DD HH:MM:SS is preferred.'),
          'date_year' => $this->t('A date in yyyy format.'),
          'offset'    => $this->t('An offset from the current time such as "@example1" or "@example2"', [
            '@example1' => '+1 day',
            '@example2' => '-2 hours -30 minutes',
          ]),
        ],
        '#default_value' => isset($this->value['type']) ? $this->value['type'] : 'date',
      ];
      // Add js to handle year filter state.
      $form['#attached']['library'][] = 'views_year_filter/year_filter';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    // If year filter selected.
    if (!empty($this->value['type']) && $this->value['type'] == 'date_year' && isset($this->value['value'])) {
      // Get the value.
      $value = $this->value['value'] ?? '';
      // In Case of changed and created date is timestamp.
      if ($field == 'node_field_data.changed' || $field == 'node_field_data.created') {
        $this->query->addWhereExpression($this->options['group'], "YEAR(FROM_UNIXTIME($field)) $this->operator $value");
      }
      else {
        // Add Expression for dates / not timestamp.
        $this->query->addWhereExpression($this->options['group'], "YEAR($field) $this->operator $value");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $this->applyDatePopupToForm($form);
  }

}
