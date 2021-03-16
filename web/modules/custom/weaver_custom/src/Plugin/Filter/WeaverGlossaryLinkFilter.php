<?php

namespace Drupal\weaver_custom\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "weaver_glossary_link_filter",
 *   title = @Translation("Glossary Link Filter"),
 *   description = @Translation("Adds modal popups for glossary term links. Should go near the end of all filters."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class WeaverGlossaryLinkFilter extends FilterBase {

	public function process($text, $langcode) {
		$new_text = $text;

		$dom = new \DOMDocument('1.0', 'utf-8');
		@$dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
		$dom->encoding = 'utf-8';

		// get any <a> tags and check if they link to PDFs
		$elements = $dom->getElementsByTagName('a');

		foreach ($elements as $element) {
    	$data_entity_type = $element->getAttribute('data-entity-type');

    	if ($data_entity_type == 'taxonomy_term') {
    		$uuid = $element->getAttribute('data-entity-uuid');

				// check that term belongs to Glossary vocabulary
				$term = \Drupal::service('entity.repository')->loadEntityByUuid('taxonomy_term', $uuid);

	    	// if glossary term link, add modal and change link to toggle the popover
				if (isset($term) && $term->bundle() == 'lrb_glossary') {

					$new_element = $element;
	    		$new_element->setAttribute('data-toggle', 'popover');
					
					// get content for popover
					$name = $term->label();
					$new_element->setAttribute('title', $name);
					$new_element->setAttribute('data-html', 'true');
					$new_element->setAttribute('class', 'glossary-link');
					$description = $term->description->value;
					$new_element->setAttribute('data-content', $description);

					// remove link
					$new_element->removeAttribute('href');
					$new_element->setAttribute('tabindex', 0);		

					$icon = $dom->createElement('i');
					$icon->setAttribute('class', 'fal fa-question-circle');
		    	$new_element->appendChild($icon);

					$replace = $new_element;
		    	$element->parentNode->replaceChild($replace, $element);
				}
	    }
		}

		$new_text = $dom->saveHTML();

	  return new FilterProcessResult($new_text);
	}
}