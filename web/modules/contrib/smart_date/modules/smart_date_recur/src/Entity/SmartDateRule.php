<?php

namespace Drupal\smart_date_recur\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\smart_date\Entity\SmartDateFormat;
use Drupal\smart_date\SmartDateTrait;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\Constraint\AfterConstraint;
use Recurr\Transformer\Constraint\BeforeConstraint;
use Recurr\Transformer\Constraint\BetweenConstraint;

/**
 * Defines the Smart date rule entity.
 *
 * @ingroup smart_date_recur
 *
 * @ContentEntityType(
 *   id = "smart_date_rule",
 *   label = @Translation("Smart date recurring rule"),
 *   handlers = {
 *     "storage" = "Drupal\smart_date_recur\RuleStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "remove" = "Drupal\smart_date_recur\Form\SmartDateRemoveInstanceForm",
 *     }
 *   },
 *   base_table = "smart_date_rule",
 *   data_table = "smart_date_rule_data",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "rule",
 *   },
 *   links = {
 *     "remove-form" = "/admin/content/smart_date_recur/{smart_date_rule}/instance/remove/{index}",
 *   },
 * )
 */
class SmartDateRule extends ContentEntityBase {

  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * The frequency of recurrence.
   *
   * @var string
   */
  protected $freq = '';

  /**
   * The limit to recurrence.
   *
   * @var string
   */
  protected $limit = '';

  /**
   * An imploded array of extra parameters, such as increment values.
   *
   * @var string
   */
  protected $parameters = '';

  /**
   * The assembled rule, as a string.
   *
   * @var string
   */
  protected $rule = '';

  /**
   * The timestamp for the first instance.
   *
   * @var int
   */
  protected $start = NULL;

  /**
   * The timezone for the field/rule.
   *
   * @var string
   */
  protected $timezone = NULL;

  /**
   * {@inheritdoc}
   */
  public function getRule() {
    return $this->get('rule')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRule($rule) {
    $this->set('rule', $rule);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  private function makeRuleFromParts() {
    $repeat = $this->get('freq')->getString();
    if (empty($repeat)) {
      return FALSE;
    }

    $rule = new FormattableMarkup('RRULE:FREQ=:freq', [':freq' => $repeat]);
    // Processing for extra parameters e.g. INCREMENT, BYMONTHDAY, etc.
    $params = $this->get('parameters')->getString();
    if (!empty($params)) {
      $rule .= ';' . $params;
    }
    // If a limit has been set, add it to the rule definition.
    $end = $this->get('limit')->getString();
    if (!empty($end)) {
      $rule .= ';' . $end;
      if (strpos($end, 'UNTIL') === 0) {
        // Add midnight to specify the end of the last day.
        $rule .= 'T235959';
      }
    }
    $this->setRule($rule);
    return $rule;
  }

  /**
   * Retrieve all overrides created for this rule.
   */
  public function getRuleOverrides() {
    $result = \Drupal::entityQuery('smart_date_override')
      ->condition('rrule', $this->id())
      ->execute();
    $overrides = [];
    if ($result && $overrides_return = SmartDateOverride::loadMultiple($result)) {
      foreach ($overrides_return as $override) {
        $index = $override->rrule_index->getString();
        $overrides[$index] = $override;
      }
    }
    return $overrides;
  }

  /**
   * Provide a formatted array of instances, with any overrides applied.
   */
  public function getRuleInstances($before = NULL, $after = NULL) {
    $instances = $this->makeRuleInstances($before, $after)->toArray();
    $overrides = $this->getRuleOverrides();

    $formatted = [];
    foreach ($instances as $instance) {
      $index = $instance->getIndex();
      // Check for an override.
      if (isset($overrides[$index])) {
        // Check for rescheduled, overridden, or cancelled
        // and don't use default value.
        $override = $overrides[$index];
        if ($override->entity_id->getString()) {
          // Overridden, retrieve appropriate entity.
          $override_type = 'overridden';
          $override = $entity_storage
            ->load($override['entity_id']);
          $field = $override->get($field_name);
          // TODO: drill down and retrieve, replace values.
        }
        elseif ($override->value->getString()) {
          // Rescheduled, use values from override.
          $formatted[$index] = [
            'value' => $override->value->getString(),
            'end_value' => $override->end_value->getString(),
            'oid' => $override->id(),
          ];
        }
        else {
          // Cancelled.
        }
        continue;
      }
      // Use the generated instance as-is.
      $formatted[$index] = [
        'value' => $instance->getStart()->getTimestamp(),
        'end_value' => $instance->getEnd()->getTimestamp(),
      ];
    }
    // Return the assembled array.
    return $formatted;
  }

  /**
   * Generate default instances based on rule structure.
   */
  public function getNewInstances() {
    $month_limit = $this->getMonthsLimit($this);
    $before = strtotime('+' . (int) $month_limit . ' months');
    $instances = $this->getStoredInstances();
    $last_instance = end($instances);
    $new_instances = $this->makeRuleInstances($before, $last_instance['value']);
    return $new_instances;
  }

  /**
   * Helper function to parse instances from storage and return as an array.
   */
  public function getStoredInstances() {
    $instances = $this->instances->getValue();
    if (is_array($instances)) {
      $instances = $instances[0]['data'];
    }
    return $instances;
  }

  /**
   * Generate default instances based on rule structure.
   */
  public function makeRuleInstances($before = NULL, $after = NULL) {
    $rrule = $this->getAssembledRule();
    if (empty($rrule)) {
      // Required elements missing, so abort.
      return FALSE;
    }

    $constraint = NULL;
    if ($before && $after) {
      $constraint = new BetweenConstraint(new \DateTime('@' . $after), new \DateTime('@' . $before));
    }
    elseif ($before) {
      $constraint = new BeforeConstraint(new \DateTime('@' . $before));
    }
    elseif ($after) {
      $constraint = new AfterConstraint(new \DateTime('@' . $after));
    }

    $transformer = new ArrayTransformer();
    $instances = $transformer->transform($rrule, $constraint);

    // TODO: Convert the generated instances into an array for later processing.
    return $instances;
  }

  /**
   * Retrieve the entity to which the rule is attached.
   */
  public function getParentEntity($id_only = FALSE) {
    // Retrieve the entity using the rule id.
    $rid = $this->id();
    if (empty($rid)) {
      return FALSE;
    }
    $entity_type = $this->entity_type->getString();

    $field_name = $this->field_name->getString();
    $result = \Drupal::entityQuery($entity_type)
      ->condition($field_name . '.rrule', $rid)
      ->execute();

    $id = array_pop($result);
    if ($id_only) {
      return $id;
    }
    $entity_manager = \Drupal::entityTypeManager($entity_type);
    $entity_storage = $entity_manager
      ->getStorage($entity_type);

    $entity = $entity_storage
      ->load($id);
    return $entity;
  }

  /**
   * Get the RRule object.
   */
  public function getAssembledRule() {
    $rule = $this->makeRuleFromParts();
    if (empty($rule)) {
      // Required elements missing, so abort.
      return FALSE;
    }
    // TODO: proper timezone handling, allowing for field override.
    $tz_string = \Drupal::config('system.date')->get('timezone')['default'];
    $timezone = new \DateTimeZone($tz_string);

    $start = new \DateTime('@' . $this->get('start')->getString(), $timezone);
    $start->setTimezone($timezone);

    $end = new \DateTime('@' . $this->get('end')->getString(), $timezone);
    $end->setTimezone($timezone);

    $rrule = new Rule($rule, $start, $end);
    return $rrule;
  }

  /**
   * Use the transformer to get text output of the rule.
   */
  public function getTextRule() {
    $freq = $this->get('freq')->getString();
    $repeat = $freq;
    $params = $this->getParametersArray();
    $day_labels = [
      'MO' => $this->t('Monday'),
      'TU' => $this->t('Tuesday'),
      'WE' => $this->t('Wednesday'),
      'TH' => $this->t('Thursday'),
      'FR' => $this->t('Friday'),
      'SA' => $this->t('Saturday'),
      'SU' => $this->t('Sunday'),
    ];
    // Convert the stored repeat value to something human-readable.
    if ($params['interval'] && $params['interval'] > 1) {
      switch ($repeat) {
        case 'MINUTELY':
          $period = $this->t('minutes');
          break;

        case 'HOURLY':
          $period = $this->t('hours');
          break;

        case 'DAILY':
          $period = $this->t('days');
          break;

        case 'WEEKLY':
          $period = $this->t('weeks');
          break;

        case 'MONTHLY':
          $period = $this->t('months');
          break;

        case 'YEARLY':
          $period = $this->t('years');
          break;

      }
      $repeat = $this->t('every :num :period', [':num' => $params['interval'], ':period' => $period]);
    }
    else {
      $frequency_labels = static::getFrequencyLabels();
      $repeat = $frequency_labels[$repeat];
    }
    $start_ts = $this->start;
    // TODO: proper timezone handling, allowing for field override.
    $tz_string = \Drupal::config('system.date')->get('timezone')['default'];
    $format = SmartDateFormat::load('time_only');

    $time_set = FALSE;
    // Add extra time parameters, if set.
    if ($params['byhour']) {
      $current_time = DrupalDateTime::createFromTimestamp($start_ts, $tz_string);
      $ranges = $this->makeRanges($params['byhour']);
      $range_text = [];
      foreach ($ranges as $range) {
        $range_start = array_shift($range);
        $current_time->setTime($range_start, 0);
        $range_start_ts = $current_time->getTimestamp();
        if ($range) {
          $range_end = array_pop($range);
          $current_time->setTime($range_end + 1, 0);
          $range_end_ts = $current_time->getTimestamp();
        } else {
          $range_end_ts = $range_start_ts;
        }
        $range_text[] = SmartDateTrait::formatSmartDate($range_start_ts, $range_end_ts, $format->getOptions(), $tz_string, 'string');
      }
      $repeat .= ' ' . $this->t('within') . ' ' . implode(', ', $range_text);
      $time_set = TRUE;
    }
    if ($params['byminute']) {
      $ranges = $this->makeRanges($params['byminute']);
      $range_text = [];
      foreach ($ranges as $range) {
        $range_start = array_shift($range);
        if ($range) {
          $range_end = array_pop($range);
          $range_text[] = $this->t(':start to :end', [':start' => $range_start, ':end' => $range_end], ['context' => 'Rule text']);
        } else {
          $range_text[] = $range_start;
        }
      }
      $repeat .= ' ' . $this->t('at :ranges past the hour', [':ranges' => implode(', ', $range_text)], ['context' => 'Rule text']);
      $time_set = TRUE;
    }
    // Convert the stored day modifier to something human-readable.
    if ($params['which']) {
      switch ($params['which']) {
        case '1':
          $params['which'] = $this->t('first');
          break;

        case '2':
          $params['which'] = $this->t('second');
          break;

        case '3':
          $params['which'] = $this->t('third');
          break;

        case '4':
          $params['which'] = $this->t('fourth');
          break;

        case '5':
          $params['which'] = $this->t('fifth');
          break;

        case '-1':
          $params['which'] = $this->t('last');
          break;

      }
    }
    // Convert the stored day value to something human-readable.
    if (isset($params['day'])) {
      switch ($params['day']) {
        case 'SU':
        case 'MO':
        case 'TU':
        case 'WE':
        case 'TH':
        case 'FR':
        case 'SA':
          $params['day'] = $day_labels[$params['day']];
          break;

        case 'MO,TU,WE,TH,FR':
          $params['day'] = $this->t('weekday');
          break;

        case 'SA,SU':
          $params['day'] = $this->t('weekend day');
          break;

        case '':
          $params['day'] = $this->t('day');
          break;

      }
    }

    // Format the day output.
    if (in_array($freq, ['MINUTELY', 'HOURLY', 'DAILY', 'WEEKLY'])) {
      if (!empty($params['byday']) && is_array($params['byday'])) {
        switch (count($params['byday'])) {
          case 1:
            $day_output = $day_labels[array_pop($params['byday'])];
            break;

          case 2:
            $day_output = $day_labels[$params['byday'][0]] . ' ' . $this->t('and') . ' ' . $day_labels[$params['byday'][1]];
            break;

          default:
            $day_output = '';
            foreach ($params['byday'] as $key => $day) {
              if ($key === array_key_last($params['byday'])) {
                $day_output .= $this->t('and') . ' ' . $day_labels[$day];

              }
              else {
                $day_output .= $day_labels[$day] . ', ';
              }
            }
            break;
        }
      }
      else {
        // Default to getting the day from the start date.
        $day_labels_by_day_of_week = array_values($day_labels);
        $day_output = $day_labels_by_day_of_week[date('N', $start_ts) - 1];
      }
      $day = $this->t('on :day', [':day' => $day_output], ['context' => 'Rule text']);
    }
    else {
      $day = date('jS', $start_ts);
      if ($params['which']) {
        $day = $params['which'] . ' ' . $params['day'];
      }
      $day = $this->t('on the :day', [':day' => $day], ['context' => 'Rule text']);
    }

    // Format the month display, if needed.
    if ($freq == 'YEARLY') {
      $month = ' ' . $this->t('of :month', [':month' => date('F', $start_ts)], ['context' => 'Rule text']);
    }
    else {
      $month = '';
    }

    if ($time_set) {
      $time = '';
    }
    else {
      // Format the time display.
      // Use the "Time Only" Smart Date Format to allow better formatting.
      $end_ts = $this->end->getValue()[0]['value'];
      if (SmartDateTrait::isAllDay($start_ts, $end_ts, $tz_string)) {
        $time = SmartDateTrait::formatSmartDate($start_ts, $end_ts, $format->getOptions(), $tz_string, 'string');
      } else {
        $time_string = SmartDateTrait::formatSmartDate($start_ts, $start_ts, $format->getOptions(), $tz_string, 'string');
        $time = $this->t('at :time', [':time' => ''], ['context' => 'Rule text']) . $time_string;
      }
    }

    // Process the limit value, if present.
    $limit = '';
    if ($this->limit) {
      list($limit_type, $limit_val) = explode('=', $this->limit);
      switch ($limit_type) {
        case 'UNTIL':
          $limit_ts = strtotime($limit_val);
          $format = SmartDateFormat::load('date_only');
          $date_string = SmartDateTrait::formatSmartDate($limit_ts, $limit_ts, $format->getOptions(), $tz_string, 'string');
          $limit = ' ' . $this->t('until :date', [':date' => $date_string]);
          break;

        case 'COUNT':
          $limit = ' ' . $this->t('for :num times', [':num' => $limit_val]);
      }
    }

    return [
      '#theme' => 'smart_date_recurring_text_rule',
      '#repeat' => $repeat,
      '#day' => $day,
      '#month' => $month,
      '#time' => $time,
      '#limit' => $limit,
    ];
  }

  /**
   * Helper function to convert an array into ranges.
   *
   * @param array $array
   *   The array to convert.
   * @param int $offset
   *   The offset to use for comoparison.
   *
   * @return array
   *   An array of ranges.
   */
  private function makeRanges(array $array, $offset = 1) {
    $ranges = [];
    if (!$array || count($array) == 1) {
      return $array;
    }
    $start_item = array_shift($array);
    $range = [$start_item];
    foreach ($array as $value) {
      if ($value == $start_item + $offset) {
        // Add to the current range.
        $range[] = $value;
      }
      else {
        // Start a new range.
        $ranges[] = $range;
        $range = [$value];
      }
      $start_item = $value;
    }
    // Add the final range.
    $ranges[] = $range;
    return $ranges;
   }

  /**
   * Retrieve the months_limit value from the field definition.
   */
  public static function getThirdPartyFallback($field_def, $property, $default = NULL) {
    $value = $default;
    if (method_exists($field_def, 'getThirdPartySetting')) {
      // Works for field definitions and rule objects.
      $value = $field_def
        ->getThirdPartySetting('smart_date_recur', $property, $default);
    } elseif (method_exists($field_def, 'getSetting')) {
      // For custom entities, set value in your field definition.
      $value = $field_def->getSetting($property);
    }
    return $value;
  }

  /**
   * Retrieve the months_limit value from the field definition.
   */
  public static function getMonthsLimit($field_def) {
    $month_limit = static::getThirdPartyFallback($field_def, 'month_limit', 12);
    return $month_limit;
  }

  /**
   * Return an array of frequency labels.
   */
  public static function getFrequencyLabels() {
    return [
      'MINUTELY' => t('By Minutes'),
      'HOURLY' => t('Hourly'),
      'DAILY' => t('Daily'),
      'WEEKLY' => t('Weekly'),
      'MONTHLY' => t('Monthly'),
      'YEARLY' => t('Annually'),
    ];
  }

  /**
   * Return an array of frequency labels.
   */
  public static function getFrequencyLabelsOrNull() {
    $values = ['none' => 'Not recurring'];
    $labels = static::getFrequencyLabels();
    return array_merge($values, $labels);
  }

  /**
   * Retrieve a setting from the field config.
   */
  public function getFieldSettings($setting_name, $module = 'smart_date_recur') {
    $entity_type = $this->entity_type->getString();
    $bundle = $this->bundle->getString();
    $field_name = $this->field_name->getString();
    $bundle_fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
    $field_def = $bundle_fields[$field_name];
    if ($field_def instanceof FieldConfigInterface) {
      $value = $field_def->getThirdPartySetting($module, $setting_name);
    }
    elseif ($field_def instanceof BaseFieldDefinition) {
      // TODO: Document that for custom entities, you must enable recurring
      // functionality by adding ->setSetting('allow_recurring', TRUE)
      // to your field definition.
      $value = $field_def->getSetting($setting_name);
    }
    else {
      // Not sure what other method we can provide to define this.
      $value = FALSE;
    }
    return $value;
  }

  /**
   * Convert the stored parameters into an array.
   */
  public function getParametersArray() {
    $params = $this->get('parameters')->getString();
    $return_array = [
      'interval' => NULL,
      'which' => '',
      'day' => '',
      'byday' => [],
      'byhour' => [],
      'byminute' => [],
    ];
    if ($params && $params = explode(';', $params)) {
      foreach ($params as $param) {
        list($var_name, $var_value) = explode('=', $param);
        switch ($var_name) {
          case 'INTERVAL':
            $return_array['interval'] = (int) $var_value;
            break;

          case 'BYDAY':
            $arr = preg_split('/(?<=[-0-9])(?=[,A-Z]+)/i', $var_value);
            if ((int) $arr[0]) {
              // Starts with a number, so treat as a compound value.
              $return_array['which'] = $arr[0];
              $return_array['day'] = $arr[1];
            }
            else {
              // Assume this is a multi-day value.
              $freq = $this->get('freq')->getString();
              if (in_array($freq, ['MINUTELY', 'HOURLY', 'DAILY', 'WEEKLY'])) {
                // Split into an array before returning the value.
                $return_array['byday'] = explode(',', $arr[0]);
              }
              else {
                $return_array['day'] = $arr[0];
              }
            }
            break;

          case 'BYHOUR':
            $return_array['byhour'] = explode(',', $var_value);
            break;

          case 'BYMINUTE':
            $return_array['byminute'] = explode(',', $var_value);
            break;

          case 'BYMONTHDAY':
            $return_array['which'] = $var_value;
            break;

          case 'BYSETPOS':
            $return_array['which'] = $var_value;
            break;

        }
      }
    }
    return $return_array;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach ($entities as $id => $rrule) {
      // Delete any child overrides when a rule is deleted.
      $overrides = $rrule->getRuleOverrides();
      foreach ($overrides as $override) {
        $override->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['rule'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Rule'))
      ->setDescription(t('The Rule that will be used to generate instances.'))
      ->setSettings([
        'max_length' => 256,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      // TODO add a unique constrain for a combination of values,
      // e.g. start field amd delta and revision.
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE);

    // Separate storage for the frequency.
    $fields['freq'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Frequency'))
      ->setDescription(t('How often the date recurs.'))
      ->setSetting('is_ascii', TRUE)
      // Longest value we anticipate is 'MINUTELY'.
      ->setSetting('max_length', 8)
      ->setRequired(TRUE);

    // Separate storage for the limit.
    $fields['limit'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Limit'))
      ->setDescription(t('A constraint on how long to recur.'))
      // Longest value looks like UNTIL=19970902T170000Z.
      ->setSetting('max_length', 25)
      ->setSetting('is_ascii', TRUE);

    // Separate storage for extra parameters such as INTERVAL or BYMONTHDAY.
    // NOTE: The intention is to store these semicolon-separated.
    $fields['parameters'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parameters'))
      ->setDescription(t('Additional parameters to define the recurrence.'))
      ->setSetting('is_ascii', TRUE);

    // TODO: Decide if this field is necessary, given the presence of the Limit.
    $fields['unlimited'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Unlimited'))
      ->setDescription(t('Whether or not the rule has a limit or end.'))
      ->setDefaultValue(TRUE)
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type on which the date is set.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bundle'))
      ->setDescription(t('The bundle on which the date is set.'))
      ->setSetting('is_ascii', TRUE)
      // TODO: Check for a different limit on bundle max length.
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Smart Date field name'))
      ->setDescription(t('The field name on which the date is set.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH);

    $fields['start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Start timestamp value'))
      ->setRequired(TRUE);

    $fields['end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('End timestamp value'))
      ->setRequired(TRUE);

    $fields['instances'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Instances'))
      ->setDescription(t('A serialized array of the instances.'));

    return $fields;
  }

}
