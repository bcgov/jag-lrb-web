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

function weaver_preprocess_field(&$variables) {

  $field = $variables['element'];
  $field_name = $variables['field_name'];

  $object = $variables['element']['#object'];
  $bundle = $field['#bundle'];
  $items = $variables['items'];

  switch ($field_name) {

    case 'field_weaver_buttons':
    case 'field_lrb_resources_footer':
      foreach ($items as $key => $item) {
        $variables['items'][$key]['content']['#title'] = \Drupal\Core\Render\Markup::create($variables['items'][$key]['content']['#title'] . file_get_contents(drupal_get_path('theme', 'weaver') . '/images/get-help-arrow.svg'));
        $variables['items'][$key]['attributes']->addClass('text-right');
        $variables['items'][$key]['content']['#options']['attributes']['class'] = ['arrow-link'];
      }
      break;

    case 'field_weaver_link':
      if ($bundle == 'lrb_leading_decision') {
        foreach ($items as $key => $item) {
          $variables['items'][$key]['content']['#title'] = Markup::create('<i class="fal fa-file-pdf mr-2"></i>' . $variables['items'][$key]['content']['#title']);
        }
      }
      break;

    case 'field_weaver_links':
      if ($bundle == 'lrb_info_card') {
        foreach ($items as $key => $items) {
          $variables['items'][$key]['content']['#options']['attributes']['class'] = ['arrow-link font-weight-bold'];
        }
      }
      break;
  }
}