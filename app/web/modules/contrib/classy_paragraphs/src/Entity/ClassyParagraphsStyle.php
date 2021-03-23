<?php

namespace Drupal\classy_paragraphs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\classy_paragraphs\ClassyParagraphsStyleInterface;

/**
 * Defines the Classy paragraphs style entity.
 *
 * @ConfigEntityType(
 *   id = "classy_paragraphs_style",
 *   label = @Translation("Classy paragraphs style"),
 *   handlers = {
 *     "list_builder" = "Drupal\classy_paragraphs\ClassyParagraphsStyleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\classy_paragraphs\Form\ClassyParagraphsStyleForm",
 *       "edit" = "Drupal\classy_paragraphs\Form\ClassyParagraphsStyleForm",
 *       "delete" = "Drupal\classy_paragraphs\Form\ClassyParagraphsStyleDeleteForm"
 *     },
 *     "access" = "Drupal\classy_paragraphs\ClassyParagraphsAccessControlHandler"
 *   },
 *   config_prefix = "classy_paragraphs_style",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "classes",
 *     "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/classy_paragraphs_style/{classy_paragraphs_style}",
 *     "edit-form" = "/admin/structure/classy_paragraphs_style/{classy_paragraphs_style}/edit",
 *     "delete-form" = "/admin/structure/classy_paragraphs_style/{classy_paragraphs_style}/delete",
 *     "collection" = "/admin/structure/classy_paragraphs_style"
 *   }
 * )
 */
class ClassyParagraphsStyle extends ConfigEntityBase implements ClassyParagraphsStyleInterface {

  /**
   * The Classy paragraphs style ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Classy paragraphs style label.
   *
   * @var string
   */
  protected $label;

  /**
   * An array of CSS classes
   * In the format:
   * btn
   * btn-default
   *
   * @var array
   */
  protected $classes;

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return isset($this->classes) ? $this->classes : NULL;
  }

}
