<?php

namespace Drupal\weaver_custom\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "weaver_responsive_table_filter",
 *   title = @Translation("Responsive Table Filter"),
 *   description = @Translation("Adds wrapper around tables to make them responsive. Should go near the end of all filters."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class WeaverResponsiveTableFilter extends FilterBase {

	public function process($text, $langcode) {
		$new_text = $text;

		$dom = new \DOMDocument('1.0', 'utf-8');
		@$dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
		$dom->encoding = 'utf-8';

		// get any <a> tags and check if they link to PDFs
		$elements = $dom->getElementsByTagName('table');

		$table_wrapper = $dom->createElement('div');
		$table_wrapper->setAttribute('class', 'table-responsive col');

		foreach ($elements as $key => $element) {

			$this_wrapper = $table_wrapper->cloneNode();
			$element->parentNode->replaceChild($this_wrapper, $element);
			$this_wrapper->appendChild($element);
		}

		$new_text = $dom->saveHTML();

	  return new FilterProcessResult($new_text);
	}
}