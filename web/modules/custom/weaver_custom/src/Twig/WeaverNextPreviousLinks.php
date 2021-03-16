<?php

namespace Drupal\weaver_custom\Twig;

use Drupal\node\Entity\Node;

/**
 * Class DefaultService.
 *
 * @package Drupal\weaver_custom
 */
class WeaverNextPreviousLinks extends \Twig_Extension {

	/**
   * {@inheritdoc}
   * This function must return the name of the extension. It must be unique.
   */
  public function getName() {
    return 'weaver_next_previous_links';
  }

  /**
   * In this function we can declare the extension function
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
      	'weaver_next_previous_links', 
        [$this, 'weaver_next_previous_links'],
        ['is_safe' => ['html']],
      )];
  }

  /**
   * The php function to load the links based on a node
   */
  public function weaver_next_previous_links(Node $node) {
    $type = $node->getType();

    // select nodes of type with created date greater than this one
    $next_query = \Drupal::entityQuery('node');
    $next_query->condition('type', $type);
    $next_query->condition('status', 1);
    $next_query->condition('created', $node->getCreatedTime(), '>');
    $next_query->range(0, 1);
    $next_query->sort('created');
    $next_result = $next_query->execute();

    if ($next_result) {
      $next_node = Node::load($next_result[array_key_first($next_result)]);
    }

    // // select nodes of type with created date less than this one    
    $prev_query = \Drupal::entityQuery('node');
    $prev_query->condition('type', $type);
    $prev_query->condition('status', 1);
    $prev_query->condition('created', $node->getCreatedTime(), '<');
    $prev_query->range(0, 1);
    $prev_query->sort('created', 'DESC');
    $prev_result = $prev_query->execute();

    if ($prev_result) {
      $prev_node = Node::load($prev_result[array_key_first($prev_result)]);
    }

  	$renderable = [
      '#next' => ($next_result) ? $next_node : NULL,
      '#previous' => ($prev_result) ? $prev_node : NULL,
		  '#theme' => 'weaver_next_previous_links',
		];

		$rendered = \Drupal::service('renderer')->renderPlain($renderable);

		return $rendered;
  }
}
