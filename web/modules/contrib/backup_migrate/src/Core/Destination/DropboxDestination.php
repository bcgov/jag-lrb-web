<?php

namespace Drupal\backup_migrate\Core\Destination;

use Drupal\backup_migrate\Core\Config\ConfigurableInterface;
use Drupal\backup_migrate\Core\File\BackupFile;
use Drupal\backup_migrate\Core\File\BackupFileInterface;
use Drupal\backup_migrate\Core\File\BackupFileReadableInterface;
use Drupal\backup_migrate\Core\File\ReadableStreamBackupFile;
use Drupal\Component\Utility\Bytes;
use Drupal\Core\Messenger\MessengerTrait;
use ErrorException;
use Exception;

/**
 * Class DropboxDestination.
 *
 * @package Drupal\backup_migrate\Core\Destination
 */
class DropboxDestination extends DestinationBase implements
    RemoteDestinationInterface,
    ListableDestinationInterface,
    ReadableDestinationInterface,
    ConfigurableInterface {

  use MessengerTrait;

  const TEMPORARY_FOLDER = '/tmp/';

  const BACKUP_MIGRATE_DROPBOX_CONTENT_URL = 'https://content.dropboxapi.com/2/files';
  const BACKUP_MIGRATE_DROPBOX_V2 = 'https://api.dropboxapi.com/2';

  protected $token;

  protected $upload_session = [];

  /**
   * {@inheritdoc}
   */
  public function checkWritable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile($name) {
    $files = $this->listFiles();
    if (isset($files[$name])) {
      return $files[$name];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function fileExists($name) {
    return (bool) $this->getFile($name);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function loadFileForReading(BackupFileInterface $file) {
    // If this file is already readable, simply return it.
    if ($file instanceof BackupFileReadableInterface) {
      return $file;
    }
    $name = $file->getFullName();
    if ($this->fileExists($name)) {
      $this->_fileDownload($name);

      return new ReadableStreamBackupFile(self::TEMPORARY_FOLDER . $name);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function listFiles($count = 100, $start = 0) {
    $files = [];
    $path = $this->confGet('destination_folder');
    $listing = json_decode($this->_listFiles($path));

    if (!empty($listing->entries)) {
      foreach ($listing->entries as $file) {
        if ($file->{'.tag'} === 'file') {
          $filename = $file->name;
          $out = new BackupFile();
          $out->setMeta('id', $file->id);
          $out->setMeta('datestamp', strtotime($file->server_modified));
          $out->setMeta('filesize', $file->size);
          $out->setFullName($filename);
          $files[$filename] = $out;
        }
      }
    }
    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function countFiles() {
    $file_list = $this->listFiles();
    return count($file_list);
  }

  /**
   * {@inheritdoc}
   */
  public function queryFiles($filters = [], $sort = 'datestamp', $sort_direction = SORT_DESC, $count = 100, $start = 0) {
    // Get the full list of files.
    $out = $this->listFiles($count, $start);
    foreach ($out as $key => $file) {
      $out[$key] = $this->loadFileMetadata($file);
    }

    // Filter the output.
    if ($filters) {
      $out = array_filter($out, function ($file) use ($filters) {
        foreach ($filters as $key => $value) {
          if ($file->getMeta($key) !== $value) {
            return FALSE;
          }
        }
        return TRUE;
      });
    }

    // Sort the files.
    if ($sort && $sort_direction) {
      uasort($out, function ($a, $b) use ($sort, $sort_direction) {
        if ($sort_direction == SORT_DESC) {
          return $b->getMeta($sort) < $b->getMeta($sort);
        }
        else {
          return $b->getMeta($sort) > $b->getMeta($sort);
        }
      });
    }

    // Slice the return array.
    if ($count || $start) {
      $out = array_slice($out, $start, $count);
    }

    return $out;
  }

  /**
   * {@inheritdoc}
   */
  protected function saveTheFile(BackupFileReadableInterface $file) {
    $destination = $this->confGet('destination_folder') . '/' . $file->getFullName();
    $this->fileUpload($file->realpath(), $destination);
  }

  /**
   * {@inheritdoc}
   */
  protected function saveTheFileMetadata(BackupFileInterface $file) {
    // Metadata is saved during the file upload process. Nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  protected function loadFileMetadataArray(BackupFileInterface $file) {
    // Metadata is fetched with the listing. There is nothing to be fetched.
  }

  /**
   * {@inheritdoc}
   */
  protected function deleteTheFile($id) {
    $path = $this->confGet('destination_folder') . '/' . $id;
    $this->_deleteDropboxFile($path);
  }

  /**
   * {@inheritdoc}
   */
  protected function _deleteDropboxFile($path) {
    // Simple upload.
    $parameters = [
      'path' => $path,
    ];

    // Header.
    $header = [];
    $header[] = 'Content-Type: application/json';
    $header[] = 'Authorization: Bearer ' . $this->confGet('token');

    // Curl.
    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, self::BACKUP_MIGRATE_DROPBOX_V2 . '/files/delete_v2');
    curl_setopt($request, CURLOPT_POST, TRUE);
    curl_setopt($request, CURLOPT_HTTPHEADER, $header);
    curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    $this->_executeCurl($request);
    curl_close($request);
  }

  /**
   * Function to upload a file on dropbox.
   *
   * @param string $file
   *   The path to the file to upload.
   * @param string $path
   *   The destination path to the file to upload.
   */
  public function fileUpload($file, $path) {

    // Cut PHP memory limit by 10% to allow for other in memory data.
    $php_memory_limit = intval(Bytes::toInt(ini_get('memory_limit')) * 0.9);

    // Dropbox currently has a 150M upload limit per transaction.
    $dropbox_upload_limit = Bytes::toInt('150M');

    // For testing or in case the 10% leeway isn't enough allow a smaller upload
    // limit as an advanced setting. This variable has no ui but can be set with
    // drush or through the variable module.
    $manual_upload_limit = Bytes::toInt('150M');

    // Use the smallest value for the max file size.
    $max_file_size = min($php_memory_limit, $dropbox_upload_limit, $manual_upload_limit);

    // File.
    $file_size = filesize($file);

    // If the file size is greater than the max size.
    if ($file_size > $max_file_size) {
      // Open file.
      $file_handle = fopen($file, 'rb');
      if (!$file_handle) {
        throw new ErrorException('Cannot open backup file (1).');
      }

      // Start.
      $content = fread($file_handle, $max_file_size);
      if (!$content) {
        throw new ErrorException('Cannot read backup file (2).');
      }
      $this->_fileUploadSessionStart($content);

      // Append.
      while (!feof($file_handle)) {
        // Get content.
        $content = fread($file_handle, $max_file_size);
        if (!$content) {
          throw new ErrorException('Cannot read backup file (3).');
        }
        $this->_fileUploadSessionAppend($content);
      }

      // Finish.
      $this->_fileUploadSessionFinish($path);
    }
    else {
      $content = file_get_contents($file);
      if (!$content) {
        throw new ErrorException('Cannot open backup file (4).');
      }
      $this->_fileUpload($path, $content);
    }
  }

  /**
   * Function to start a file upload session.
   *
   * @param mixed $content
   *   COntent of the file.
   */
  protected function _fileUploadSessionStart($content) {
    // Header.
    $header = [];
    $header[] = 'Content-type: application/octet-stream';
    $header[] = 'Authorization: Bearer ' . $this->confGet('token');
    $header[] = 'Accept: application/json';

    // Curl.
    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, self::BACKUP_MIGRATE_DROPBOX_CONTENT_URL . '/upload_session/start');
    curl_setopt($request, CURLOPT_POST, 1);
    curl_setopt($request, CURLOPT_HTTPHEADER, $header);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($request, CURLOPT_POSTFIELDS, $content);
    $result = json_decode($this->_executeCurl($request), TRUE);

    // Catch any errors.
    if (!is_array($result) || !isset($result['session_id'])) {
      throw new ErrorException('No session id returned.');
    }

    curl_close($request);

    $this->upload_session['session_id'] = $result['session_id'];
    $this->upload_session['offset'] = strlen($content);
  }

  /**
   * Function to append to a file upload session.
   *
   * @param mixed $content
   *   The content of the file.
   */
  protected function _fileUploadSessionAppend($content) {
    // Args.
    $parameters = [
      'cursor' => $this->upload_session,
    ];

    // Header.
    $header = [];
    $header[] = 'Content-type: application/octet-stream';
    $header[] = 'Authorization: Bearer ' . $this->confGet('token');
    $header[] = 'Dropbox-API-Arg: ' . json_encode($parameters);
    $header[] = 'Accept: application/json';

    // Curl.
    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, self::BACKUP_MIGRATE_DROPBOX_CONTENT_URL . '/upload_session/append_v2');
    curl_setopt($request, CURLOPT_POST, 1);
    curl_setopt($request, CURLOPT_HTTPHEADER, $header);
    curl_setopt($request, CURLOPT_POSTFIELDS, $content);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    $this->_executeCurl($request);

    curl_close($request);
    $this->upload_session['offset'] += strlen($content);
  }

  /**
   * Function to finish file upload session.
   *
   * @param string $path
   *   The path to the file.
   */
  protected function _fileUploadSessionFinish($path) {
    // Finish.
    $parameters = [
      'cursor' => $this->upload_session,
      'commit' => [
        'path' => $path,
        'mode' => 'add',
        'autorename' => TRUE,
        'mute' => TRUE,
      ],
    ];

    $header = [];
    $header[] = 'Content-type: application/octet-stream';
    $header[] = 'Authorization: Bearer ' . $this->confGet('token');
    $header[] = 'Dropbox-API-Arg: ' . json_encode($parameters);
    $header[] = 'Accept: application/json';

    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, self::BACKUP_MIGRATE_DROPBOX_CONTENT_URL . '/upload_session/finish');
    curl_setopt($request, CURLOPT_POST, 1);
    curl_setopt($request, CURLOPT_HTTPHEADER, $header);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    $result = $this->_executeCurl($request);
    curl_close($request);
  }

  /**
   * Function to upload a file.
   *
   * @param string $path
   *   Path to the file.
   * @param mixed $content
   *   Content to upload.
   */
  protected function _fileUpload($path, $content) {
    // Simple upload.
    $parameters = [
      'path' => $path,
      'mode' => 'add',
      'autorename' => TRUE,
      'mute' => FALSE,
    ];

    // Header.
    $header = [];
    $header[] = 'Content-type: application/octet-stream';
    $header[] = 'Authorization: Bearer ' . $this->confGet('token');
    $header[] = 'Dropbox-API-Arg: ' . json_encode($parameters);
    $header[] = 'Accept: application/json';

    // Curl.
    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, self::BACKUP_MIGRATE_DROPBOX_CONTENT_URL . '/upload');
    curl_setopt($request, CURLOPT_POST, 1);
    curl_setopt($request, CURLOPT_HTTPHEADER, $header);
    curl_setopt($request, CURLOPT_POSTFIELDS, $content);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    $result = $this->_executeCurl($request);

    curl_close($request);
  }

  /**
   * Function to list files on dropbox.
   */
  protected function _listFiles($path) {
    if (empty($path)) {
      return '';
    }
    // Simple upload.
    $parameters = [
      'path' => $path,
    ];

    // Header.
    $header = [];
    $header[] = 'Content-Type: application/json';
    $header[] = 'Authorization: Bearer ' . $this->confGet('token');

    // Curl.
    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, self::BACKUP_MIGRATE_DROPBOX_V2 . '/files/list_folder');
    curl_setopt($request, CURLOPT_POST, TRUE);
    curl_setopt($request, CURLOPT_HTTPHEADER, $header);
    curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    $result = $this->_executeCurl($request);
    curl_close($request);

    return $result;
  }

  /**
   * Function to download a file from dropbox.
   *
   * @param string $name
   *   Name of the file.
   */
  protected function _fileDownload($name) {
    $path = $this->confGet('destination_folder') . '/' . $name;

    $parameters = [
      'path' => $path,
    ];

    $saveTo = self::TEMPORARY_FOLDER . $name;
    $fp = fopen($saveTo, 'w+');

    // If $fp is FALSE, something went wrong.
    if ($fp === FALSE) {
      throw new Exception('Could not open: ' . $saveTo);
    }

    // Header.
    $header = [];
    $header[] = 'Content-Type: text/plain';
    $header[] = 'Authorization: Bearer ' . $this->confGet('token');
    $header[] = 'Dropbox-API-Arg: ' . json_encode($parameters);

    // Curl.
    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, self::BACKUP_MIGRATE_DROPBOX_CONTENT_URL . '/download');
    curl_setopt($request, CURLOPT_FILE, $fp);
    curl_setopt($request, CURLOPT_POST, 1);
    curl_setopt($request, CURLOPT_HTTPHEADER, $header);
    $result = $this->_executeCurl($request);

    curl_close($request);

    return $result;
  }

  /**
   * Function to execute a curl request to dropbox.
   *
   * @param mixed $request
   *   The request.
   *
   * @return mixed
   *   The response of the curl request.
   */
  protected function _executeCurl($request) {
    $result = curl_exec($request);

    $response_code = curl_getinfo($request, CURLINFO_HTTP_CODE);
    if (curl_error($request)) {
      throw new ErrorException('Curl error: ' . curl_error($request));
    }
    elseif (isset($result['error'])) {
      $result = json_decode($result);
      throw new ErrorException('Dropbox error: ' . $result['error_summary']);
    }
    elseif ($response_code >= 500) {
      throw new ErrorException('Dropbox server error. Try later or check status.dropbox.com for outages.');
    }
    elseif ($response_code >= 400) {
      throw new ErrorException('Bad http status response code (' . $response_code . '): ' . $result);
    }

    return $result;
  }

}
