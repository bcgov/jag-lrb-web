<?php

/**
 * @file
 * Contains \Drupal\block\Plugin\views\area\Block.
 */

namespace Drupal\views_block_area\Plugin\views\area;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockManagerInterface;

/**
 * Provides an area handler which renders a block entity in a certain view mode.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("views_block_area")
 */
class ViewsBlockArea extends AreaPluginBase {

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.block'),
      $container->get('entity.repository'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManagerInterface $block_manager, EntityRepositoryInterface $entity_repository = NULL, RendererInterface $renderer) {
    $this->blockManager = $block_manager;
    $this->entityRepository = $entity_repository;
    $this->renderer = $renderer;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['block_id'] = ['default' => NULL];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = [];
    /** @var \Drupal\block_field\BlockFieldManagerInterface $block_field_manager */
    $definitions = $this->getBlockDefinitions();
    foreach ($definitions as $id => $definition) {
      // If allowed plugin ids are set then check that this block should be
      // included.
      $category = (string) $definition['category'];
      $options[$category][$id] = $definition['admin_label'];
    }

    $form['block_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Block'),
      '#options' => $options,
      '#empty_option' => $this->t('Please select'),
      '#default_value' => $this->options['block_id'],
    ];
  }

  /**
   * Get sorted listed of supported block definitions.
   *
   * @return array
   *   An associative array of supported block definitions.
   */
  protected function getBlockDefinitions() {
    $definitions = $this->blockManager->getSortedDefinitions();
    $block_definitions = [];
    foreach ($definitions as $plugin_id => $definition) {
      // Context aware plugins are not currently supported.
      // Core and component plugins can be context-aware
      // https://www.drupal.org/node/1938688
      // @see \Drupal\ctools\Plugin\Block\EntityView
      if (!empty($definition['context'])) {
        continue;
      }

      $block_definitions[$plugin_id] = $definition;
    }
    return $block_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $element = [];
    /** @var \Drupal\block_field\BlockFieldItemInterface $item */
    $block_instance = $this->getBlock();
    // Make sure the block exists and is accessible.
    if (!$block_instance || !$block_instance->access(\Drupal::currentUser())) {
      return NULL;
    }

    // @see \Drupal\block\BlockViewBuilder::buildPreRenderableBlock
    // @see template_preprocess_block()
    $element = [
      '#theme' => 'block',
      '#attributes' => [],
      '#configuration' => $block_instance->getConfiguration(),
      '#plugin_id' => $block_instance->getPluginId(),
      '#base_plugin_id' => $block_instance->getBaseId(),
      '#derivative_plugin_id' => $block_instance->getDerivativeId(),
      '#id' => $block_instance->getPluginId(),
      'content' => $block_instance->build(),
    ];
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->renderer;
    $renderer->addCacheableDependency($element, $block_instance);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBlock() {
    if (empty($this->options['block_id'])) {
      return NULL;
    }

    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = $this->blockManager;

    /** @var \Drupal\Core\Block\BlockPluginInterface $block_instance */
    $block_instance = $block_manager->createInstance($this->options['block_id'], []);

    $plugin_definition = $block_instance->getPluginDefinition();

    // Don't return broken block plugin instances.
    if ($plugin_definition['id'] == 'broken') {
      return NULL;
    }

    // Don't return broken block content instances.
    if ($plugin_definition['id'] == 'block_content') {
      $uuid = $block_instance->getDerivativeId();
      if (!$this->entityRepository->loadEntityByUuid('block_content', $uuid)) {
        return NULL;
      }
    }

    return $block_instance;
  }
}
