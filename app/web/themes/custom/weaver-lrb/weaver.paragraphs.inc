<?php

/**
 * @file
 * Functions to support theming in the SASS Starterkit subtheme.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\User;
use Drupal\views\Views;

function weaver_preprocess_paragraph(&$variables) {

  $paragraph = $variables['paragraph'];
  $type = $paragraph->getParagraphType()->id;

  $variables['custom_classes'] = '';

  $variables['anchor'] = '';
  if ($paragraph->hasField('field_wv_anchor')) {
    $variables['anchor'] = $paragraph->get('field_wv_anchor')->value;
  }

  $variables['custom_classes'] = [];

  switch ($type) {
  	
    case 'wv_accordion':
      // get list of all items for toggling
      $accordion_aria_ids = '';
      $accordion_items = $paragraph->get('field_wv_accordion_group');
      foreach ($accordion_items as $item) {
        $accordion_aria_ids .= 'section' . $item->target_id . ',';
      }
      $variables['accordion_aria_ids'] = $accordion_aria_ids;

      // if inside two-column container, don't use Bootstrap container div wrappers
      $variables['use_container'] = TRUE;
      if ($paragraph->getParentEntity()->bundle() == 'wv_container') {
        $variables['use_container'] = FALSE;
      }
      break;

    case 'wv_accordion_item':
      break;

    case 'lrb_box':
      $variables['col_classes'] = 'col-sm-6 col-lg-3';
      switch ($paragraph->getParentEntity()->get('field_lrb_link_box_layout')->value) {
        case 2:
          $variables['col_classes'] = 'col-lg-6';
          break;

        case 3:
          $variables['col_classes'] = 'col-lg-4';
          break;
      }
      $variables['box_link'] = Url::fromUri($paragraph->get('field_weaver_link')->uri)->toString();
      break;  

    case 'wv_container':
      $column_type = $paragraph->get('field_wv_container_type')->value;

      switch ($column_type) {

        case 1: // equal columns
          $variables['first_col_classes'] = 'col-md-6';
          $variables['second_col_classes'] = 'col-md-6';
          break;

        case 2: // 2/3 left and 1/3 right
          $variables['first_col_classes'] = 'col-md-6';
          $variables['second_col_classes'] = 'col-md-5 offset-md-1';
          break;

        case 3: // 1/3 left and 2/3 right
          $variables['first_col_classes'] = 'col-md-4';
          $variables['second_col_classes'] = 'col-md-7 offset-md-1';
          break;
      }

      break;

    // Leading Decisions
    case 'lrb_leading_decisions':
      $config = \Drupal::config('weaver_custom.settings');
      $variables['leading_decisions_intro'] = $config->get('weaver_custom.site_elements.leading_decisions_intro');
      break;

    case 'lrb_text_resources':
      $variables['links'] = [];

      $links = $paragraph->get('field_lrb_resources_links')->getValue();
      foreach ($links as $link) {
        $variables['links'][] = Link::fromTextAndUrl(Markup::create($link['title']), Url::fromUri($link['uri'], $link['options']));
      }
      break;

    case 'weaver_text':
      $variables['column_classes'] = 'col-md-10';
      $variables['in_container'] = FALSE;
      $variables['in_container_full'] = FALSE;
      if ($paragraph->getParentEntity()->bundle() == 'wv_container') {
        $variables['in_container'] = TRUE;
        $variables['column_classes'] = 'col-md-12';
      }        
      if ($paragraph->getParentEntity()->bundle() == 'wv_container_full') {
        $variables['in_container_full'] = TRUE;
        $variables['column_classes'] = 'col-md-12 px-0 px-md-3';
      }
      break;

    case 'weaver_twocol_text_image':
      // set class to align image side
      $align = $paragraph->get('field_weaver_col_options')->value;
      $align_class = 'image-left';
      $variables['image_offset'] = '';
      $variables['text_offset'] = 'pl-3 pl-lg-5';

      if ($align == 'right') {
        $align_class = 'image-right';
        $variables['image_offset'] = 'pl-lg-5';
        $variables['text_offset'] = 'pr-lg-5';
      }
      $variables['custom_classes'][] = $align_class;
      break;

  }
}