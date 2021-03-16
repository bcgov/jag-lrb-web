<?php

namespace Drupal\weaver_custom\Twig;

use Drupal\node\Entity\Node;

/**
 * Class DefaultService.
 *
 * @package Drupal\pacs_custom
 */
class WeaverCustomTwigExtension extends \Twig_Extension {

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

    $next = 'next';
    $previous = 'previous';

  	$renderable = [
      '#next' => $next,
      '#previous' => $previous,
		  '#theme' => 'weaver_next_previous_links',
		];

		$rendered = \Drupal::service('renderer')->renderPlain($renderable);

		return $rendered;
  }
}
