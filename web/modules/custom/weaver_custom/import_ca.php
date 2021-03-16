//<?php

$json = '[
 {
   "employer": "B.C. (\"Chaos 3D\") - see also BC and Yukon Council of Film Unions (Collective Agreement)",
   "id": "WWG28"
 },
 {
   "employer": "B.C. Ltd. (\"Rogue Webisodes\") - see also BC and Yukon Council of Film Unions (Collective Agreement)",
   "id": "WWG28"
 },
 {
 		"employer": "Chesapeake Shores S1 Productions Inc. (\"Chesapeake Shores Season 1\") - see also Canadian Media Production Association - BC Producers\' Branch (Collective Agreement)",
 		"id": "WWP19"
 	}
]';

$agreements = json_decode($json);

foreach ($agreements as $a) {
	$master = null;
	$employer_name = $a->employer;

	if (strstr($employer_name, ' - see also BC and Yukon Council of Film Unions (Collective Agreement)')) {
		$employer_name = str_replace(' - see also BC and Yukon Council of Film Unions (Collective Agreement)', '', $employer_name);
		$master = 'British Columbia and Yukon Council of Film Unions';
	}

	if (strstr($employer_name, ' - see also Canadian Media Production Association-BC Producers\' Branch (Collective Agreement)') || strstr($employer_name, ' - see also Canadian Media Production Association - BC Producers\' Branch (Collective Agreement)')) {
		$employer_name = str_replace(' - see also Canadian Media Production Association-BC Producers\' Branch (Collective Agreement)', '', $employer_name);
		$employer_name = str_replace(' - see also Canadian Media Production Association - BC Producers\' Branch (Collective Agreement)', '', $employer_name);

		$master = 'Canadian Media Production Association - BC Producers\' Branch';
	}

	if ($master != null) {
		$master_org = null;
		$master_nids = \Drupal::entityQuery('node')->condition('type', 'lrb_employer')->condition('title', $master)->range(0,1)->execute();
		if (count($master_nids)) {
			$master_org = \Drupal\node\Entity\Node::load($master_nids[array_key_first($master_nids)]);
		}
		dpm($master_org);
	}

	$query = \Drupal::entityQuery('node');
	$query->condition('type', 'lrb_employer');
	$query->condition('title', $employer_name);
	$query->range(0,1);
	$nids = $query->execute();

	// if Employer entity exists, use it
	if (count($nids)) {

		$employer = \Drupal\node\Entity\Node::load($nids[array_key_first($nids)]);
		$message = t('@employer already exists.', ['@employer' => $employer_name]);

	// if Employer does not exist, create it
	} else {

		$employer_info = [
			'type' => 'lrb_employer',
			'title' => $employer_name,
		];
		

		$employer = \Drupal\node\Entity\Node::create($employer_info);
		
		// add link to master org if exists
		if ($master_org != null) {
			$employer->set('field_lrb_employer_master', $master_org->id());
		}
		
		$employer->save();

		$message = t('@employer node created.', ['@employer' => $employer_name]);
	}
	\Drupal::messenger()->addStatus($message);

	// check if Collective Agreement exists
	$query = \Drupal::entityQuery('media');
	$query->condition('bundle', 'lrb_collective_agreement');
	$query->condition('name', $a->id);
	$query->range(0,1);
	$mids = $query->execute();

	// if CA exists, use it but add employer
	if (count($mids)) {
		$ca = \Drupal\media\Entity\Media::load($mids[array_key_first($mids)]);
		$message = t('CA @ca already exists.', ['@ca' => $a->id]);

		$ca->get('field_lrb_ca_employer')->appendItem(['target_id' => $employer->id()]);
		$ca->save();

	// if CA does not exist, create it
	} else {

		// save file
		$source = 'public://pdfs/ca/www.lrb.bc.ca/cas/' . $a->id . '.pdf';
		$uri = 'public://collective-agreements/' . $a->id . '.pdf';
		$pdf = \Drupal\file\Entity\File::create(['uri' => $uri]);
		$filesystem = \Drupal::service('file_system');
		$filesystem->copy($source, $uri, 'FILE_EXISTS_REPLACE');
		$pdf->setPermanent();
		$pdf->setMimeType('application/pdf');
		$pdf->save();

		// save CA entity
		$ca_info = [
			'bundle' => 'lrb_collective_agreement',
			'name' => $a->id,
			'field_lrb_ca_id' => $a->id,
			'field_lrb_ca_employer' => $employer->id(),
			'field_media_file' => $pdf->id(),
		];

		$ca = \Drupal\media\Entity\Media::create($ca_info);
		$ca->save();

		$message = t('CA @ca created.', ['@ca' => $a->id]);
	}

	\Drupal::messenger()->addStatus($message);
}