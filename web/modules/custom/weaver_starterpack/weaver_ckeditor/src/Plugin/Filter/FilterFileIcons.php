<?php

namespace Drupal\weaver_ckeditor\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "weaver_filter_file_icons",
 *   title = @Translation("File Icons Filter"),
 *   description = @Translation("Add icons to links that go to internal files. Should go near the end of all filters."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterFileIcons extends FilterBase {

	public function process($text, $langcode) {

    libxml_use_internal_errors(true);
		$dom = new \DOMDocument('1.0', 'utf-8');
		@$dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
		$dom->encoding = 'utf-8';

		// get any <a> tags and check if they link to PDFs
		$elements = $dom->getElementsByTagName('a');

		foreach ($elements as $element) {
      // check that element doesn't already have any classes -- e.g., isn't a button or something
      if ($element->hasAttribute('class') == FALSE) {
  			$new_element = $element;
      	$href = $element->getAttribute('href');
        $data_entity_type = $element->getAttribute('data-entity-type');
        $pdf = FALSE;

        // check if direct PDF link
      	if (strstr($href, '.pdf')) {
          $pdf = TRUE;

          // check if locally hosted
          global $base_url;
          if (strpos($href, $base_url) == 0) {
            $filename = str_replace($base_url, '', $href);

            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $filename)) {
              $filesize = weaverFormatBytes(filesize($_SERVER['DOCUMENT_ROOT'] . $filename), 0);
            } else {
              $filesize = 'N/A';
            }
          }

        } else if ($data_entity_type == 'media') {
          $uuid = $element->getAttribute('data-entity-uuid');

          // check that term belongs to Glossary vocabulary
          $media = \Drupal::service('entity.repository')->loadEntityByUuid('media', $uuid);
          
          if ($media->bundle() == 'document' || $media->bundle() == 'lrb_collective_agreement' || $media->bundle() == 'lrb_decision') {
            
            if ($media->bundle() == 'document') {
              $file = $media->get('field_media_document')->entity;
            } else {
              $file = $media->get('field_media_file')->entity;
            }

            if ($file->get('filemime')->value == 'application/pdf') {
              $pdf = TRUE;

              $filesize = weaverFormatBytes($file->get('filesize')->value, 0);
            }
          }
        }

        if ($pdf == TRUE) {

          // if PDF link, add "data-extension=pdf"
          $new_element->setAttribute('data-extension', 'pdf');

          // also ensure it is target=_blank
          $new_element->setAttribute('target', '_blank');
          
          $newValue = $dom->createElement('span');
          $newValue->textContent = $new_element->textContent . ' (' . $filesize . ')';
    
          $icon = $dom->createElement('i');
          $icon->setAttribute('class', 'fal fa-file-pdf');
    
          $new_element->textContent = '';
          
          $new_element->appendChild($icon);
          $new_element->appendChild($newValue);

          $element->parentNode->replaceChild($new_element, $element);
        }
      }
    }

		$new_text = $dom->saveHTML();

	  return new FilterProcessResult($new_text);
	}

}