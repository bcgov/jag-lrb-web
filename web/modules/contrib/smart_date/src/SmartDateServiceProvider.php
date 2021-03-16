<?php

namespace Drupal\smart_date;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\smart_date\Normalizer\SmartDateNormalizer;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Service Provider for Smart Date.
 */
class SmartDateServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['hal'])) {
      // HAL module is enabled, add our Smart Date normalizer.
      // Priority of the normalizer must be higher than other
      // general-purpose typed data and field item normalizers.
      $smart_date = new Definition(SmartDateNormalizer::class);
      $smart_date->addTag('normalizer', ['priority' => 30]);
      $container->setDefinition('smart_date.normalizer.smartdate.hal', $smart_date);
    }
  }

}
