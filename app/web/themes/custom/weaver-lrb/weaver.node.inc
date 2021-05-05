<?php

/**
 * @file
 * Functions to support theming in the SASS Starterkit subtheme.
 */

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\views\Views;

function weaver_preprocess_node(&$variables) {
  $node = $variables['node'];
  $type = $node->bundle();
  $variables['base_url'] = \Drupal::request()->getSchemeAndHttpHost();
  
  switch ($type) {

  	// News
  	case 'weaver_news':
      $node_categories = $node->get('field_lrb_news_category')->getValue();
		
      // set updated date if this is revision
      if ($node->getCreatedTime() != $node->getChangedTime()) {
        $variables['updated'] = $node->getChangedTime();
      }

      // get list of categories for this article
      if ($variables['view_mode'] == 'full' || $variables['view_mode'] == 'teaser') {
        $variables['node_categories'] = '';
        foreach ($node_categories as $key => $node_category) {
          $category_name = Term::load($node_category['target_id'])->label();
          $clean_category_name = \Drupal::service("pathauto.alias_cleaner")->cleanString($category_name);
          $url = Url::fromRoute('view.lrb_news.page_1', ['arg_0' => $clean_category_name]);

          $variables['node_categories'] .= Link::fromTextAndUrl($category_name, $url)->toString();
          if ($key != array_key_last($node_categories)) {
            $variables['node_categories'] .= ', ';
          }
        }
      }

      switch ($variables['view_mode']) {

        case 'lrb_card':

          $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);

          $params = \Drupal::routeMatch()->getParameters();

          // if page is news page
          // if ($params->get('node')) {
          //   $node_type = $params->get('node')->getType();

          //   if ($node_type == 'weaver_news') {
          //     $is_news_node = TRUE;
          //     $variables['show_text'] = FALSE;
          //   }
          // }

          // // if page is main news view page
          // if ($params->get('view_id') && $params->get('view_id') == 'lrb_news') {
          //   if ($params->get('display_id') == 'page_1') {
          //     $is_views_page = TRUE;
          //     $variables['show_text'] = FALSE;
          //     $variables['show_date'] = TRUE;
          //   }
          // }

    			$variables['custom_classes'] = 'col-sm-6 col-md-4';

    			$variables['card_title'] = Link::fromTextAndUrl($node->label(), $url)->toRenderable();
          $variables['card_title']['#attributes']['class'][] = 'no-underline';

    			// don't know why summary isn't being automatically found if summary is empty
    			$variables['card_text'] = $variables['content']['body'];

          $variables['card_surtitle'] = '';
          $variables['card_date'] = '';
          $variables['card_footers'] = [];

          // side style
          $side_footer = Link::fromTextAndUrl(t('Read article'), $url)->toRenderable();
          $side_footer['#attributes']['class'] = 'underline side-footer';
          $variables['card_footers'][] = $side_footer;

          // home page style
          $variables['card_title']['#attributes']['class'][] = 'link-blue';
          $home_footer = Link::fromTextAndUrl(t('Keep reading'), $url)->toRenderable();
          $home_footer['#attributes']['class'] = 'link-teal underline home-footer';
    			$variables['card_footers'][] = $home_footer;

          // news page style
          $variables['card_date'] =  \Drupal::service('date.formatter')->format($node->get('created')->value, 'short');
          $icon = t('<i class="fal fa-arrow-right fa-2x" title="Keep reading"></i>');
          $news_footer = Link::fromTextAndUrl(Markup::create($icon), $url)->toRenderable();
          $news_footer['#attributes']['class'] = 'underline news-footer';
          $variables['card_footers'][] = $news_footer;

          foreach ($node_categories as $key => $node_category) {
            $variables['card_surtitle'] .= Term::load($node_category['target_id'])->label();
            if ($key != array_key_last($node_categories)) {
              $variables['card_surtitle'] .= ', ';
            }
          }
  		    break;

        case 'full':
          // get categories list for sidebar links
          $variables['categories'] = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'lrb_news_categories']);
          $variables['news_page_url'] = Url::fromRoute('view.lrb_news.page_1')->toString();
          break;

      }
  	  break;

    // Topic Page
    case 'lrb_topic':
      if ($variables['view_mode'] == 'lrb_topics_grid') {

        foreach ($node->get('field_lrb_topic_sub')->getValue() as $subpage) {
          $variables['subpages'][] = [
            'title' => $subpage['title'],
            'url' => Url::fromUri($subpage['uri'])->toString(),
          ];
        }

        $variables['arrow'] = file_get_contents(drupal_get_path('theme', 'weaver') . '/images/get-help-arrow.svg');
      }
      break;
  	
    // Troubleshooter
    case 'lrb_troubleshooter':
      $url = $node->get('field_lrb_trouble_link')->uri;
      $variables['target'] = (UrlHelper::isExternal($url)) ? 'target="_blank"' : '';

      $icon = $node->get('field_weaver_icon')->getValue();
      if (isset($icon[0])) {
        $variables['icon'] = $icon[0]['icon_name'];
      }
      break;

  }
}