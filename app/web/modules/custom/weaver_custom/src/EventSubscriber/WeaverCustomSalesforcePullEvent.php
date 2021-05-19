<?php

namespace Drupal\weaver_custom\EventSubscriber;

use \Drupal\Core\File\FileSystemInterface;
use Drupal\salesforce\SelectQuery;
use Drupal\salesforce_mapping\Event\SalesforcePullEntityValueEvent;
use Drupal\salesforce_mapping\Event\SalesforcePullEvent;
use Drupal\salesforce_mapping\Event\SalesforceQueryEvent;
use Drupal\salesforce\Event\SalesforceEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\weaver_custom\EventSubscriber
 */
class WeaverCustomSalesforcePullEvent implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
	  return [
      SalesforceEvents::PULL_QUERY => 'pullQuery',
      SalesforceEvents::PULL_PREPULL => 'prepull',
      SalesforceEvents::PULL_ENTITY_VALUE => 'pullEntityValue',
      SalesforceEvents::PULL_PRESAVE => 'pullPresave',
    ];
  }

  public function pullQuery(SalesforceQueryEvent $event) {
    $mapping = $event->getMapping();
    $query = $event->getQuery();
    // limit to 10 for testing
    // $query->limit = 1;
  	
  	switch ($mapping->id()) {
      case 'lrb_sf_decisions':
        // only need to get decisions  >2020
        $query->addCondition('CALENDAR_YEAR(Issued_Date__c)', '2019', '>');
        // dpm($query);
        break;

  		case 'lrb_sf_hearings':

		    // ensure only current 
		    $query->addCondition('LRB_Hearing_Date__c', 'YESTERDAY', '>');
		    // dpm($query);
		  	// limit to 10 for testing
		    // $query->limit = 5;
		    // dpm($query);
		    break;
		}
  }

  public function prepull(SalesforcePullEvent $event) {
  }

  public function pullEntityValue(SalesforcePullEntityValueEvent $event) {
    // dpm($event);
  }

  public function pullPresave(SalesforcePullEvent $event) {
    $mapping = $event->getMapping();
  	$drupalEntity = $event->getEntity();
  	$sf_data = $event->getMappedObject()->getSalesforceRecord();

    $client = \Drupal::service('salesforce.client');
  	
  	switch ($mapping->id()) {

      case 'lrb_sf_decisions':

        // LRB_Disposition_Number__c is formatted like 2020 BCLRB 22
        // document Title will be formatted like 2020BCLRB022
        $disposition_number = $sf_data->fields()['LRB_Disposition_Number__c'];
        $exploded_disposition_number = explode(' ', $disposition_number);

        // need to add zeroes to make ID three digits -- e.g., 2 becomes 002
        $year = $exploded_disposition_number[0];
        $unchanged_id = $exploded_disposition_number[2];
        $three_digit_id = sprintf('%03d', $exploded_disposition_number[2]);
        $content_version_title = $year . $exploded_disposition_number[1] . $three_digit_id;
        
        // get ContentVersion object that contains PDF data
        $query = new \Drupal\salesforce\SelectQuery('ContentVersion');
        $query->fields = ['Id', 'Title', 'IsLatest', 'VersionData'];
        $query->addCondition('Title', "'$content_version_title'", '=');
        $query->addCondition('IsLatest', 'true', '=');
        $query->limit = 1;          
        $result = $client->query($query);

        if ($result->records()) {
          // dpm($result->records());
          $content_version_fields = $result->records()[array_key_first($result->records())]->fields();

          if (isset($content_version_fields['VersionData'])) {

            $version_data_url = 'https://lrb.my.salesforce.com' . $content_version_fields['VersionData'];

            try {
              //https://developer.salesforce.com/docs/atlas.en-us.object_reference.meta/api/sforce_api_objects_contentversion.htm
              $file_data = $client->httpRequestRaw($version_data_url);

              $year_folder = 'public://decisions/' . $year;
              \Drupal::service('file_system')->prepareDirectory($year_folder, FileSystemInterface::CREATE_DIRECTORY);
              $destination = $year_folder . '/' . $content_version_title . '.pdf';
              
              $file = file_save_data($file_data, $destination, FileSystemInterface::EXISTS_REPLACE);
  
              // save the file reference to the Decision entity
              $drupalEntity->set('field_media_file', $file->id());
  
            } catch (\Exception $e) {
              // Unable to fetch file data from SF.
              \Drupal::logger('db')->error(t('failed to fetch PDF for Decision @decision', ['@decision' => $content_version_title]));
              return;
            }
          }
        }
        break;

  		case 'lrb_sf_hearings':

				// join LRB_Hearing_Date__c and LRB_Hearing_Time__c to make the Drupal DateTime
  			// value should be like 2008-03-28T09:42:00
  			$date = $sf_data->field('LRB_Hearing_Date__c');
  			$time = $sf_data->field('LRB_Hearing_Time__c');

  			// time incorrectly uses Zulu time zone
        // see weaver_custom_remove_old_hearings() in weaver_custom.module as well

        // change the time value to be PST as that is what it reflects
  			$time = str_replace('Z', 'PST', $time);
				$datetime = new \DateTime($date . 'T' . $time);

        // change timezone back to Zulu and subtract an hour before saving as UTC to the database so that it will show correctly as PST on the site
        $new_timezone = new \DateTimeZone('Z');
				$datetime->setTimezone($new_timezone);
        $datetime->sub(new \DateInterval("PT1H"));

				$formatted_datetime = $datetime->format('Y-m-d\TH:i:s');
  			$drupalEntity->set('field_lrb_hearing_date', $formatted_datetime);

  			// query the related "LRB Case" (Dispute__c) object as well
  			$case_id = $sf_data->field('LRB_Case__c');
  			if ($case_id != '' && $case_id != null) {
					$case_query = new \Drupal\salesforce\SelectQuery('Dispute__c');
					$case_query->fields = ['Id', 'LRB_CodeSection__c'];
					$case_query->addCondition("Id", sprintf("'%s'", $case_id), "=  ");
					$case_query->limit = 1;
					$result = $client->query($case_query);

          $records = $result->records();
          if (isset($records[array_key_first($records)])) {
            $sections = $records[array_key_first($records)]->field('LRB_CodeSection__c');
            $sections = str_replace(',', ', ', $sections);
            if ($sections) {
              $drupalEntity->set('field_lrb_hearing_sections', $sections);
            }
          }
				}
				break;
  	}
  }
}


/*
LRB_Case__c => string (18) "a0M0A000000maK2UAI"
Name => string (8) "CH-87674"
LRB_Hearing_Date__c => string (10) "2021-02-04"
LRB_Hearing_Time__c => string (13) "09:30:00.000Z"
Case_Title__c => string (50) "Beachcomber Hot Tubs and Teamsters 31 (Section 74)"
CreatedDate => string (28) "2021-01-15T20:29:03.000+0000"
Id => string (18) "a0d0A000000Sh26QAC"

*/