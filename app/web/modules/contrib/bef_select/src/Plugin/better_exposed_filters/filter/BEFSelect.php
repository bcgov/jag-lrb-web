<?php

namespace Drupal\bef_select\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Select form element to select from available field values.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_select",
 *   label = @Translation("BEF Select"),
 * )
 */
class BEFSelect extends FilterWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Filter table alias.
   */
  const FILTER_TABLE_ALIAS = 'filter_table';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new BEFSelect.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'query_tags' => '',
      'max_results' => 1000,
      'order_by' => 'none',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['query_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query tags'),
      '#default_value' => $this->configuration['query_tags'],
      '#description' => $this->t('Additional tags to be added to the filter options query, comma-sepearted if multiple.'),
    ];

    $form['max_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum options'),
      '#default_value' => $this->configuration['max_results'],
      '#description' => $this->t('Maximum number of options for the select element.'),
    ];

    $form['order_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Order by'),
      '#default_value' => $this->configuration['order_by'],
      '#description' => $this->t('Choose how the options will be sorted.'),
      '#options' => [
        'none' => $this->t('No sorting'),
        'asc' => $this->t('Ascending'),
        'desc' => $this->t('Descending'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($filter = NULL, array $filter_options = []) {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $is_applicable = FALSE;

    if (is_a($filter, 'Drupal\views\Plugin\views\filter\StringFilter')) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    $filter = $this->handler;
    $field_id = $this->getExposedFilterFieldId();

    // Get possible values for the filter.
    // This is probably not the most universal way to do it,
    // it'll need to be extended to cover many use cases.
    $query = $this->connection
      ->select($filter->options['table'], static::FILTER_TABLE_ALIAS)
      ->fields('filter_table', [$filter->options['field']])
      ->distinct()
      ->range(0, $this->configuration['max_results']);

    // add orderBy if not "No sorting"
    if ($this->configuration['order_by'] != 'none') {
      $query->orderBy($filter->options['field'], $this->configuration['order_by']);
    }

    $alter_tags = [];
    // Add custom alter tags.
    foreach (explode(',', $this->configuration['query_tags']) as $tag) {
      $alter_tags[] = trim($tag);
    }

    // Add access-related stuff to the query.
    if (!empty($filter->options['entity_type']) && $entity_type_definition = $this->entityTypeManager->getDefinition($filter->options['entity_type'])) {
      // Join with the entity type base table for easier access alterations.
      $base_table = $entity_type_definition->getBaseTable();
      $id_field = $entity_type_definition->getKey('id');
      $query->join(
        $base_table,
        $base_table,
        static::FILTER_TABLE_ALIAS . '.' . $id_field . '=' . $base_table . '.' . $id_field
      );
      $alter_tags[] = $filter->options['entity_type'] . '_access';
    }

    foreach ($alter_tags as $alter_tag) {
      $query->addTag($alter_tag);
    }

    $results = $query
      ->execute()
      ->fetchCol();

    if (!empty($results)) {
      $multiple = $filter->options['operator'] === 'in' ? TRUE : FALSE;
      $element = [
        '#type' => 'select',
        '#options' => $multiple ? [] : ['' => $this->t('-- Select --')],
        '#multiple' => $multiple,
        '#default_value' => $multiple ? NULL : '',
        '#element_validate' => [[$this, 'elementValidate']],
      ];
      foreach ($results as $result) {
        $element['#options'][$result] = $result;
      }

      foreach (['#value', '#default_value'] as $parameter) {
        if (isset($form[$field_id][$parameter]) && in_array($form[$field_id][$parameter], $element['#options'])) {
          $element[$parameter] = $form[$field_id][$parameter];
        }
      }

      if ($multiple) {
        $identifier = $filter->options['expose']['identifier'];
        $user_input = $form_state->getUserInput();
        if (!empty($user_input[$identifier]) && !is_array($user_input[$identifier])) {
          $values = explode(',', $user_input[$identifier]);
          foreach ($values as $key => $value) {
            $values[$key] = trim($value);
          }
          $user_input[$identifier] = $values;
          $form_state->setUserInput($user_input);
        }
      }

      $form[$field_id] = $element;
    }
  }

  /**
   * We need to convert value for the element if it's a multiple select.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function elementValidate(array $element, FormStateInterface $form_state) {
    $value = $form_state->getValue($element['#parents']);
    if (is_array($value)) {
      $form_state->setValue($element['#parents'], implode(', ', $value));
    }
  }

}
