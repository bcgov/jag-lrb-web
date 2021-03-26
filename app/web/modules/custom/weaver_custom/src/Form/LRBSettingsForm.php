<?php

namespace Drupal\weaver_custom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LRBSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'weaver_custom_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    
    // Default settings.
    $config = $this->config('weaver_custom.settings');

    $form['site_elements'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Site Elements'),
    ];

    // $form['site_elements']['home'] = [
    //   '#type' => 'fieldset',
    //   '#title' => $this->t('Home Page'),
    //   '#tree' => true,
    // ];

    // $form['site_elements']['home']['updates'] = [
    //   '#type' => 'text_format',
    //   '#title' => $this->t('Updates Bar'),
    //   '#format' => $config->get('weaver_custom.site_elements.home.updates.format'),
    //   '#default_value' => $config->get('weaver_custom.site_elements.home.updates.value'),
    // ];

    $form['site_elements']['all'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('All Pages'),
      '#tree' => true,
    ];

    $form['site_elements']['all']['updates_left'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Updates Bar Left Side'),
      '#format' => $config->get('weaver_custom.site_elements.all.updates_left.format'),
      '#default_value' => $config->get('weaver_custom.site_elements.all.updates_left.value'),
    ];

    $form['site_elements']['all']['updates_right'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Updates Bar Right Side'),
      '#format' => $config->get('weaver_custom.site_elements.all.updates_right.format'),
      '#default_value' => $config->get('weaver_custom.site_elements.all.updates_right.value'),
    ];

    $form['site_elements']['side_nav'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Side Navigation'),
      '#tree' => true,
    ];

    // add this many elements for side nav
    $side_count = 3;
    $side_nav_value = $config->get('weaver_custom.site_elements.side_nav');

    for ($i = 1; $i <= $side_count; $i++) {

      $form['site_elements']['side_nav'][$i] = [
        '#type' => 'container',
        '#markup' => '<hr><h5>' . $this->t('Navigation Element %num', ['%num' => $i]) . '</h5>',

        'title' => [
          '#type' => 'textfield',
          '#title' => $this->t('Title'),
          '#attributes' => [
            'placeholder' => $this->t('e.g., Decisions'),
          ],
          '#default_value' => (isset($side_nav_value[$i]['title'])) ? $side_nav_value[$i]['title'] : '',
        ],

        'icon' => [
          '#suffix' => (isset($side_nav_value[$i]['icon']) && $side_nav_value[$i]['icon'] != '') ? '<i class="fal fa-' . $side_nav_value[$i]['icon'] . ' fa-2x"></i>' : '',
          '#type' => 'textfield',
          '#title' => $this->t('Icon'),
          '#autocomplete_route_name' => 'fontawesome.autocomplete',
          '#attributes' => [
            'placeholder' => $this->t('Search for the icon you wish to use...'),
          ],
          '#default_value' => (isset($side_nav_value[$i]['icon'])) ? $side_nav_value[$i]['icon'] : '',
        ],

        'link' => [
          '#type' => 'textfield',
          '#title' => $this->t('Link'),
          '#attributes' => [
            'placeholder' => $this->t('e.g., /decisions'),
          ],
          '#default_value' => (isset($side_nav_value[$i]['link'])) ? $side_nav_value[$i]['link'] : '',
        ],

        'tooltip' => [
          '#type' => 'textfield',
          '#title' => $this->t('Tooltip Text'),
          '#attributes' => [
            'placeholder' => $this->t('e.g., Click here to search Decisions'),
          ],
          '#default_value' => (isset($side_nav_value[$i]['tooltip'])) ? $side_nav_value[$i]['tooltip'] : '',
        ],
      ];
    }

    $form['site_elements']['newsletter_privacy'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Newsletter Privacy Statement'),
      '#default_value' => $config->get('weaver_custom.site_elements.newsletter_privacy'),
    ];

    $form['site_elements']['mailchimp_signup'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Newsletter Call to Action'),
      '#default_value' => $config->get('weaver_custom.site_elements.mailchimp_signup'),
    ];

    $form['site_elements']['disclaimer'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Disclaimer'),
      '#format' => $config->get('weaver_custom.site_elements.disclaimer.format'),
      '#default_value' => $config->get('weaver_custom.site_elements.disclaimer.value'),
    ];

    $form['site_elements']['territorial'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Territorial Acknoweledgement'),
      '#format' => $config->get('weaver_custom.site_elements.territorial.format'),
      '#default_value' => $config->get('weaver_custom.site_elements.territorial.value'),
    ];

    $form['site_elements']['leading_decisions_intro'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Leading Decisions Block Intro Text'),
      '#format' => $config->get('weaver_custom.site_elements.leading_decisions_intro.format'),
      '#default_value' => $config->get('weaver_custom.site_elements.leading_decisions_intro.value'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'weaver_custom.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('weaver_custom.settings');

    $config->set('weaver_custom.site_elements.home', $form_state->getValue('home'));

    $config->set('weaver_custom.site_elements.all', $form_state->getValue('all'));

    $config->set('weaver_custom.site_elements.side_nav', $form_state->getValue('side_nav'));

    $config->set('weaver_custom.site_elements.newsletter_privacy', $form_state->getValue('newsletter_privacy'));

    $config->set('weaver_custom.site_elements.mailchimp_signup', $form_state->getValue('mailchimp_signup'));

    $config->set('weaver_custom.site_elements.disclaimer', $form_state->getValue('disclaimer'));

    $config->set('weaver_custom.site_elements.territorial', $form_state->getValue('territorial'));

    $config->set('weaver_custom.site_elements.leading_decisions_intro', $form_state->getValue('leading_decisions_intro'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // make sure no side nav elements are incomplete
    $site_nav = $form_state->getValue('side_nav');

    foreach ($site_nav as $key => $item) {
      // check if any empty values in element
      if (array_search('', $item) !== false) {
        // if so, ensure all are empty
        
        if (array_filter($item)) {
          $empty = array_search('', $item);
          $form_state->setErrorByName('side_nav][' . $key . '][' . $empty, $this->t('This value cannot be empty.'));
        }
      } 

      // check each link is either external or valid internal
      if (strpos($item['link'], 'http') === false && strpos($item['link'], '/') === false) {
          $form_state->setErrorByName('side_nav][' . $key . '][link', $this->t('This is not a valid link.<br/>Internal links must start with /.<br/>External links must start with https://'));
      }
    }


  }

}