<?php
use Drupal\Core\Link;
use Drupal\Core\Url;
use \Drupal\file\Entity\File;
use \Drupal\media\Entity\Media;

//6873
function startsWith($haystack, $needle) {
  return preg_match('#^' . $needle . '#', $haystack) === 1;
}

function weaver_link_decisions($data, &$context) {
	$decision = \Drupal\media\Entity\Media::load($data);
	$desc = $decision->get('field_lrb_decision_parties')->value;

	// find instances of Decision IDs in text
	$pattern = '(B[0-9]*\/[0-9]*)';
	preg_match($pattern, $desc, $matches);

	// parse out the entity name
	if (count($matches)) {
		foreach ($matches as $match) {
			$name_array = explode('/', $match);
			$id = trim($name_array[0], 'B');
			$id_no_zeroes = trim($id, 0);
			$year = $name_array[1];
			
			// year may have only 2 digits -- e.g., 93 instead of 1993
			if (strlen($year) == 2) {
				$year = $year + 1900;
			}

			// get matching entity
			$name = $year . '-B' . $id;
		  $query = \Drupal::entityQuery('media')
		    ->condition('bundle', 'lrb_decision')
		    ->condition('name', $name)
		    // ->range(0, 10)
		    ;
		  $mids = $query->execute();

		  // if no results, try again with "no zeroes" version
		  if (count($mids) == 0) {
		  	$name = $year . '-B' . $id_no_zeroes;
			  $query = \Drupal::entityQuery('media')
			    ->condition('bundle', 'lrb_decision')
			    ->condition('name', $name)
			    // ->range(0, 10)
			    ;
			  $mids = $query->execute();
		  }

		  // if results, add the link
		  if (count($mids)) {
		  	foreach ($mids as $mid) {
			  	$linked_decision = Media::load($mid);
			  	$uri = $linked_decision->get('field_media_file')->entity->getFileUri();
			  	// <a href="PDF" title="ID">ID</a>
			  	$link = '<a href="' . file_create_url($uri) . '" target="_blank" data-extension="pdf" title="' . $match . '">' . $match . '</a>';

			  	$new_desc = preg_replace($pattern, $link, $desc);
			  	
			  	$decision->set('field_lrb_decision_parties', ['value' => $new_desc, 'format' => 'full_html']);
			  	$decision->save();
			  }
		  }
		}

	} else {
  	$decision->set('field_lrb_decision_parties', $desc);
  	$decision->save();
  }
}

function weaver_update_ids($data, &$context) {
	$change = FALSE;

	$decision = \Drupal\media\Entity\Media::load($data);

	$date = new DateTime($decision->get('field_lrb_decision_date')->value);
	$year = $date->format('Y');

	$desc = $decision->get('field_lrb_decision_parties')->value;
	$did = $decision->get('field_lrb_decision_id')->value;
	$file_id = $decision->get('field_media_file')->target_id;

	$new_did = $did . '/' . $year;

	if (strpos($did, '/') == FALSE) {
		$decision->set('field_lrb_decision_id', $new_did);
		$change = TRUE;
	}

	$new_desc = trim($desc);

	if (strpos($desc, 'Re:') === 0) {
		$new_desc = trim(substr($desc, 3));

		if ($new_desc != '' && $new_desc != null) {
			$decision->set('field_lrb_decision_parties', $new_desc);
		}
	}

	$pattern = '.*BRITISH COLUMBIA LABOUR RELATIONS BOARD';

	if (startsWith(trim($new_desc), $pattern)) {
		// preg_replace(pattern, replacement, subject)
		$pattern = '(.*BRITISH COLUMBIA LABOUR RELATIONS BOARD)';
		$new_desc = preg_replace($pattern, ' ', $new_desc, 1);
		if ($new_desc != '' && $new_desc != null) {
			$decision->set('field_lrb_decision_parties', trim($new_desc));
		}
	}

	if ($new_desc != null && $new_desc != $desc) {
		$change = TRUE;
	}

	if ($change == TRUE) {
		$decision->save();
	}
}

function weaver_import_get_pdf_pages($file_id) {
	$page = '';

	$file = \Drupal\file\Entity\File::load($file_id);

	$uri = $file->getFileUri();
	
	$parser = new \Smalot\PdfParser\Parser();
	
	try {
		$pdf    = $parser->parseFile($uri);
		$pages  = $pdf->getPages();
	
		return $pages;
	
	} catch(Exception $e) {

  	$error = t('PDF @name could not parse PDF. ID: @id', ['@name' => $uri, '@id' => $file_id]);
  	\Drupal::messenger()->addError($error);
	}
}

function weaver_import_decisions_update_dates_v2($mid, $year, &$context) {
	$decision = \Drupal\media\Entity\Media::load($mid);
	$file_id = $decision->get('field_media_file')->target_id;
	$description = '';
	$allArray = [];
	$has_parties = FALSE;
	$trimmedArray = [];

	if ($file_id != '') {

		$pages = weaver_import_get_pdf_pages($file_id);

		if ($pages) {

			$textArray = $pages[0]->getTextArray();

    	foreach ($textArray as $key => $entry) {
    		if (trim($entry) != '') {
    			$trimmedArray[] = trim($textArray[$key]);
    		}
    	}

    	foreach ($trimmedArray as $key => $entry) {
    		if (strpos($entry, 'To Interested Parties') === 0) {
    			$parties_key = $key;
    			$has_parties = TRUE;
    			break;
    		}
    	}
    	
    	if ($has_parties == TRUE && $parties_key != null) {

	    	try {
	    		$date_string = $trimmedArray[$parties_key-1];
	    		// dpm($date_string);
					$date = new DateTime(trim($date_string));
		    	// dpm($date->format('Y-m-d'));
		    	$decision->set('field_lrb_decision_date', $date->format('Y-m-d'));
		    	$decision->save();
	    		
	    	} catch (Exception $e) {
					// no file id
			  	$error = t('PDF @name could not be converted to a DateTime. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
			  	\Drupal::messenger()->addError($error);
	    	}
	    }
		}

	} else {

		// no file id
  	$error = t('PDF @name did not have a file attached. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
  	\Drupal::messenger()->addError($error);
	}
}

function weaver_import_decisions_update_descriptions($mid, $year, &$context) {
	$decision = \Drupal\media\Entity\Media::load($mid);
	$file_id = $decision->get('field_media_file')->target_id;
	$description = '';
	$allArray = [];
	$needles = ['PANEL', 'PANEL:', 'PANEL ', 'PANEL: '];

	if ($file_id != '') {

		$pages = weaver_import_get_pdf_pages($file_id);

		if ($pages) {
			// find page that has instance of PANEL
			$has_panel = FALSE;
			$panel_page = null;

			foreach ($pages as $num => $page) {

				$textArray = $page->getTextArray();

				// keep adding pages until found text
				// $allArray += $textArray;
				$allArray = array_merge($allArray, $textArray);

				// search $allArray to get correct key for later
				foreach ($needles as $needle) {
					$panel_key = array_search($needle, $allArray);

					// first check for perfect match
					if ($panel_key != FALSE) {
						$panel_page = $page;
						$has_panel = TRUE;
						break;
		
					} else {

						// check for beginning at least
			    	foreach ($allArray as $key => $entry) {
			    		if (strpos($entry, 'PANEL') === 0) {
			    			$panel_key = $key;
			    			$panel_page = $page;
			    			$has_panel = TRUE;
			    			break;
			    		}
			    	}
			    }
				}
				if ($has_panel == TRUE) {
					break;
				}
			}
			
			if ($panel_page != null) {
				// dpm($allArray);
				// dpm($panel_key);

				// find first instance of BRITISH COLUMBIA LABOUR RELATIONS BOARD to remove it and entries before it
				$board_key = array_search('BRITISH COLUMBIA LABOUR RELATIONS BOARD', $allArray);

				foreach ($allArray as $key => $panel_entry) {
					if (trim($panel_entry) == '' || $key <= $board_key || $key >= $panel_key) {
						unset($allArray[$key]);
					}
				}

				foreach($allArray as $text_bit) {
					$description .= $text_bit;
				}

				$decision->set('field_lrb_decision_parties', $description);
				$decision->save();

			} else {

				// PANEL was not found...
		  	$error = t('PDF @name did not have instance of PANEL. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
		  	\Drupal::messenger()->addError($error);
			}
		}

	} else {

		// no file id
  	$error = t('PDF @name did not have a file attached. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
  	\Drupal::messenger()->addError($error);
	}
}

function weaver_import_decisions_update_descriptions_v3($mid, &$context) {
	$decision = \Drupal\media\Entity\Media::load($mid);
	$parties = $decision->get('field_lrb_decision_parties')->value;
	$new = str_replace("–", '-', $parties);

	$decision->set('field_lrb_decision_parties', ['value' => $new, 'format' => 'full_html']);
	$decision->save();
}

function weaver_import_decisions_update_descriptions_v2($mid, $year, &$context) {
	$decision = \Drupal\media\Entity\Media::load($mid);

	// check if description is empty
	if ($decision->get('field_lrb_decision_parties')->value == '') {
		$file_id = $decision->get('field_media_file')->target_id;
		$description = '';
		$re_key = '';

		if ($file_id != '') {

			$pages = weaver_import_get_pdf_pages($file_id);

			if ($pages) {
				$textArray = $pages[0]->getTextArray();

				foreach ($textArray as $key => $entry) {
					if (strpos(trim($entry), 'Re:') === 0) {
						$re_key = $key;
					}

					if ($re_key != '' && trim($entry) == '') {
						$next_blank_key = $key;
						break;
					}
				}
				// dpm($re_key);
				// dpm($next_blank_key);
				if ($re_key != '' && $next_blank_key != '') {
					foreach ($textArray as $key => $entry) {
						if ($key >= $re_key && $key < $next_blank_key) {
							$description .= $entry;
						}
					}
				}

				if ($description != '') {
					$decision->set('field_lrb_decision_parties', $description);
					$decision->save();

				} else {

					// PANEL was not found...
			  	$error = t('PDF @name has no description still. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
			  	\Drupal::messenger()->addError($error);
				}
			}

		} else {

			// no file id
	  	$error = t('PDF @name did not have a file attached. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
	  	\Drupal::messenger()->addError($error);
		}
	}
}

function weaver_import_decisions_update_dates($mid, $year, &$context) {
	$decision = \Drupal\media\Entity\Media::load($mid);
	$file_id = $decision->get('field_media_file')->target_id;

	if ($file_id != '') {

		$pages = weaver_import_get_pdf_pages($file_id);

		if ($pages) {
			// Loop over each page to find and extract Date of Decision text
			$date_text = '';

			foreach ($pages as $page) {
		    $text = $page->getText();

		    $date_pos = strpos($text, 'DATE OF DECISION');

		    if ($date_pos != FALSE) {
		    	// search for DATE OF DECISION area until end of line or end of page
		    	$pattern = '(DATE OF DECISION.+(\n|\Z))';
		    	preg_match_all($pattern, $text, $matches);
		    	// dpm($matches);
		    	// remove empty matches
		    	foreach ($matches as $key => $match) {
		    		if (trim($match[0]) == '') {
		    			unset($matches[$key]);
		    		}
		    	}

		    	if (count($matches[0])) {
			    	if (count($matches) > 1) {
			    		
			    		// found multiple dates - let me know
				    	$error = t('PDF @name had multiple dates. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
				    	\Drupal::messenger()->addError($error);

			    	} else {

			    		if (isset($matches[0]) && isset($matches[0][0])) {
				    		$date_text = str_replace('DATE OF DECISION', '', $matches[0][0]);
				    		$date_text = str_replace(':', '', $date_text);
				    		$date_text = trim($date_text);
				    	} else {

				    		// couldn't find expected matches array
					    	$error = t('PDF @name has unexpected matches. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
					    	\Drupal::messenger()->addError($error);
				    	}
			    	}

			    }

		    	break;
		    
		    }
			}

	  	// didn't find a date
			if ($date_text == '') {

	    	$error = t('PDF @name never found a date. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
	    	\Drupal::messenger()->addError($error);
	    
	    } else {

	    	// load new date to Decision
	    	// dpm($date_text);
	    	try {
		    	$date = new DateTime($date_text);
		    	// dpm($date->format('Y-m-d'));
		    	$decision->set('field_lrb_decision_date', $date->format('Y-m-d'));
		    	$decision->save();
	    		
	    	} catch (Exception $e) {
					// no file id
			  	$error = t('PDF @name could not be converted to a DateTime. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
			  	\Drupal::messenger()->addError($error);
	    	}
	    }
	  }
	} else {

		// no file id
  	$error = t('PDF @name did not have a file attached. ID: @id', ['@name' => $decision->label(), '@id' => $decision->id()]);
  	\Drupal::messenger()->addError($error);
	}
}

function weaver_import_decisions($info, $year, &$context) {

	if ($year > 2005) {
		// 2006+ $info will be array of values

		$id = $info->id;
		$index = $info->index;
		$date = $info->date;
		$linked_date = (isset($info->linked_date)) ? $info->linked_date : null;

    // get a source filename because IDs like B1 and B10 are B001 and B010 in filenames
    $source_filename_id = str_replace('B', '', $id);
    $source_filename = 'B' . sprintf("%03d", $source_filename_id) . '$' . $year . '.pdf';

	} else {
		// pre-2006 $info will look like this:
		// http://www.lrb.bc.ca/decisions/C001$1990.pdf
		$source_filename = str_replace('http://www.lrb.bc.ca/decisions/', '', $info);
		$source_parts = explode('$', $source_filename);
		$id = $source_parts[0];
	}

	$name = $year . '-' . $id;

	$master = null;

	// check if Decision exists
	$query = \Drupal::entityQuery('media');
	$query->condition('bundle', 'lrb_decision');
	$query->condition('name', $name);
	$query->range(0,1);
	$mids = $query->execute();

	// if Decision exists, use it
	if (count($mids)) {
		$decision = \Drupal\media\Entity\Media::load($mids[array_key_first($mids)]);
		$message = t('Decision @ca already exists.', ['@ca' => $name]);

	// if Decision does not exist, create it
	} else {

		// save file
		$source = 'public://pdfs/decisions/' . $year . '/' . $source_filename; // .pdf already part of filename
		$uri = 'public://decisions/' . $year . '/' . $id . '.pdf';
		$pdf = \Drupal\file\Entity\File::create(['uri' => $uri]);
		
		if (!file_exists($source)) {
			$error = t('Source file @file does not exist.', ['@file' => $source]);
			\Drupal::messenger()->addError($error);
		} else {
			$filesystem = \Drupal::service('file_system');

			$filesystem->copy($source, $uri, 'FILE_EXISTS_REPLACE');
			$pdf->setPermanent();
			$pdf->setMimeType('application/pdf');
			$pdf->save();
		}

		// save Decision entity
		if ($year <= 2005) {
			$decision_info = [
				'bundle' => 'lrb_decision',
				'name' => $name,
				'field_lrb_decision_id' => $id,
				'field_lrb_decision_date' => $year . '-01-01', // don't have date, so set to Jan 1 of the year
				'field_media_file' => $pdf->id(),
			];
		} else {
			$decision_info = [
				'bundle' => 'lrb_decision',
				'name' => $name,
				'field_lrb_decision_id' => $id,
				'field_lrb_decision_parties' => $index,
				'field_lrb_decision_date' => $date,
				'field_lrb_decision_linked_date' => $linked_date,
				'field_media_file' => $pdf->id(),
			];
		}

		$decision = \Drupal\media\Entity\Media::create($decision_info);
		$decision->save();

		$message = t('Decision @ca created.', ['@ca' => $id]);
	}

	\Drupal::messenger()->addStatus($message);
}