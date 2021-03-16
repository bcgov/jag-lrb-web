<?php

namespace Drupal\ckeditor_liststyle\Plugin\CKEditorPlugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;

/**
 * Defines the "List Style" plugin.
 *
 * @CKEditorPlugin(
 *   id = "liststyle",
 *   label = @Translation("List Style")
 * )
 */
class ListStylePlugin extends PluginBase implements CKEditorPluginInterface, CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $enabled = FALSE;
    $settings = $editor->getSettings();
    foreach ($settings['toolbar']['rows'] as $row) {
      foreach ($row as $group) {
        foreach ($group['items'] as $button) {
          if (($button === 'BulletedList') || ($button === 'NumberedList')) {
            $enabled = TRUE;
          }
        }
      }
    }

    return $enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    $plugin_paths = [
      'libraries/liststyle/plugin.js',
      'libraries/ckeditor/plugins/liststyle/plugin.js',
    ];

    $plugin = FALSE;
    foreach ($plugin_paths as $plugin_path) {
      if (file_exists(DRUPAL_ROOT . '/' . $plugin_path)) {
        $plugin = $plugin_path;
        break;
      }
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
