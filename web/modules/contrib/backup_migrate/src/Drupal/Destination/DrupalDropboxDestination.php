<?php

namespace Drupal\backup_migrate\Drupal\Destination;

/**
 * @file
 * File to define schema for Dropbox.
 */

use Drupal\backup_migrate\Core\Destination\DropboxDestination;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Class DrupalDropboxDestination.
 *
 * @package BackupMigrate\Drupal\Destination
 */
class DrupalDropboxDestination extends DropboxDestination {
  use MessengerTrait;

  /**
   * Init configurations.
   */
  public function configSchema($params = []) {
    $schema = [];
    // Init settings.
    if ($params['operation'] == 'initialize') {

      $schema['fields']['token'] = [
        'type' => 'text',
        'title' => $this->t('Dropbox App Token'),
        'required' => TRUE,
        'description' => $this->t('Generated access token from your app. <b>Do not</b> use the secret key.'),
      ];

      $schema['fields']['destination_folder'] = [
        'type' => 'text',
        'title' => $this->t('Dropbox Destination Folder'),
        'required' => TRUE,
        'description' => $this->t('The folder where to upload the files. It cannot be the root folder ( / ) .'),
      ];

    }
    return $schema;
  }

}
