<?php

namespace Drupal\smart_date\Normalizer;

use Drupal\hal\Normalizer\TimestampItemNormalizer;
use Drupal\smart_date\Plugin\Field\FieldType\SmartDateItem;
use DateTimeZone;
use DateTime;

/**
 * Enhances the smart date field so it can be denormalized.
 */
class SmartDateNormalizer extends TimestampItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = SmartDateItem::class;

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $field = parent::denormalize($data, $class, $format, $context);

    // Repeat the denormalization of date string for end_value.
    $timezone = new DateTimeZone('UTC');
    $date = DateTime::createFromFormat($data['format'], $data['end_value'], $timezone);
    $field->get('end_value')->setValue($date->getTimestamp());

    return $field;
  }

}
