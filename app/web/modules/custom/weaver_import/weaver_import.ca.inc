<?php

function weaver_import_ca_dupes($data, &$context) {
	// $query = \Drupal::entityQuery('media');
	// $query->condition('bundle', 'lrb_collective_agreement');
	// $query->condition('name', $data->id);
	// $mids = $query->execute();

	// if (count($mids) > 1) {
	// 	dpm($mids);
	// }
}

function weaver_import_ca($data, &$context) {
	$master = null;
	$employer_name = trim($data->employer);
	$union_name = trim($data->union);

	// fix typos in employer names
	$employer_name = str_replace('(Collective Agreemnent).', '(Collective Agreement)', $employer_name);
	$employer_name = str_replace('(collective agreemnent)', '(Collective Agreement)', $employer_name);
	$employer_name = str_replace('BC Producers’ Branch', 'BC Producers\' Branch', $employer_name);

	// $masters = [
	// 	'BC and Yukon Council of Film Unions',
	// 	'Canadian Film and Television Production Association',
	// 	'Canadian Media Production Association-BC Producers\' Branch',
	// 	'Canadian Media Production Association - BC Producers\' Branch',
	// 	'Canadian Media Production Association',
	// 	'IATSE Local 669 Western Canada Agreement',
	// ];

	// // go through list of Masters and see if current employer uses one
	// foreach ($masters as $this_master) {
	// 	$master_needle = ' - see also ' . $this_master . ' (Collective Agreement)';
	// 	if (strstr($employer_name, $master_needle)) {
	// 		// set the Master name
	// 		$master = $this_master;

	// 		if ($this_master == 'BC and Yukon Council of Film Unions') {
	// 			$master = 'British Columbia and Yukon Council of Film Unions';
	// 		}

	// 		// remove Master wording from employer name
	// 		$employer_name = str_replace(' - see also ', '', $employer_name);
	// 		$employer_name = str_replace(' (Collective Agreement)', '', $employer_name);
	// 		$employer_name = str_replace($this_master, '', $employer_name);
	// 	}
	// }

	// if ($master != null) {
	// 	$master_org = null;
	// 	$master_nids = \Drupal::entityQuery('node')->condition('type', 'lrb_employer')->condition('title', $master)->range(0,1)->execute();
	// 	if (count($master_nids)) {
	// 		$master_org = \Drupal\node\Entity\Node::load($master_nids[array_key_first($master_nids)]);
	// 	}
	// }

	$query = \Drupal::entityQuery('node');
	$query->condition('type', 'lrb_employer');
	$query->condition('title', $employer_name);
	$query->range(0,1);
	$nids = $query->execute();

	// if Employer entity exists, use it
	if (count($nids)) {

		$employer = \Drupal\node\Entity\Node::load($nids[array_key_first($nids)]);
		// $message = t('@employer already exists.', ['@employer' => $employer_name]);

	// if Employer does not exist, create it
	} else {
		// reduce title length if too long
		if (strlen($employer_name) > 200) {
			$error = t('@name was too long.', ['@name' => $employer_name]);
			\Drupal::messenger()->addError($error);

			$employer_name = substr($employer_name, 0, 200);
		}

		$employer_info = [
			'type' => 'lrb_employer',
			'title' => $employer_name,
			'field_lrb_employer_ismaster' => 0,
		];
		

		$employer = \Drupal\node\Entity\Node::create($employer_info);
		
		// // add link to master org if exists
		// if ($master_org != null) {
		// 	$employer->set('field_lrb_employer_master', $master_org->id());
		// }
		
		$employer->save();

		// $message = t('@employer node created.', ['@employer' => $employer_name]);
	}

	$union_query = \Drupal::entityQuery('node');
	$union_query->condition('type', 'lrb_union');
	$union_query->condition('title', $union_name);
	$union_query->range(0,1);
	$nids = $union_query->execute();

	// if Union entity exists, use it
	if (count($nids)) {

		$union = \Drupal\node\Entity\Node::load($nids[array_key_first($nids)]);
		// $message = t('@union already exists.', ['@union' => $union_name]);

	// if Union does not exist, create it
	} else {
		// reduce title length if too long
		if (strlen($union_name) > 200) {
			$error = t('@name was too long.', ['@name' => $union_name]);
			\Drupal::messenger()->addError($error);

			$union_name = substr($union_name, 0, 200);
		}

		$union_info = [
			'type' => 'lrb_union',
			'title' => $union_name,
		];

		$union = \Drupal\node\Entity\Node::create($union_info);
		
		$union->save();

		// $message = t('@employer node created.', ['@employer' => $employer_name]);
	}


	// \Drupal::messenger()->addStatus($message);

	// check if Collective Agreement exists
	$query = \Drupal::entityQuery('media');
	$query->condition('bundle', 'lrb_collective_agreement');
	$query->condition('name', $data->id);
	$query->range(0,1);
	$mids = $query->execute();

	// if CA exists, use it but add employer and/or union
	if (count($mids)) {
		$ca = \Drupal\media\Entity\Media::load($mids[array_key_first($mids)]);
		// $message = t('CA @ca already exists.', ['@ca' => $data->id]);
		// \Drupal::messenger()->addStatus($message);

		// only add employer if not already there
		$existing_employers = array_column($ca->field_lrb_ca_employer->getValue(), 'target_id');
		if (!in_array($employer->id(), $existing_employers)) {
			$ca->field_lrb_ca_employer->appendItem(['target_id' => $employer->id()]);
			$ca->save();
		}

		$existing_unions = array_column($ca->field_lrb_ca_union->getValue(), 'target_id');
		if (!in_array($union->id(), $existing_unions)) {
			$ca->field_lrb_ca_union->appendItem(['target_id' => $union->id()]);
			$ca->save();
		}

	// if CA does not exist, create it
	} else {
		dpm($data->id);
		// save file
		$source = 'public://newcas/' . $data->id . '.pdf';
		$uri = 'public://collective-agreements/' . $data->id . '.pdf';
		$pdf = \Drupal\file\Entity\File::create(['uri' => $uri]);
		if (!file_exists($source)) {
			$error = t('File @file does not exist.', ['@file' => $source]);
			\Drupal::messenger()->addError($error);
		} else {
			$filesystem = \Drupal::service('file_system');
			$filesystem->copy($source, $uri, 'FILE_EXISTS_REPLACE');
			$pdf->setPermanent();
			$pdf->setMimeType('application/pdf');
			$pdf->save();
		}

		// save CA entity
		$ca_info = [
			'bundle' => 'lrb_collective_agreement',
			'name' => $data->id,
			'field_lrb_ca_id' => $data->id,
			'field_lrb_ca_employer' => $employer->id(),
			'field_lrb_ca_union' => $union->id(),
			'field_media_file' => $pdf->id(),
		];

		$ca = \Drupal\media\Entity\Media::create($ca_info);
		$ca->save();

		// $message = t('CA @ca created.', ['@ca' => $data->id]);
	}

	\Drupal::messenger()->addStatus($message);
}

function weaver_import_ca_industries($data, &$context) {
	$employer_name = trim($data->employer);
	$industry_name = trim($data->industry);
	$ca = trim($data->id);

	// get employer node
	$query = \Drupal::entityQuery('node');
	$query->condition('type', 'lrb_employer');
	$query->condition('title', $employer_name);
	$query->range(0,1);
	$nids = $query->execute();


	// if Employer entity exists, use it
	if (count($nids)) {

		$employer = \Drupal\node\Entity\Node::load($nids[array_key_first($nids)]);
	
		// make sure taxonomy term exists
		$query = \Drupal::entityQuery('taxonomy_term');
		$query->condition('vid', 'industries');
		$query->condition('name', $industry_name);
		$query->range(0,1);
		$tids = $query->execute();

		// if exists, set employer to industry
		if (count($tids)) {
				$employer->set('field_lrb_employer_industries', $tids[array_key_first($tids)]);
				$employer->save();
		} else {
			// if not exists, create it and add employer to industry

			$error = t('Industry @industry does not exist.', ['@industry' => $industry_name]);
			
			$term_info = [
				'vid' => 'industries',
				'name' => $industry_name
			];

			$term = \Drupal\taxonomy\Entity\Term::create($term_info);
			$employer->set('field_lrb_employer_industries', $term->id());
			$employer->save();
		}
	}

	\Drupal::messenger()->addError($error);
}

function weaver_import_ca_terms($data, &$context) {
	$ca = trim($data->id);
	$term = trim($data->term);

	// try and get dates from $term
	if (strpos($term, '-') != FALSE) {
		$dates = explode('-', $term);
		$start_date = trim($dates[0]);
		$end_date = trim($dates[1]);

	} else if (strpos($term, ' to ') != FALSE) {

		// try "to"
		$dates = explode(' to ', $term);
		$start_date = trim($dates[0]);
		$end_date = trim($dates[1]);

	} else {

		// only one date
		$start_date = trim($term);
		$end_date = null;
	}

	if ($start_date) { 
		$start_datetime = new \DateTime($start_date);
		$start_value = $start_datetime->format('Y-m-d');
	}
	if ($end_date) { 
		$end_datetime = new \DateTime($end_date);
		$end_value = $end_datetime->format('Y-m-d'); 
	}

	if ($start_datetime != null || $end_datetime != null) {
		// get ca
		$query = \Drupal::entityQuery('media');
		$query->condition('bundle', 'lrb_collective_agreement');
		$query->condition('name', $ca);
		$query->range(0,1);
		$mids = $query->execute();

		if (count($mids)) {
			$ca_entity = \Drupal\media\Entity\Media::load($mids[array_key_first($mids)]);

			if ($start_datetime != null) {
				$ca_entity->set('field_lrb_ca_start', $start_value);
			}
			
			if ($end_datetime != null) {
				$ca_entity->set('field_lrb_ca_end', $end_value);
			}

			$ca_entity->save();
		}
	}

	if ($error) {
		\Drupal::messenger()->addError($error);
	}
}