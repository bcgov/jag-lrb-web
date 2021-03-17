<?php

namespace Drupal\webformnavigation;

use Drupal;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_submission_log\WebformSubmissionLogManager;

/**
 * Defines a helper class for the webform navigation module.
 */
class WebformNavigationHelper {

  /**
   * Name of the table where log entries are stored.
   */
  const TABLE = 'webformnavigation_log';

  /**
   * Name of the error operation.
   */
  const ERROR_OPERATION = 'errors';

  /**
   * Name of the page visited operation
   */
  const PAGE_VISITED_OPERATION = 'page visited';

  /**
   * Name of the navigation handler.
   */
  const HANDLER_ID = 'webform_navigation';

  /**
   * The temp_store key.
   */
  const TEMP_STORE_KEY = 'webformnavigation_errors';

  /**
   * @var \Drupal\webform_submission_log\WebformSubmissionLogManager
   */
  protected $webform_submission_log_manager;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * AutosaveHelper constructor.
   *
   * @param \Drupal\webform_submission_log\WebformSubmissionLogManager $webform_submission_log_manager
   * @param \Drupal\Core\Database\Connection $datababse
   */
  public function __construct(WebformSubmissionLogManager $webform_submission_log_manager, Connection $datababse) {
    $this->webform_submission_log_manager = $webform_submission_log_manager;
    $this->database = $datababse;
  }

  /**
   * Gets the current submission page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission entity.
   *
   * @return string
   *   The current submission page ID.
   */
  public function getCurrentPage(WebformSubmissionInterface $webform_submission) {
    $pages = $webform_submission->getWebform()->getPages();
    return empty($webform_submission->getCurrentPage()) ? array_keys($pages)[0] : $webform_submission->getCurrentPage();
  }

  /**
   * Has visited page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission entity.
   * @param string $page
   *   The page we're checking.
   *
   * @return bool
   */
  public function hasVisitedPage(WebformSubmissionInterface $webform_submission, $page) {
    // Get outta here if the submission hasn't been saved yet.
    if (empty($webform_submission->id())) {
      return FALSE;
    }
    $query = $this->database->select(self::TABLE, 'l');
    $query->condition('webform_id', $webform_submission->getWebform()->id());
    $query->condition('sid', $webform_submission->id());
    $query->condition('operation', self::PAGE_VISITED_OPERATION);
    $query->condition('data', $page);
    $query->fields('l', [
      'lid',
      'sid',
      'data',
    ]);
    $submission_log = $query->execute()->fetch();
    return !empty($submission_log);
  }

  /**
   * Gets either all errors or errors for a specific page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission entity.
   *
   * @param string|null $page
   *   Set to page name if you only want the data for a particular page.
   *
   * @return array
   */
  public function getErrors(WebformSubmissionInterface $webform_submission, $page = NULL) {
    // Get outta here if the submission hasn't been saved yet.
    if (empty($webform_submission->id())) {
      return [];
    }
    $query = $this->database->select(self::TABLE, 'l');
    $query->condition('webform_id', $webform_submission->getWebform()->id());
    $query->condition('sid', $webform_submission->id());
    $query->condition('operation', self::ERROR_OPERATION);
    $query->fields('l', [
      'lid',
      'sid',
      'data',
    ]);
    $query->orderBy('l.lid', 'DESC');
    $query->range(0, 1);
    $submission_log = $query->execute()->fetch();
    $data = !empty($submission_log->data) ? unserialize($submission_log->data) : [];
    return (isset($page) && isset($data[$page])) ? $data[$page] : $data;
  }

  /**
   * Logs the current submission page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission entity.
   * @param string $page
   *   The page to log.
   *
   * @throws \Exception
   */
  public function logPageVisit(WebformSubmissionInterface $webform_submission, $page) {
    // Get outta here if the submission hasn't been saved yet.
    if (empty($webform_submission->id())) {
      return;
    }
    // Only log the page if they haven't already visited it.
    if (!$this->hasVisitedPage($webform_submission, $page)) {
      $fields = [
        'webform_id' => $webform_submission->getWebform()->id(),
        'sid' => $webform_submission->id(),
        'operation' => self::PAGE_VISITED_OPERATION,
        'handler_id' => self::HANDLER_ID,
        'uid' => Drupal::currentUser()->id(),
        'data' => $page,
        'timestamp' => (string) Drupal::time()->getRequestTime(),
      ];
      $query = $this->database->insert(self::TABLE, $fields);
      $query->fields($fields)->execute();
    }
  }

  /**
   * Logs the stashed submission errors.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission entity.
   *
   * @throws \Exception
   */
  public function logStashedPageErrors(WebformSubmissionInterface $webform_submission) {
    /** @var \Drupal\Core\TempStore\PrivateTempStore $store */
    $store = Drupal::service('tempstore.private')->get('webformnavigation');
    $errors = $store->get(self::TEMP_STORE_KEY);
    // Get outta here if there are not any stashed errors.
    if (empty($errors)) {
      return;
    }
    $prev_errors = $this->getErrors($webform_submission);
    $new_errors = array_merge($prev_errors, $errors);
    // Log the stashed errors.
    $this->logErrors($webform_submission, $new_errors);
    // Clear the stashed errors now that they are logged.
    $store->delete(self::TEMP_STORE_KEY);
  }

  /**
   * Logs the current submission errors.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Exception
   */
  public function logPageErrors(WebformSubmissionInterface $webform_submission, FormStateInterface $form_state) {
    $form_errors = $form_state->getErrors();
    $current_errors = $this->getErrors($webform_submission);
    $paged_errors = empty($current_errors) ? [] : $current_errors;
    $current_page = $webform_submission->getCurrentPage();
    // Reset the current page's errors with those set in the form state.
    $paged_errors[$current_page] = [];
    foreach ($form_errors as $element => $error) {
      $base_element = explode('][', $element)[0];
      $page = $this->getElementPage($webform_submission->getWebform(), $base_element);
      // Place error on current page if the page is empty.
      if (!empty($page) && is_string($page)) {
        $paged_errors[$page][$element] = $error;
      }
      else {
        $paged_errors[$current_page][$element] = $error;
      }
    }
    // Stash the errors and return if the submission hasn't been created yet.
    if (empty($webform_submission->id())) {
      /** @var \Drupal\Core\TempStore\PrivateTempStore $store */
      $store = Drupal::service('tempstore.private')->get('webformnavigation');
      $store->set(self::TEMP_STORE_KEY, $paged_errors);
      return;
    }
    $this->logErrors($webform_submission, $paged_errors);
  }

  /**
   * Logs errors.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission entity.
   * @param array $errors
   *
   * @throws \Exception
   */
  public function logErrors(WebformSubmissionInterface $webform_submission, array $errors) {
    // Get outta here if the submission hasn't been saved yet.
    if (empty($webform_submission->id())) {
      return;
    }
    if (!empty($errors)) {
      $fields = [
        'webform_id' => $webform_submission->getWebform()->id(),
        'sid' => $webform_submission->id(),
        'operation' => self::ERROR_OPERATION,
        'handler_id' => self::HANDLER_ID,
        'uid' => Drupal::currentUser()->id(),
        'data' => serialize($errors),
        'timestamp' => (string) Drupal::time()->getRequestTime(),
      ];
      $this->database->insert(self::TABLE)->fields($fields)->execute();
    }
  }

  /**
   * Delete submission logs.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission entity.
   */
  public function deleteSubmissionLogs(WebformSubmissionInterface $webform_submission) {
    // Get outta here if the submission hasn't been saved yet.
    if (empty($webform_submission->id())) {
      return;
    }
    $query = $this->database->delete(self::TABLE);
    $query->condition('webform_id', $webform_submission->getWebform()->id());
    $query->condition('sid', $webform_submission->id());
    $query->execute();
  }

  /**
   * Gets a page an element is located at.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform entity.
   * @param $element
   *   A webform element.
   *
   * @return mixed
   */
  public function getElementPage(WebformInterface $webform, $element) {
    $element = $webform->getElement($element);
    return !empty($element) && array_key_exists('#webform_parents', $element) ? $element['#webform_parents'][0] : NULL;
  }

  /**
   * Validates all pages within a submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @throws \Exception
   */
  public function validatePages(WebformSubmissionInterface $webform_submission) {
    $webform = $webform_submission->getWebform();
    $current_page = $webform_submission->getCurrentPage();
    // Validate pages we have yet to visit.
    foreach ($webform->getPages() as $key => $page) {
      if (!$this->hasVisitedPage($webform_submission, $key) && $key != 'webform_confirmation' && empty($page['#states'])) {
        $webform_submission->setCurrentPage($key);
        WebformSubmissionForm::submitWebformSubmission($webform_submission);
        $this->logPageVisit($webform_submission, $key);
      }
    }
    $webform_submission->setCurrentPage($current_page);
  }

}
