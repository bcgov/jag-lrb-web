<?php

namespace Drupal\salesforce_mapping_ui\Form;

use Drupal\Component\Utility\NestedArray;
use \Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\salesforce_mapping\SalesforceMappingFieldPluginInterface as FieldPluginInterface;

/**
 * Salesforce Mapping Fields Form.
 */
class SalesforceMappingFieldsForm extends SalesforceMappingFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->ensureConnection('objectDescribe', [$this->entity->getSalesforceObjectType(), TRUE])) {
      return $form;
    }
    $form = parent::buildForm($form, $form_state);
    // Previously "Field Mapping" table on the map edit form.
    // @TODO add a header with Fieldmap Property information.

    // Add #entity property to expose it to our field plugin forms.
    $form['#entity'] = $this->entity;

    $form['#attached']['library'][] = 'salesforce/admin';
    // This needs to be loaded now as it can't be loaded via AJAX for the AC
    // enabled fields.
    $form['#attached']['library'][] = 'core/drupal.autocomplete';

    // For each field on the map, add a row to our table.
    $form['overview'] = ['#markup' => 'Field mapping overview goes here.'];

    $form['key_wrapper'] = [
      '#title' => $this->t('Upsert Key'),
      '#type' => 'details',
      '#open' => TRUE,
      '#description' => $this->t('An Upsert Key can be assigned to map a Drupal property to a Salesforce External Identifier. If specified an UPSERT will be used to limit data duplication.'),
    ];

    $key_options = $this->getUpsertKeyOptions();
    if (empty($key_options)) {
      $form['key_wrapper']['#description'] .= ' ' . $this->t('To add an upsert key for @sobject_name, assign a field as an External Identifier in Salesforce.', ['@sobject_name' => $this->entity->get('salesforce_object_type')]);
      $form['key_wrapper']['key'] = [
        '#type' => 'value',
        '#value' => '',
      ];
    }
    else {
      $form['key_wrapper']['key'] = [
        '#type' => 'select',
        '#title' => $this->t('Upsert Key'),
        '#options' => $key_options,
        '#default_value' => $this->entity->getKeyField(),
        '#empty_option' => $this->t('(none)'),
        '#empty_value' => '',
      ];
      $form['key_wrapper']['always_upsert'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Always Upsert'),
        '#default_value' => $this->entity->get('always_upsert'),
        '#description' => $this->t('If checked, always use "upsert" to push data to Salesforce. Otherwise, prefer a Salesforce ID if available. For example, given a user mapping with "email" set for upsert key, leave this checkbox off; otherwise, a new Salesforce record will be created whenever a user changes their email.'),
      ];
    }

    $form['field_mappings_wrapper'] = [
      '#title' => $this->t('Mapped Fields'),
      '#type' => 'fieldset',
    ];

    $field_mappings_wrapper = &$form['field_mappings_wrapper'];
    // Check to see if we have enough information to allow mapping fields.  If
    // not, tell the user what is needed in order to have the field map show up.
    $field_mappings_wrapper['field_mappings'] = [
      '#tree' => TRUE,
      '#type' => 'container',
      // @TODO there's probably a better way to tie ajax callbacks to this element than by hard-coding an HTML DOM ID here.
      '#prefix' => '<div id="edit-field-mappings">',
      '#suffix' => '</div>',
      '#attributes' => ['class' => ['container-striped']],
    ];
    $rows = &$field_mappings_wrapper['field_mappings'];

    $form['field_mappings_wrapper']['ajax_warning'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'edit-ajax-warning',
      ],
    ];

    $add_field_text = !empty($field_mappings) ? $this->t('Add another field mapping') : $this->t('Add a field mapping to get started');

    $form['buttons'] = [
      '#type' => 'container',
      '#tree' => true
    ];
    $form['buttons']['field_type'] = [
      '#title' => $this->t('Field Type'),
      '#type' => 'select',
      '#options' => $this->getDrupalTypeOptions($this->entity),
      '#attributes' => ['id' => 'edit-mapping-add-field-type'],
      '#empty_option' => $this->t('- Select -'),
    ];
    $form['buttons']['add'] = [
      '#value' => $add_field_text,
      '#type' => 'submit',
      '#limit_validation_errors' => [['buttons']],
      '#submit' => ['::addField'],
      '#ajax' => [
        'callback' => [$this, 'fieldAddCallback'],
        'wrapper' => 'edit-field-mappings',
      ],
      '#states' => [
        'disabled' => [
          ':input#edit-mapping-add-field-type' => ['value' => ''],
        ],
      ],
    ];

    // Add a row for each saved mapping.
    foreach ($this->entity->getFieldMappings() as $field_plugin) {
      $rows[] = $this->getRow($form, $form_state, $field_plugin);
    }

    // Add a new row in case it was just added.
    $values = &$form_state->getValues();
    $new_field = NestedArray::getValue($values, ['buttons', 'new_field']);
    if (!empty($new_field)) {
      $rows[] = $this->getRow($form, $form_state);
      NestedArray::unsetValue($values, ['buttons', 'new_field']);
    }

    // Retrieve and add the form actions array.
    $actions = $this->actionsElement($form, $form_state);
    if (!empty($actions)) {
      $form['actions'] = $actions;
    }

    return $form;
  }

  /**
   * Return an options array of field labels for any fields marked externalId.
   */
  private function getUpsertKeyOptions() {
    $options = [];
    try {
      $describe = $this->getSalesforceObject();
    }
    catch (\Exception $e) {
      return [];
    }

    foreach ($describe->fields as $field) {
      if ($field['externalId'] || $field['idLookup']) {
        $options[$field['name']] = $field['label'];
      }
    }
    return $options;
  }

  /**
   * Helper function to return an empty row for the field mapping form.
   */
  private function getRow($form, FormStateInterface $form_state, FieldPluginInterface $field_plugin = NULL) {
    $values = &$form_state->getValues();
    if ($field_plugin == NULL) {
      $field_type = NestedArray::getValue($values, ['buttons', 'new_field']);
      $field_plugin_definition = $this->getFieldPlugin($field_type);
      $configuration = [
        'mapping' => $this->entity,
        'id' => count(Element::children($form['field_mappings_wrapper']['field_mappings'])),
        'drupal_field_type' => $field_type,
      ];
      /** @var \Drupal\salesforce_mapping\SalesforceMappingFieldPluginInterface $field_plugin */
      $field_plugin = $this->mappingFieldPluginManager->createInstance(
        $field_plugin_definition['id'], $configuration
      );
      $field_mapping_plugins = $this->entity->getFieldMappings();
      $config = [];
      foreach ($field_mapping_plugins as $plugin) {
        $config[] = $plugin->getConfiguration();
      }
      $config[] = $field_plugin->getConfiguration();
      $this->entity->set('field_mappings', $config);
    }

    $row['config'] = $field_plugin->buildConfigurationForm($form, $form_state);
    $row['config']['id'] = ['#type' => 'value', '#value' => $field_plugin->config('id')];
    // @TODO implement "lock/unlock" logic here:
    // @TODO convert these to AJAX operations
    $operations = [
      'delete' => $this->t('Delete'),
    ];
    $defaults = [];
    $row['ops'] = [
      '#title' => $this->t('Operations'),
      '#type' => 'checkboxes',
      '#options' => $operations,
      '#default_value' => $defaults,
      '#attributes' => ['class' => ['narrow']],
    ];
    $row['drupal_field_type'] = [
      '#type' => 'hidden',
      '#value' => $field_plugin->getPluginId(),
    ];
    $row['#type'] = 'container';
    $row['#attributes'] = [
      'class' => ['field_mapping_field', 'row', $field_plugin->config('id') % 2 ? 'odd' : 'even']
    ];
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Transform data from the operations column into the expected schema.
    // Copy the submitted values so we don't run into problems with array
    // indexing while removing delete field mappings.
    $values = $form_state->getValues();
    if (empty($values['field_mappings'])) {
      // No mappings have been added, no validation to be done.
      return;
    }

    $key = $values['key'];
    $key_mapped = FALSE;

    foreach ($values['field_mappings'] as $i => $value) {
      // If a field was deleted, delete it!
      if (!empty($value['ops']['delete'])) {
        $form_state->unsetValue(["field_mappings", "$i"]);
        continue;
      }

      // Pass validation to field plugins before performing mapping validation.
      $field_plugin = $this->entity->getFieldMapping($value);
      $sub_form_state = SubformState::createForSubform($form['field_mappings_wrapper']['field_mappings'][$i], $form, $form_state);
      $field_plugin->validateConfigurationForm($form['field_mappings_wrapper']['field_mappings'][$i], $sub_form_state);

      // Send to drupal field plugin for additional validation.
      if ($field_plugin->config('salesforce_field') == $key) {
        $key_mapped = TRUE;
      }
    }

    if (!empty($key) && !$key_mapped) {
      // Do not allow saving mapping when key field is not mapped.
      $form_state->setErrorByName('key', $this->t('You must add the selected field to the field mapping in order set an Upsert Key.'));
    }

  }

  /**
   * Submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Need to transform the schema slightly to remove the "config" dereference.
    // Also trigger submit handlers on plugins.
    $form_state->unsetValue(['buttons', 'field_type', 'ops']);

    $values = &$form_state->getValues();
    foreach ($values['field_mappings'] as $i => &$value) {
      // Pass submit values to plugin submit handler.
      $field_plugin = $this->entity->getFieldMapping($value);
      $sub_form_state = SubformState::createForSubform($form['field_mappings_wrapper']['field_mappings'][$i], $form, $form_state);
      $field_plugin->submitConfigurationForm($form['field_mappings_wrapper']['field_mappings'][$i], $sub_form_state);

      $value = $value + $value['config'] + ['id' => $i];
      unset($value['config'], $value['ops']);
    }
    $this->entity->set('field_mappings', $values['field_mappings']);
    parent::submitForm($form, $form_state);
  }

  /**
   * Ajax callback for adding a new field.
   */
  public function fieldAddCallback(&$form, FormStateInterface $form_state) {
    return $form['field_mappings_wrapper']['field_mappings'];
  }

  public function addField(&$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $values = &$form_state->getValues();
    $new_field = NestedArray::getValue($values, ['buttons', 'field_type']);
    if (in_array('add', $trigger['#array_parents'])
      && !empty($new_field)
      && $trigger['#name'] != 'context_drupal_field_value') {
      NestedArray::setValue($values, ['buttons', 'new_field'], $new_field);
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * Get an array of drupal types.
   */
  protected function getDrupalTypeOptions($mapping) {
    $field_plugins = $this->mappingFieldPluginManager->getDefinitions();
    $options = [];
    foreach ($field_plugins as $definition) {
      if (call_user_func([$definition['class'], 'isAllowed'], $mapping)) {
        $options[$definition['id']] = $definition['label'];
      }
    }
    return $options;
  }

  /**
   * Get a field plugin of the given type.
   */
  protected function getFieldPlugin($field_type) {
    $field_plugins = $this->mappingFieldPluginManager->getDefinitions();
    return $field_plugins[$field_type];
  }

}
