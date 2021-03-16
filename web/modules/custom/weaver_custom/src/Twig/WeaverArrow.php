<?php

namespace Drupal\weaver_custom\Twig;

/**
 * Class DefaultService.
 *
 * @package Drupal\pacs_custom
 */
class WeaverArrow extends \Twig_Extension {

	/**
   * {@inheritdoc}
   * This function must return the name of the extension. It must be unique.
   */
  public function getName() {
    return 'weaver_arrow';
  }

  /**
   * In this function we can declare the extension function
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
      	'weaver_arrow', 
        [$this, 'weaver_arrow'],
        ['is_safe' => ['html']],
      )];
  }

  /**
   * The php function to load a given block
   */
  public function weaver_arrow() {

  	$renderable = [
		  '#theme' => 'weaver_arrow',
		];

		$rendered = \Drupal::service('renderer')->renderPlain($renderable);

		return $rendered;
  }
}
