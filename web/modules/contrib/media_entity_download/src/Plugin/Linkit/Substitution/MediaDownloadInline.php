<?php

namespace Drupal\media_entity_download\Plugin\Linkit\Substitution;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * A substitution plugin for a direct download link to a file.
 *
 * @Substitution(
 *   id = "media_download_inline",
 *   label = @Translation("Direct download URL for media item (Browser may display media directly)"),
 * )
 */
class MediaDownloadInline extends MediaDownload {

  /**
   * {@inheritdoc}
   */
  public function getContentDisposition() {
    return ResponseHeaderBag::DISPOSITION_INLINE;
  }

}
