<?php

namespace Drupal\weaver_custom\Twig;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Class DefaultService.
 *
 * @package Drupal\weaver_custom
 */
class WeaverNewsCategoryUrl extends \Twig_Extension {

	/**
   * {@inheritdoc}
   * This function must return the name of the extension. It must be unique.
   */
  public function getName() {
    return 'weaver_news_category_url';
  }

  /**
   * In this function we can declare the extension function
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
      	'weaver_news_category_url', 
        [$this, 'weaver_news_category_url'],
        ['is_safe' => ['html']],
      )];
  }

  /**
   * The php function to load the link based on a term id
   */
  public function weaver_news_category_url(int $term_id) {
    $category_name = Term::load($term_id)->label();
    $clean_category_name = \Drupal::service("pathauto.alias_cleaner")->cleanString($category_name);
    $url = Url::fromRoute('view.lrb_news.page_1', ['arg_0' => $clean_category_name]);

    return $url->toString();
  }

}