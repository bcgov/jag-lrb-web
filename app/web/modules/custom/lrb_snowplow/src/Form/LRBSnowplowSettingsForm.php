<?php

namespace Drupal\lrb_snowplow\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LRBSnowplowSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lrb_snowplow_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    
    // Default settings.
    $config = $this->config('lrb_snowplow.settings');

    $form['code']['snowplow_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Snowplow'),
      '#description' => $this->t('This will active Snowplow analytics tracking for non-logged-in visitors.'),
      '#default_value' => $config->get('lrb_snowplow.code.snowplow_active'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'lrb_snowplow.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('lrb_snowplow.settings');

    $config->set('lrb_snowplow.code.snowplow_active', $form_state->getValue('snowplow_active'));

    $config->set('lrb_snowplow.code.header', $form_state->getValue('header'));

    $config->set('lrb_snowplow.code.footer', $form_state->getValue('footer'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

}