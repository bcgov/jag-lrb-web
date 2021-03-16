<?php

/**
 * @file
 * Webform mailchimp module hook definitions.
 */

use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter mergevars before they are sent to Mailchimp.
 *
 * @param array $mergevars
 *   The current mergevars.
 * @param WebformSubmissionInterface $submission
 *   The webform submission entity used to populate the mergevars.
 * @param WebformHandlerInterface $handler
 *   The webform submission handler used to populate the mergevars.
 *
 * @ingroup webform_mailchimp
 */
function hook_webform_mailchimp_lists_mergevars_alter(&$mergevars, WebformSubmissionInterface $submission, WebformHandlerInterface $handler) {

}

/**
 * @} End of "addtogroup hooks".
 */

