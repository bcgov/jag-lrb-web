<?php

namespace Drupal\classy_paragraphs;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the Classy Paragraphs config entity.
 */
class ClassyParagraphsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // There are no restrictions on viewing the label or config entity itself.
    if (in_array($operation, ['view label', 'view'])) {
      return AccessResult::allowed();
    }
    elseif (in_array($operation, ['update', 'delete'])) {
      return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
    }

    return parent::checkAccess($entity, $operation, $account);
  }
}
