<?php

namespace Drupal\classy_paragraphs;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Classy paragraphs style entities.
 */
interface ClassyParagraphsStyleInterface extends ConfigEntityInterface {

  /**
   * Returns text from the classes field.
   *
   * @return
   *   A string of classes.
   */
  public function getClasses();

}
