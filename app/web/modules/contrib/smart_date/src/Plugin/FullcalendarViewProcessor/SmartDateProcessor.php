<?php

namespace Drupal\smart_date\Plugin\FullcalendarViewProcessor;

use Drupal\fullcalendar_view\Plugin\FullcalendarViewProcessorBase;
use Drupal\smart_date\SmartDateTrait;

/**
 * Smart Date plugin.
 *
 * @FullcalendarViewProcessor(
 *   id = "fullcalendar_view_smart_date",
 *   label = @Translation("Smart date processor"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
class SmartDateProcessor extends FullcalendarViewProcessorBase {

  /**
   * Process retrieved values before being passed to Fullcalendar.
   *
   * Processing view results of fullcalendar_view for a smart date field.
   *
   * ToDo:
   * - timezone handling
   * - fullcalender_view recurring events handling (not considered yet, maybe
   * this is not to be supported by smart date anyway
   */
  public function process(array &$variables) {
    /* @var \Drupal\views\ViewExecutable $view */
    $view = $variables['view'];
    $view_index = key($variables['#attached']['drupalSettings']['fullCalendarView']);

    $fields = $view->field;
    $options = $view->style_plugin->options;
    $start_field = $options['start'];
    $start_field_options = $fields[$start_field]->options;
    $format_label = $start_field_options['settings']['format'];
    // Load the format specified in the View.
    $format = SmartDateTrait::loadSmartDateFormat($format_label);
    $multiple = $fields[$start_field]->multiple;
    // If not a Smart Date field or not existing config, nothing to do.
    if (strpos($start_field_options['type'], 'smartdate') !== 0 || empty($variables['#attached']['drupalSettings']['fullCalendarView'][$view_index]['calendar_options'])) {
      return;
    }
    $calendar_options = json_decode($variables['#attached']['drupalSettings']['fullCalendarView'][$view_index]['calendar_options'], TRUE);
    $entries = $calendar_options['events'];
    $mappings = $this->getIdMappings($entries);
    if ($multiple && $start_field_options['group_rows'] == FALSE) {
      $messenger = \Drupal::messenger();
      $messenger->addMessage('Please group the rows', $messenger::TYPE_WARNING);
    }
    foreach ($view->result as $key => $row) {
      $current_entity = $row->_entity;
      $values = $current_entity->get($start_field)->getValue();
      $row_data = [];
      $row_data['format'] = $format;
      if ($multiple) {
        foreach ($values as $delta => $value) {
          $value['delta'] = $delta;
          $value['id'] = $row->_entity->id();
          $lookup_key = $value['id'] . '-' . $delta;
          $entries_index = $mappings[$lookup_key];
          $this->updateEntry($entries[$entries_index], $value, $format);
        }
      }
      else {
        $values[0]['delta'] = $key;
        $values[0]['id'] = $row->_entity->id();
        $this->updateEntry($entries[$key], $values[0], $format);
      }
    }
    // Update the entries.
    if ($entries) {
      $calendar_options['events'] = $entries;
      $variables['#attached']['drupalSettings']['fullCalendarView'][$view_index]['calendar_options'] = json_encode($calendar_options);
    }
  }

  /**
   * Helper function to extract a simple array mapping ids to their array keys.
   *
   * @param array $entries
   *   The entries as created by Fullcalendar View.
   *
   * @return array
   *   An array to map
   */
  private function getIdMappings(array $entries) {
    $ids = [];
    foreach ($entries as $key => $entry) {
      $id_parts = explode('-', $entry['id']);
      $label = $entry['eid'] . '-' . $id_parts[1];
      $ids[$label] = $key;
    }
    return $ids;
  }

  /**
   * Helper function to update the FCV-created data arrary.
   *
   * @param array $entry
   *   The original data, created by Fullcalendar View.
   * @param array $row_data
   *   Data that will be used to update the calendar entry.
   * @param array $format
   *   Formatting options from the specified Smart Date Format.
   */
  private function updateEntry(array &$entry, array $row_data, array $format) {
    $start = $row_data['value'];
    $end = $row_data['end_value'];
    $timezone = $row_data['timezone'] ?: NULL;
    if (empty($entry) || empty($start) || empty($end)) {
      return FALSE;
    }
    // Check for all day events.
    if (SmartDateTrait::isAllDay($start, $end)) {
      $entry['start'] = date('Y-m-d', $start);
      // The end date is inclusive for a all day event in full calendar,
      // which is not what we want. So we need one day offset.
      $entry['end'] = date('Y-m-d', $end + (60 * 60 * 24));
      $entry['allDay'] = TRUE;
    }
    else {
      $entry['start'] = date(DATE_ATOM, $start);
      $entry['end'] = date(DATE_ATOM, $end);
      $entry['allDay'] = FALSE;
    }
    // Append the id with necessary additional data.
    if (!empty($row_data['rrule'])) {
      $entry['eid'] = $row_data['id'] . '-R-' . $row_data['rrule'] . '-I-' . $row_data['rrule_index'];
    }
    elseif (isset($row_data['delta'])) {
      $entry['eid'] = $row_data['id'] . '-D-' . $row_data['delta'];
    }
  }

}
