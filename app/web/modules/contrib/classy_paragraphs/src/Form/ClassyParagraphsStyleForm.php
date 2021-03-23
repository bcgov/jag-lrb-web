<?php

namespace Drupal\classy_paragraphs\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ClassyParagraphsStyleForm.
 *
 * @package Drupal\classy_paragraphs\Form
 */
class ClassyParagraphsStyleForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\classy_paragraphs\Entity\ClassyParagraphsStyle $style */
    $style = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $style->label(),
      '#description' => $this->t("Label for the Classy paragraphs style."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $style->id(),
      '#machine_name' => [
        'exists' => '\Drupal\classy_paragraphs\Entity\ClassyParagraphsStyle::load',
      ],
      '#disabled' => !$style->isNew(),
    ];
    
    $form['classes'] = [
      '#title' => $this->t('Classes'),
      '#type' => 'textarea',
      '#default_value' => $style->getClasses(),
      '#description' => $this->t('Enter the CSS classes you want applied. Enter one per-line.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $style = $this->entity;
    $status = $style->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label style.', [
          '%label' => $style->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label style.', [
          '%label' => $style->label(),
        ]));
    }
    $form_state->setRedirectUrl($style->toUrl('collection'));
  }

}
