<?php

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

function weaver_preprocess_views_view(&$variables) {
  $view = $variables['view'];

  $variables['views_content_classes'] = '';

  switch ($view->id()) {

    case 'lrb_collective_agreements':

      $variables['views_content_classes'] = 'row';

      // loads search params for the grouping links
      if (count($_GET)) {
        $search_params = '?';
        foreach ($view->exposed_raw_input as $key => $value) {
          if (isset($_GET[$key])) {
            $search_params .= $key . '=' . $_GET[$key] . '&';
          }
        }
  
        $variables['search_params'] = rtrim($search_params, '&');
      }
      break;

    case 'lrb_decisions':
        $variables['views_content_classes'] = 'row';
        break;
        
    case 'lrb_hearings':
      // expand search fields if URL shows a search was performed
      if (is_array($_GET) && count($_GET)) {
        $variables['search_params'] = TRUE;
      }
      break;

    case 'lrb_news':
      
      switch ($view->getDisplay()->display['id']) {
      
        case 'block_1': // home page
          $variables['views_content_classes'] = 'home-page-display';
          break;

        case 'page_1': // /updates page
          $variables['current_url'] = Url::fromRoute('<current>')->toString();

          $variables['categories'] = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'lrb_news_categories']);

          $variables['news_page_url'] = Url::fromRoute('view.lrb_news.page_1')->toString();

          $variables['views_content_classes'] = 'bg-grey-lighter box-shadow';
          break;

      }
      break;

    case 'lrb_site_search':
      $variables['views_content_classes'] = 'col-md-8';
      break;

    case 'lrb_topics_grid':
      $variables['views_content_classes'] = 'row';
      break;

    case 'lrb_troubleshooter':
      switch ($view->getDisplay()->display['id']) {

        case 'block_2':
          $variables['views_content_classes'] = 'row pt-3';
          break;
      }
      break;
  }
}

function weaver_preprocess_views_exposed_form(&$variables) {
  $variables['form_id'] = $variables['form']['#id'];
}

function weaver_preprocess_views_view_field(&$variables) {
  $view = $variables['view'];
  $field = $variables['field'];
  // 
  switch ($view->id()) {
    case 'lrb_collective_agreements':
      switch ($field->field) {

        case 'field_lrb_ca_start':
          $ca = $variables['row']->_entity;
          $start = $ca->get('field_lrb_ca_start')->value;
          $end = $ca->get('field_lrb_ca_end')->value;

          $output = weaver_custom_output_term($start, $end);

          $variables['output'] = $output;
          break;
      }
      break;
    
    case 'lrb_decisions':

      switch ($field->field) {

        case 'field_lrb_decision_id':
          // link to either Salesforce PDF or internal PDF file
          $url = null;
          $original_output = '<i class="fal fa-file-pdf"></i>' . $variables['output'];
          if ($variables['row']->_entity->get('field_lrb_decisions_sfurl')->value != '') {
            $url = Url::fromUri($variables['row']->_entity->get('field_lrb_decisions_sfurl')->value);

          } else {

            $file = $variables['row']->_entity->get('field_media_file')->entity;
            if (isset($file)) {
              $filesize = weaverformatBytes($file->getSize(), 0);
              $original_output .= ' (' . $filesize . ')';
              $url = Url::fromRoute(
                'media_entity_download.download',
                [
                  'media' => $variables['row']->_entity->id(),
                  'inline' => null,
                ]
              );
            }
          }

          if ($url != null && $url != '') {
            $link = Link::fromTextAndUrl(Markup::create($original_output), $url)->toRenderable();
            $link['#attributes'] = ['target' => '_blank', 'data-extension' => 'pdf'];
            $variables['output'] = $link; 
          }
          break;
      }
      break;

    case 'lrb_site_search':
      switch ($field->field) {

        case 'search_api_excerpt':
          $variables['result_path'] = ''; 
          $variables['listing_type'] = '';

          $entity = $variables['row']->_object->getEntity();
          $variables['entity'] = $entity;

          $variables['date'] = \Drupal::service('date.formatter')->format($entity->changed->value, 'medium');
          $variables['date_label'] = t('Last updated:');

          if ($entity->getEntityTypeId() == 'media') {
            $url = Url::fromRoute(
              'media_entity_download.download',
              [
                'media' => $entity->id(),
              ],
            );

            $variables['result_path'] = $url->toString();
            $variables['listing_type'] = $entity->bundle->entity->label();
          
            // change $output to document details
            switch ($entity->bundle->target_id) {
              case 'lrb_collective_agreement':
                $employer = $entity->get('field_lrb_ca_employer')->entity;
                $variables['date'] = '';
                $variables['date_label'] = '';

                $duration = null;
                $term = null;
                if ($entity->get('field_lrb_ca_start')->value != null && $entity->get('field_lrb_ca_end')->value != null) {
                  $start = $entity->get('field_lrb_ca_start')->value;
                  $end = $entity->get('field_lrb_ca_end')->value;
                  $term = weaver_custom_output_term($start, $end);
                  $duration = weaver_custom_output_duration($start, $end);
                }

                $industries = $employer->get('field_lrb_employer_industries')->getValue();
                if (count($industries)) {
                  $industry_labels = '';
                  foreach ($industries as $industry) {
                    $industry_term = Term::load($industry['target_id']);
                    if ($industry_term) {
                      $industry_labels .= $industry_term->label() . ', ';
                    }
                  }
                  $industry_labels = rtrim(trim($industry_labels), ',');
                }

                $excerpt_replacement = t('<span class="mr-3"><label>Employer:</label> @employer</span><br/><span class="mr-3"><label>Union:</label> @union</span><br/><span class="mr-3"><label>Term:</label> @term</span><span class="mr-3"><label>Duration:</label> @duration</span><span class="mr-3"><label>Industries:</label> @industries', 
                  [
                    '@employer' => $employer->label(),
                    '@union' => ($entity->get('field_lrb_ca_union')->target_id) ? $entity->get('field_lrb_ca_union')->entity->label() : 'N/A',
                    '@term' => ($term != null) ? $term : 'N/A',
                    '@duration' => ($duration != null) ? $duration : 'N/A',
                    '@industries' => (count($industries)) ? $industry_labels : 'N/A',
                  ]
                );
                $variables['output'] = $excerpt_replacement;
                break;

              case 'lrb_decision':

                // check if SF link exists and if so, use this link instead of pdf link
                if ($entity->get('field_lrb_decisions_sfurl')->value != '') {
                  $variables['result_path'] = $entity->get('field_lrb_decisions_sfurl')->value;
                }

                $variables['date'] = $entity->get('field_lrb_decision_date')->value;
                $variables['date_label'] = t('Date:');

                $excerpt_replacement = t('@parties', 
                  [
                    '@parties' => $entity->get('field_lrb_decision_parties')->value,
                  ]
                );
                $variables['output'] = $excerpt_replacement;
                break;
            }
          }

          if ($entity->getEntityTypeId() == 'node') {
            $url = Url::fromRoute(
              'entity.node.canonical', 
              [
                'node' => $variables['entity']->id()
              ]
            );
            
            $variables['result_path'] = $url->toString();
            $variables['listing_type'] = $entity->type->entity->label();

            // special format for Hearings
            switch ($entity->getType()) {

              case 'lrb_hearings':
                $date = strtotime($entity->field_lrb_hearing_date->value)-28800; // compensates for date coming in wrong timezone
                $variables['date'] = \Drupal::service('date.formatter')->format($date, 'long_date_with_time');
                $variables['date_label'] = t('Date and time:');

                $excerpt_replacement = t('<span class="mr-3"><label>Status:</label> @status</span><span class="mr-3"><label>Type:</label> @type</span><span class="mr-3"><label>Location:</label> @location</span><br/><span class="mr-3"><label>Section(s):</label> @sections</span><span class="mr-3"><label>Case no.:</label> @number', 
                  [
                    '@date' => $entity->get('field_lrb_hearing_date')->value,
                    '@location' => ($entity->get('field_lrb_hearing_location')->value) ? $entity->get('field_lrb_hearing_location')->value : 'N/A',
                    '@number' => ($entity->get('field_lrb_hearings_number')->value) ? $entity->get('field_lrb_hearings_number')->value : 'N/A',
                    '@section' => ($entity->get('field_lrb_hearing_sections')->value) ? $entity->get('field_lrb_hearing_sections')->value : 'N/A',
                    '@status' => ($entity->get('field_lrb_hearing_status')->value) ? $entity->get('field_lrb_hearing_status')->value : 'N/A',
                    '@type' => ($entity->get('field_lrb_hearing_type')->value) ? $entity->get('field_lrb_hearing_type')->value : 'N/A',
                  ]
                );
                $variables['output'] = $excerpt_replacement;
                break;

              case 'weaver_news':
                $variables['date'] = \Drupal::service('date.formatter')->format($entity->created->value, 'medium');
                $variables['date_label'] = t('Published:');
                break;
            }
          }

          break;
      }
      break;
  } 
}