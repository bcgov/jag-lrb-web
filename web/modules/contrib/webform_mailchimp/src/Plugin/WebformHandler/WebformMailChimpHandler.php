<?php

namespace Drupal\webform_mailchimp\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Mailchimp\MailchimpLists;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form submission to MailChimp handler.
 *
 * @WebformHandler(
 *   id = "mailchimp",
 *   label = @Translation("MailChimp"),
 *   category = @Translation("MailChimp"),
 *   description = @Translation("Sends a form submission to a MailChimp list."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformMailChimpHandler extends WebformHandlerBase {

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $token_manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->setTokenManager($container->get('webform.token_manager'));
    return $instance;
  }

  /**
   * Set Token Manager dependency
   */
  protected function setTokenManager(WebformTokenManagerInterface $token_manager) {
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $fields = $this->getWebform()->getElementsInitializedAndFlattened();
    $lists = mailchimp_get_lists();

    $email_summary = $this->configuration['email'];
    if (!empty($fields[$this->configuration['email']])) {
      $email_summary = $fields[$this->configuration['email']]['#title'];
    }
    $email_summary = '<strong>' . $this->t('Email') . ': </strong>' . $email_summary;


    $list_summary = $this->configuration['list'];
    if (!empty($lists[$this->configuration['list']])) {
      $list_summary = $lists[$this->configuration['list']]->name;
    }
    $list_summary = '<strong>' . $this->t('List') . ': </strong>' . $list_summary;

    $markup = "$email_summary<br/>$list_summary";
    return [
      '#markup' => $markup,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'list' => [],
      'email' => '',
      'double_optin' => TRUE,
      'mergevars' => '',
      'interest_groups' => [],
      'control' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $lists = mailchimp_get_lists();

    $options = [];
    foreach ($lists as $list) {
      $options[$list->id] = $list->name;
    }

    $form['mailchimp'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('MailChimp settings'),
      '#attributes' => ['id' => 'webform-mailchimp-handler-settings'],
    ];

    $form['mailchimp']['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh lists & groups'),
      '#ajax' => [
        'callback' => [$this, 'ajaxMailchimpListHandler'],
        'wrapper' => 'webform-mailchimp-handler-settings',
      ],
      '#submit' => [[get_class($this), 'maichimpUpdateConfigSubmit']],
    ];

    $form['mailchimp']['list'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('List'),
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select an option -'),
      '#default_value' => $this->configuration['list'],
      '#options' => $options,
      '#ajax' => [
        'callback' => [$this, 'ajaxMailchimpListHandler'],
        'wrapper' => 'webform-mailchimp-handler-settings',
      ],
      '#description' => $this->t('Select the list you want to send this submission to. Alternatively, you can also use the Other field for token replacement.'),
    ];

    $fields = $this->getWebform()->getElementsInitializedAndFlattened();
    $options = [];
    foreach ($fields as $field_name => $field) {
      if (in_array($field['#type'], ['email', 'webform_email_confirm'])) {
        $options[$field_name] = $field['#title'];
      }
    }

    $default_value = $this->configuration['email'];
    if (empty($this->configuration['email']) && count($options) == 1) {
      $default_value = key($options);
    }
    $form['mailchimp']['email'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Email field'),
      '#required' => TRUE,
      '#default_value' => $default_value,
      '#options' => $options,
      '#empty_option'=> $this->t('- Select an option -'),
      '#description' => $this->t('Select the email element you want to use for subscribing to the mailchimp list specified above. Alternatively, you can also use the Other field for token replacement.'),
    ];

    $options = [];
    foreach ($fields as $field_name => $field) {
      if (in_array($field['#type'],['checkbox', 'webform_toggle'])) {
        $options[$field_name] = $field['#title'];
      }
    }

    $form['mailchimp']['control'] = [
      '#type' => 'select',
      '#title' => $this->t('Control field'),
      '#empty_option' => $this->t('- Select an option -'),
      '#default_value' => $this->configuration['control'],
      '#options' => $options,
      '#description' => $this->t('DEPRECATED: Use Webform\'s core conditions tab instead.'),
    ];

    $form['mailchimp']['mergevars'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Merge vars'),
      '#default_value' => $this->configuration['mergevars'],
      '#description' => $this->t('You can map additional fields from your webform to fields in your MailChimp list, one per line. An example might be FNAME: [webform_submission:values:first_name]. You may use tokens.'),
    ];

    $form['mailchimp']['interest_groups'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Interest groups'),
      '#description' => $this->t('Displays interest groups for the selected list. Visit <a href="@url">Getting Started with Groups</a> for more information.', ['@url' => 'https://kb.mailchimp.com/lists/groups/getting-started-with-groups']),
    ];

    // Get selected interest group. Fallback to the saved one.
    $list_id = $form_state->getValue(['mailchimp', 'list'], $this->configuration['list']);
    if ($list_id) {
      $list = mailchimp_get_list($list_id);
      if (!empty($list->intgroups)) {
        $groups_default = $this->configuration['interest_groups'];

        if (empty($groups_default)) {
          $groups_default = [];
        }
        $form['mailchimp']['interest_groups'] += mailchimp_interest_groups_form_elements($list, $groups_default, NULL, 'admin');
      }
    }

    $form['mailchimp']['double_optin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Double opt-in'),
      '#default_value' => $this->configuration['double_optin'],
    ];

    $form['mailchimp']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return $form;
  }

  /**
   * Ajax callback to update Webform Mailchimp settings.
   */
  public static function ajaxMailchimpListHandler(array $form, FormStateInterface $form_state) {
    return $form['settings']['mailchimp'];
  }


  /**
   * Submit callback for the refresh button.
   */
  public function maichimpUpdateConfigSubmit(array $form, FormStateInterface $form_state) {
    // Trigger list and group category refetch by deleting lists cache.
    $cache = \Drupal::cache('mailchimp');
    $cache->delete('lists');
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values['mailchimp'][$name])) {
        // Filter out unset interest group ids so mailchimp_subscribe_process()
        // doesn't subscribe all groups.
        if ($name == 'interest_groups') {
          if (!empty($values['mailchimp'][$name])) {
            $filtered_groups = [];
            foreach ($values['mailchimp'][$name] as $group_id => $interest_group) {
              if ($group_subcriptions = array_filter($interest_group)) {
                $filtered_groups[$group_id] = $group_subcriptions;
              }
            }
            $this->configuration[$name] = $filtered_groups;
          }
        }
        else {
          $this->configuration[$name] = $values['mailchimp'][$name];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // If update, do nothing
    if ($update) {
      return;
    }

    $fields = $webform_submission->toArray(TRUE);

    // If there's a checkbox configured, check for its value
    if (!empty($this->configuration['control']) && empty($fields['data'][$this->configuration['control']])) {
      return;
    }

    $configuration = $this->tokenManager->replace($this->configuration, $webform_submission);

    // Email could be a webform element or a string/token.
    if (!empty($fields['data'][$configuration['email']])) {
      $email = $fields['data'][$configuration['email']];
    }
    else {
      $email = $configuration['email'];
    }

    $mergevars = Yaml::decode($configuration['mergevars']) ?? [];

    // Allow other modules to alter the merge vars.
    // @see hook_mailchimp_lists_mergevars_alter().
    $entity_type = 'webform_submission';
    \Drupal::moduleHandler()->alter('mailchimp_lists_mergevars', $mergevars, $webform_submission, $entity_type);
    \Drupal::moduleHandler()->alter('webform_mailchimp_lists_mergevars', $mergevars, $webform_submission, $this);

    $handler_link = Link::createFromRoute(
      t('Edit handler'),
      'entity.webform.handler.edit_form',
      [
        'webform' => $this->getWebform()->id(),
        'webform_handler' => $this->getHandlerId(),
      ]
    )->toString();

    $submission_link = $webform_submission->toLink($this->t('Edit'), 'edit-form')->toString();

    $context = [
      'link' => $submission_link . ' / ' . $handler_link,
      'webform_submission' => $webform_submission,
      'handler_id' => $this->getHandlerId(),
    ];

    if (!empty($configuration['list']) && !empty($email)) {
      $member_data = mailchimp_get_memberinfo($configuration['list'], $email, TRUE);

      // If the user is already subscribed, do not set it back to pending
      $double_optin = $configuration['double_optin'];
      if (!empty($member_data->status) && $member_data->status == MailchimpLists::MEMBER_STATUS_SUBSCRIBED) {
        $double_optin = FALSE;
      }

      mailchimp_subscribe($configuration['list'], $email, array_filter($mergevars, 'strlen'), $configuration['interest_groups'], $double_optin);
    }
    else {
      if (empty($configuration['list'])) {
        \Drupal::logger('webform_submission')->warning(
          'No mailchimp list was provided to the handler.',
          $context
        );
      }
      if (empty($email)) {
        \Drupal::logger('webform_submission')->warning(
          'No email address was provided to the handler.',
          $context
        );
      }
    }
  }

}
