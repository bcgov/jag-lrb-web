<?php

namespace Drupal\media_entity_download\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Plugin implementation of the 'media_entity_download_download_link' formatter.
 *
 * @FieldFormatter(
 *   id = "media_entity_download_download_link",
 *   label = @Translation("Download link"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class DownloadLinkFieldFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'disposition' => ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['disposition'] = [
      '#type' => 'radios',
      '#title' => t('Download behavior'),
      '#description' => t('Whether browsers will open a "Save as..." dialog or automatically decide how to handle the download.'),
      '#default_value' => $this->getSetting('disposition'),
      '#options' => [
        ResponseHeaderBag::DISPOSITION_ATTACHMENT => $this->t('Force "Save as..." dialog'),
        ResponseHeaderBag::DISPOSITION_INLINE => $this->t('Browser default'),
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $settings = $this->getSettings();
    if ($settings['disposition'] == ResponseHeaderBag::DISPOSITION_ATTACHMENT) {
      $summary[] = $this->t('Force "Save as..." dialog');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $parent = $items->getParent()->getValue()->id();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {

      $route_parameters = ['media' => $parent];
      $url_options = [];
      if ($delta > 0) {
        $route_parameters['query']['delta'] = $delta;
      }

      // @todo: replace with DI when this issue is fixed: https://www.drupal.org/node/2053415
      /** @var \Drupal\file\FileInterface $file */
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($item->target_id);
      $filename = $file->getFilename();
      $mime_type = $file->getMimeType();

      $url_options['attributes'] = [
        'type' => "$mime_type; length={$file->getSize()}",
        'title' => $filename,
        // Classes to add to the file field for icons.
        'class' => [
          'file',
          // Add a specific class for each and every mime type.
          'file--mime-' . strtr($mime_type, ['/' => '-', '.' => '-']),
          // Add a more general class for groups of well known MIME types.
          'file--' . file_icon_class($mime_type),
        ],
      ];

      // Add download variant.
      $url_options['query'][$settings['disposition']] = NULL;
      if ($settings['disposition'] == ResponseHeaderBag::DISPOSITION_INLINE) {
        if (!empty($settings['target'])) {
          // Link target only relevant for inline downloads (attachment
          // downloads will never navigate client locations)
          $url_options['attributes']['target'] = $settings['target'];
        }
      }
      if (!empty($settings['rel'])) {
        $url_options['attributes']['rel'] = $settings['rel'];
      }

      $url = Url::fromRoute('media_entity_download.download', $route_parameters, $url_options);

      $elements[$delta] = [
        '#type' => 'link',
        '#url' => $url,
        '#title' => $filename,
      ];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getFieldStorageDefinition()->getTargetEntityTypeId() == 'media');
  }

}
