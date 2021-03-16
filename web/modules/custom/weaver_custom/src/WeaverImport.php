<?php

namespace Drupal\weaver_import;

class WeaverImport {

	public static function ca($data) {
		dpm($data);
		die();
		$message = t('Completed');
		\Drupal::messenger()->addMessage($message);
	}

	public static function importCompleteCallback() {
		die('test');
		$message = t('Completed');
		\Drupal::messenger()->addMessage($message);
	}
}