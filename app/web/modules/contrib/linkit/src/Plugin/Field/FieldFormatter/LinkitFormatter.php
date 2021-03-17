<?php

namespace Drupal\linkit\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\linkit\Entity\Profile;
use Drupal\linkit\SubstitutionManagerInterface;
use Drupal\linkit\Utility\LinkitHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'linkit' formatter.
 *
 * @FieldFormatter(
 *   id = "linkit",
 *   label = @Translation("Linkit"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkitFormatter extends LinkFormatter implements ContainerFactoryPluginInterface {

  /**
   * The substitution manager.
   *
   * @var \Drupal\linkit\SubstitutionManagerInterface
   */
  protected $substitutionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('path.validator'),
      $container->get('plugin.manager.linkit.substitution'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a new LinkitFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   * @param \Drupal\linkit\SubstitutionManagerInterface $substitution_manager
   *   The substitution manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, PathValidatorInterface $path_validator, SubstitutionManagerInterface $substitution_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $path_validator);
    $this->substitutionManager = $substitution_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'linkit_profile' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $linkit_profiles = $this->entityTypeManager->getStorage('linkit_profile')->loadMultiple();

    $options = [];
    foreach ($linkit_profiles as $linkit_profile) {
      $options[$linkit_profile->id()] = $linkit_profile->label();
    }

    $elements['linkit_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Linkit profile'),
      '#description' => $this->t('Must be the same as the profile selected on the form display for this field.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('linkit_profile'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $linkit_profile_id = $this->getSetting('linkit_profile');
    $linkit_profile = $this->entityTypeManager->getStorage('linkit_profile')->load($linkit_profile_id);

    if ($linkit_profile) {
      $summary[] = $this->t('Linkit profile: @linkit_profile', ['@linkit_profile' => $linkit_profile->label()]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Loop over the elements and substitute the URL.
    foreach ($elements as $delta => &$item) {
      /** @var \Drupal\link\LinkItemInterface $link_item */
      $link_item = $items->get($delta);
      $substituted_url = $this->getSubstitutedUrl($link_item);
      // Convert generated URL into a URL object.
      if ($substituted_url && ($url = \Drupal::pathValidator()->getUrlIfValid($substituted_url->getGeneratedUrl()))) {
        // Keep query and fragment.
        $parsed_url = parse_url($link_item->uri);
        if (!empty($parsed_url['query'])) {
          $parsed_query = [];
          parse_str($parsed_url['query'], $parsed_query);
          if (!empty($parsed_query)) {
            $url->setOption('query', $parsed_query);
          }
        }
        if (!empty($parsed_url['fragment'])) {
          $url->setOption('fragment', $parsed_url['fragment']);
        }
        // Add cache dependency to the referenced entity, e.g. for media direct file substitution
        if ($entity = LinkitHelper::getEntityFromUserInput($link_item->uri)) {
          $elements[$delta]['#cache']['tags'] = array_merge(
            $elements[$delta]['#cache']['tags'] ?? [],
            $entity->getCacheTags()
          );
        }
        $item['#url'] = $url;
      }
    }

    return $elements;
  }

  /**
   * Returns a substitution URL for the given linked item.
   *
   * In case the items links to an entity use a substituted/generated URL.
   *
   * @param \Drupal\link\LinkItemInterface $item
   *   The link item.
   *
   * @return \Drupal\Core\GeneratedUrl|null
   *   The substitution URL, or NULL if not able to retrieve it from the item.
   */
  protected function getSubstitutedUrl(LinkItemInterface $item) {
    if ($entity = LinkitHelper::getEntityFromUserInput($item->uri)) {
      $profile = Profile::load($this->getSettings()['linkit_profile']);

      /** @var \\Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher $matcher */
      $matcher = $profile->getMatcherByEntityType($entity->getEntityTypeId());
      $substitution_type = $matcher ? $matcher->getConfiguration()['settings']['substitution_type'] : SubstitutionManagerInterface::DEFAULT_SUBSTITUTION;
      return $this->substitutionManager->createInstance($substitution_type)->getUrl($entity);
    }

    return NULL;
  }

}
