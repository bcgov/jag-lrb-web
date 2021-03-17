<?php

namespace Drupal\weaver_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a Batch example Form.
 */
class WeaverImportDecisionsForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'weaver_import_decisions_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['year'] = [
      '#title' => t('Year'),
      '#type' => 'textfield',
      // '#default_value' => 2004,
    ];

    $form['submit_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Import'),
    ];

    $form['check'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check Files'),
    ];

    $form['update_dates'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Dates'),
    ];

    $form['update_dates_v2'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Dates V2'),
    ];

    $form['update_descriptions'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Descriptions'),
    ];

    $form['update_descriptions_v2'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Descriptions V2'),
    ];

    $form['update_descriptions_v3'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Descriptions V3'),
    ];

    $form['update_ids'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update IDs and Descriptions'),
    ];

    $form['link_decisions'] = [
      '#type' => 'submit',
      '#value' => $this->t('Link Decisions'),
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
    $year = $form_state->getValue('year');

    $trigger = $form_state->getTriggeringElement()['#id'];
    $operations = [];
    switch ($trigger) {

      case 'edit-link-decisions':
        // get all Decisions
        $query = \Drupal::entityQuery('media')
          ->condition('bundle', 'lrb_decision')
          // ->range(0, 1000)
          ;
        $mids = $query->execute();

        $operations = [];
        foreach ($mids as $key => $mid) {
          $operations[] = [
            'weaver_link_decisions',
            [$mid]
          ];
        }

        break;
      
      case 'edit-update-dates':

        // get all Decisions for the year
        $query = \Drupal::entityQuery('media')
          ->condition('bundle', 'lrb_decision')
          ->condition('field_lrb_decision_date', $year . '%', 'like')
          // ->range(0, 10)
          ;
        $mids = $query->execute();

        $operations = [];
        foreach ($mids as $key => $mid) {
          $operations[] = [
            'weaver_import_decisions_update_dates',
            [$mid, $year]
          ];
        }

        break;

      case 'edit-update-dates-v2':

        // get all Decisions for the year
        $query = \Drupal::entityQuery('media')
          ->condition('bundle', 'lrb_decision')
          ->condition('field_lrb_decision_date', $year . '%', 'like')
          // ->range(0, 10)
          ;
        $mids = $query->execute();

        $operations = [];
        foreach ($mids as $key => $mid) {
          $operations[] = [
            'weaver_import_decisions_update_dates_v2',
            [$mid, $year]
          ];
        }

        break;

      case 'edit-update-descriptions':

        // get all Decisions for the year
        $query = \Drupal::entityQuery('media')
          ->condition('bundle', 'lrb_decision')
          ->condition('field_lrb_decision_date', $year . '%', 'like')
          // ->range(0, 100)
          ;
        $mids = $query->execute();

        $operations = [];
        foreach ($mids as $key => $mid) {
          $operations[] = [
            'weaver_import_decisions_update_descriptions',
            [$mid, $year]
          ];
        }

        break;

      case 'edit-update-descriptions-v2':

        // get all Decisions for the year
        $query = \Drupal::entityQuery('media')
          ->condition('bundle', 'lrb_decision')
          ->condition('field_lrb_decision_date', $year . '%', 'like')
          // ->range(0, 100)
          ;
        $mids = $query->execute();

        $operations = [];
        foreach ($mids as $key => $mid) {
          $operations[] = [
            'weaver_import_decisions_update_descriptions_v2',
            [$mid, $year]
          ];
        }

        break;

      case 'edit-update-descriptions-v3':

        // get all Decisions for the year
        $query = \Drupal::entityQuery('media')
          ->condition('bundle', 'lrb_decision')
          // ->range(0, 100)
          ;
        $mids = $query->execute();

        $operations = [];
        foreach ($mids as $key => $mid) {
          $operations[] = [
            'weaver_import_decisions_update_descriptions_v3',
            [$mid]
          ];
        }

        break;

      case 'edit-update-ids':
        // get all Decisions
        $query = \Drupal::entityQuery('media')
          ->condition('bundle', 'lrb_decision')
          // ->range(0, 1000)
          ;
        $mids = $query->execute();

        $operations = [];
        foreach ($mids as $key => $mid) {
          $operations[] = [
            'weaver_update_ids',
            [$mid]
          ];
        }

        break;

      case 'edit-check':
      case 'edit-submit-button':
        // for 2006+, get precise date and index from table

        // some entries have a second date in brackets and the JSON will look like this:
        /*
         {
           "id": "B100/2006",
           "date": "April 27, 2006",
           "index": "University of British Columbia -and- Canadian Union of Public Employees, Local 116 (U.B.C. Employees)"
         },
         {
           "id": "",
           "date": "(May 10, 2006)",
           "index": ""
         },

         */

        if ($year > 2005) {
          $data = file_get_contents('public://pdfs/decisions/' . $year . '/' . $year . '.json');
          $data = json_decode($data);

          foreach ($data as $key => $entry) {
            // adjust ID
            $data[$key]->id = str_replace('/' . $year, '', $entry->id);

            // adjust date to correct format
            // checks if this entry is for a linked date, where ID and Index would be empty
            if ($entry->id != '' && $entry->index != '') {
              // dpm($entry->date);
              $date_obj = \DateTime::createFromFormat('d-M-y', $entry->date);
              if ($date_obj == FALSE) {
                $date_obj = new \DateTime($entry->date);
              }
              $data[$key]->date = $date_obj->format('Y-m-d');
            } else {

              // if linked date, add it to previous $key as 'linked_date'
              $linked_date = str_replace('(', '', $entry->date);
              $linked_date = str_replace(')', '', $linked_date);
              
              $linked_date_obj = new \DateTime($linked_date);
              $linked_date = $linked_date_obj->format('Y-m-d');

              $data[$key-1]->linked_date = $linked_date;
              unset($data[$key]);
            }
          }
        
        } else {

          // pre 2006, just use txt file in folder
          $data = file_get_contents('public://pdfs/decisions/' . $year . '/' . $year . '.txt');
          $data = explode(PHP_EOL, $data);

          // get rid of potential empty lines
          foreach ($data as $key => $entry) {
            if ($entry == '') {
              unset($data[$key]);
            }
          }
        }

        if ($trigger == 'edit-check') {
          
          dpm($data);

        } else {

          $operations = [];
          foreach ($data as $key => $info) {
            $operations[] = [
              'weaver_import_decisions',
              [$info, $year]
            ];
          }
        }

        break;
    }

    if ($trigger != 'edit-check' && count($operations)) {

      $batch = [
        'title' => t('Importing content...'),
        'operations' => $operations,
        'file' => drupal_get_path('module', 'weaver_import') . '/weaver_import.decisions.inc',
        'init_message'     => t('Commencing'),
        'progress_message' => t('Processed @current out of @total.'),
        'error_message'    => t('An error occurred during processing'),
        'finished' => 'weaver_import_decisions_complete'
      ];

      batch_set($batch);
    }
  }
}