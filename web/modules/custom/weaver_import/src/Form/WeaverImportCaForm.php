<?php

namespace Drupal\weaver_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a Batch example Form.
 */
class WeaverImportCaForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'weaver_import_ca_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  
    $form['submit_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Import'),
    ]; 

    $form['dupes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check Dupes'),
    ];

    $form['industries'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Industries'),
    ];

    $form['terms'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Terms'),
    ];

    return $form;
  }
   

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    include_once(drupal_get_path('module', 'weaver_import') . '/data/ca.php');

    $trigger = $form_state->getTriggeringElement()['#id'];

    $batch = [
      'title' => t('Importing content...'),
      'operations' => [],
      'file' => drupal_get_path('module', 'weaver_import') . '/weaver_import.ca.inc',
      'init_message'     => t('Commencing'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message'    => t('An error occurred during processing'),
      // 'finished' => '\Drupal\weaver_import\WeaverImport::importCompleteCallback'
    ];

    switch ($trigger) {
      case 'edit-dupes':
        include_once(drupal_get_path('module', 'weaver_import') . '/data/ca.php');
        $batch['title'] = t('Checking for dupes');
        $function = 'weaver_import_ca_dupes';
        break;
    
      case 'edit-industries':
        include_once(drupal_get_path('module', 'weaver_import') . '/data/ca2.php');
        $batch['title'] = t('Adding industries');
        $function = 'weaver_import_ca_industries';
        break;

      case 'edit-terms':
        include_once(drupal_get_path('module', 'weaver_import') . '/data/ca2.php');
        $batch['title'] = t('Adding terms');
        $function = 'weaver_import_ca_terms';
        break;

      default: 
        include_once(drupal_get_path('module', 'weaver_import') . '/data/ca.php');
        $function = 'weaver_import_ca';
        break;
    }

    foreach ($data as $key => $info) {
      if (strpos($info->id, 'WYC') === 0) {
        $batch['operations'][] = [
          $function,
          [$info]
        ];
      }
    }

    batch_set($batch);
  }
}