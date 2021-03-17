<?php

namespace Drupal\social_media\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class AccessController.
 *
 * @package Drupal\social_media\Controller
 */
class AccessController extends ControllerBase {

  /**
   * Check if email option is enabled, if not do not allow this path.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Return allowed if the social media email is enabled.
   */
  public function access() {
    $config = $this->config('social_media.settings');
    if ($config->get('social_media.email.enable') && $config->get('social_media.email.enable_forward')) {
      return AccessResult::allowed()->addCacheableDependency($config);
    }
    return AccessResult::forbidden()->addCacheableDependency($config);
  }

}
