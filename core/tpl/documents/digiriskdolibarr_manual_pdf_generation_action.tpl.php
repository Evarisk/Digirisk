<?php

if ($action == 'pdfGeneration') {
	global $langs;

	$filename = GETPOST('file');

	$file = $upload_dir . '/' . $filename;

	// Open and load template
	require_once ODTPHP_PATH . 'odf.php';
	try {
		$odfHandler = new odf(
			$file,
			array(
				'PATH_TO_TMP'	  => $conf->digiriskdolibarr->dir_temp,
				'ZIP_PROXY'		  => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
				'DELIMITER_LEFT'  => '{',
				'DELIMITER_RIGHT' => '}'
			)
		);
	} catch (Exception $e) {
		$error = $e->getMessage();
		dol_syslog($e->getMessage(), LOG_INFO);

		return -1;
	}

	$fileInfos = pathinfo($filename);
	$pdfName   = $fileInfos['filename'] . '.pdf';

	// Write new file
	if ( ! empty($conf->global->MAIN_ODT_AS_PDF) && $conf->global->DIGIRISKDOLIBARR_MANUAL_PDF_GENERATION > 0) {
		try {
			$odfHandler->exportAsAttachedPDF($file);
			setEventMessages($langs->trans("FileGenerated") . ' - ' . $pdfName, null);
		} catch (Exception $e) {
			$error = $e->getMessage();
			setEventMessages($langs->transnoentities('FileCouldNotBeGeneratedInPDF') . '<br>' . $langs->transnoentities('CheckDocumentationToEnablePDFGeneration'), null, 'errors');
			dol_syslog($e->getMessage(), LOG_INFO);
		}
	} else {
		try {
			$odfHandler->saveToDisk($file);
		} catch (Exception $e) {
			$error = $e->getMessage();
			dol_syslog($e->getMessage(), LOG_INFO);
			return -1;
		}
	}
}
