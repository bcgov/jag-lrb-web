<?php

namespace Drupal\salesforce_mapping\Plugin\SalesforceMappingField;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\salesforce_mapping\Entity\SalesforceMappingInterface;

/**
 * Adapter for entity properties and fields.
 *
 * @Plugin(
 *   id = "properties",
 *   label = @Translation("Properties")
 * )
 */
class Properties extends PropertiesBase {

  /**
   * Implementation of PluginFormInterface::buildConfigurationForm.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $pluginForm = parent::buildConfigurationForm($form, $form_state);
    // @TODO inspecting the form and form_state feels wrong, but haven't found a good way to get the entity from config before the config is saved.
    $options = $this->getConfigurationOptions($form['#entity']);

    // Display the plugin config form here:
    if (empty($options)) {
      $pluginForm['drupal_field_value'] = [
        '#markup' => $this->t('No available properties.'),
      ];
    }
    else {
      $pluginForm['drupal_field_value'] += [
        '#type' => 'select',
        '#options' => $options,
        '#empty_option' => $this->t('- Select -'),
        '#default_value' => $this->config('drupal_field_value'),
        '#description' => $this->t('Select a Drupal field or property to map to a Salesforce field.<br />Entity Reference fields should be handled using Related Entity Ids or Token field types.'),
      ];
    }

    return $pluginForm;
  }

  /**
   * Form options helper.
   */
  protected function getConfigurationOptions(SalesforceMappingInterface $mapping) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions */
    $field_definitions = $this->entityFieldManager->getFieldDefinitions(
      $mapping->get('drupal_entity_type'),
      $mapping->get('drupal_bundle')
    );

    $options = [];

    foreach ($field_definitions as $field_name => $field_definition) {
      $label = $field_definition->getLabel();
      if ($this->instanceOfEntityReference($field_definition)) {
        continue;
      }
      else {
        // Get a list of property definitions.
        $property_definitions = $field_definition->getFieldStorageDefinition()
          ->getPropertyDefinitions();
        if (count($property_definitions) > 1) {
          foreach ($property_definitions as $property => $property_definition) {
            $options[(string) $label][$field_name . '.' . $property] = $label . ': ' . $property_definition->getLabel();
          }
        }
        else {
          $options[$field_name] = $label;
        }
      }
    }
    asort($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $definition = parent::getPluginDefinition();
    if ($field = FieldConfig::loadByName($this->mapping->getDrupalEntityType(), $this->mapping->getDrupalBundle(), $this->config('drupal_field_value'))) {
      $definition['config_dependencies']['config'][] = $field->getConfigDependencyName();
    }
    return $definition;
  }

}
