<?php

namespace Drupal\social_media\Event;

/**
 * The '\Symfony\Component\EventDispatcher\Event' class is deprecated and to
 * manage this, drupal core has added an intermediary class
 * '\Drupal\Component\EventDispatcher\Event' in drupal 9.1.x version. In order
 * to support Drupal 8 and Drupal 9.0.x versions, we must find whichever class
 * is available and extend it.
 */
if (class_exists('\Drupal\Component\EventDispatcher\Event')) {
  class EventProxy extends \Drupal\Component\EventDispatcher\Event {
    // Using the Drupal intermediary class available from 9.1.x and later.
  }
}
elseif (class_exists('\Symfony\Component\EventDispatcher\Event')) {
  class EventProxy extends \Symfony\Component\EventDispatcher\Event {
    // Using the deprecated Symfony 3 class for Drupal 8 and 9.0.x.
  }
}
else {
  throw new \Exception('Error resolving Event class.');
}

/**
 * Class SocialMediaEvent.
 */
class SocialMediaEvent extends EventProxy {

  /**
   * TODO describe element.
   *
   * @var array
   */
  protected $element;

  /**
   * Constructor.
   *
   * @param array $element
   *   TODO describe what element is.
   */
  public function __construct(array $element) {
    $this->element = $element;
  }

  /**
   * Return the element.
   *
   * @return array
   *   The element.
   */
  public function getElement() {
    return $this->element;
  }

  /**
   * Element setter.
   *
   * @param array $element
   *   TODO describe what element is.
   */
  public function setElement(array $element) {
    $this->element = $element;
  }

}
