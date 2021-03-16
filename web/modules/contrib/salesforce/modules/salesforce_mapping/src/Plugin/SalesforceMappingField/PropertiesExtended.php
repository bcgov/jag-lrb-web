<?php

namespace Drupal\salesforce_mapping\Plugin\SalesforceMappingField;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adapter for entity properties and fields.
 *
 * @Plugin(
 *   id = "properties_extended",
 *   label = @Translation("Properties, Extended")
 * )
 */
class PropertiesExtended extends PropertiesBase {

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $definition = parent::getPluginDefinition();
    $field_name = $this->config('drupal_field_value');
    if (strpos($field_name, '.')) {
      list($field_name, $dummy) = explode('.', $field_name, 2);
    }
    // Add reference field.
    if ($field = FieldConfig::loadByName($this->mapping->getDrupalEntityType(), $this->mapping->getDrupalBundle(), $field_name)) {
      $definition['config_dependencies']['config'][] = $field->getConfigDependencyName();
      // Add dependencies of referenced field.
      foreach ($field->getDependencies() as $type => $dependency) {
        foreach ($dependency as $item) {
          $definition['config_dependencies'][$type][] = $item;
        }
      }
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $pluginForm = parent::buildConfigurationForm($form, $form_state);
    $mapping = $form['#entity'];

    // Display the plugin config form here:
    $context_name = 'drupal_field_value';

    // If the form has been submitted already, take the mode from the submitted
    // values, otherwise default to existing configuration. And if that does not
    // exist default to the "input" mode.
    $mode = $form_state->get('context_' . $context_name);
    if (!$mode) {
      $mode = 'selector';
      $form_state->set('context_' . $context_name, $mode);
    }
    $title = $mode == 'selector' ? $this->t('Data selector') : $this->t('Value');

    $pluginForm[$context_name]['setting'] = [
      '#type' => 'textfield',
      '#title' => $title,
      '#attributes' => ['class' => ['drupal-field-value']],
      '#default_value' => $this->config('drupal_field_value'),
    ];
    $element = &$pluginForm[$context_name]['setting'];
    if ($mode == 'selector') {
      $element['#description'] = $this->t("The data selector helps you drill down into the data available.");
      $element['#autocomplete_route_name'] = 'salesforce_mapping.autocomplete_controller_autocomplete';
      $element['#autocomplete_route_parameters'] = [
        'entity_type_id' => $mapping->get('drupal_entity_type'),
        'bundle' => $mapping->get('drupal_bundle'),
      ];
    }
    $value = $mode == 'selector' ? $this->t('Switch to the direct input mode') : $this->t('Switch to data selection');
    $pluginForm[$context_name]['switch_button'] = [
      '#type' => 'submit',
      '#name' => 'context_' . $context_name,
      '#attributes' => ['class' => ['drupal-field-switch-button']],
      '#parameter' => $context_name,
      '#value' => $value,
      '#submit' => [static::class . '::switchContextMode'],
      // Do not validate!
      '#limit_validation_errors' => [],
    ];

    return $pluginForm;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Resetting the `drupal_field_value` to just the `setting` portion,
    // which should be a string.
    $config_value = $form_state->getValue('config');
    $config_value['drupal_field_value'] = $config_value['drupal_field_value']['setting'];
    $form_state->setValue('config', $config_value);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDrupalFieldType(DataDefinitionInterface $data_definition) {
    $field_main_property = $data_definition;
    if ($data_definition instanceof ComplexDataDefinitionInterface) {
      $field_main_property = $data_definition
        ->getPropertyDefinition($data_definition->getMainPropertyName());
    }

    return $field_main_property ? $field_main_property->getDataType() : NULL;
  }

  /**
   * Submit callback: switch a context to data selector or direct input mode.
   */
  public static function switchContextMode(array &$form, FormStateInterface $form_state) {
    $element_name = $form_state->getTriggeringElement()['#name'];
    $mode = $form_state->get($element_name);
    $switched_mode = $mode == 'selector' ? 'input' : 'selector';
    $form_state->set($element_name, $switched_mode);
    $form_state->setRebuild();
  }

}
