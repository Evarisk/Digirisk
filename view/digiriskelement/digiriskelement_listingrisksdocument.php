<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       view/digiriskelement/digiriskelement_listingrisksdocument.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to view listingrisksdocument
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/listingrisksdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other']);
// Get parameters
$id          = GETPOST('id', 'int');
$action      = GETPOST('action', 'aZ09');
$subaction   = GETPOST('subaction', 'aZ09');
$type        = GETPOST('type', 'aZ09');

// Initialize technical objects
$document = new ListingRisksDocument($db);
$hookmanager->initHooks(array('digiriskelementlistingrisksdocument', 'digiriskelementview', 'digiriskstandardview', 'globalcard')); // Note that conf->hooks_modules contains array

if ($type != 'standard') {
	$object = new DigiriskElement($db);
	$object->fetch($id);
} else {
	$object = new DigiriskStandard($db);
	$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
}
$digiriskstandard = new DigiriskStandard($db);
$project          = new Project($db);

$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1];

// Security check
$permissionToRead   = $user->rights->digiriskdolibarr->digiriskstandard->read && ($user->rights->digiriskdolibarr->listingrisksdocument->read || $user->rights->digiriskdolibarr->listingrisksaction->read || $user->rights->digiriskdolibarr->listingrisksenvironmentalaction->read || $user->rights->digiriskdolibarr->listingrisksphoto->read);
$permissiontoadd    = $user->rights->digiriskdolibarr->listingrisksdocument->write || $user->rights->digiriskdolibarr->listingrisksaction->write || $user->rights->digiriskdolibarr->listingrisksenvironmentalaction->write || $user->rights->digiriskdolibarr->listingrisksphoto->write;
$permissiontodelete = $user->rights->digiriskdolibarr->listingrisksdocument->delete || $user->rights->digiriskdolibarr->listingrisksaction->delete || $user->rights->digiriskdolibarr->listingrisksenvironmentalaction->delete || $user->rights->digiriskdolibarr->listingrisksphoto->delete;
saturne_check_access($permissionToRead, $object);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
	if ($object->element == 'digiriskstandard') {
        $previousRef = $object->ref;
		$object->ref = '';
	}

    // Actions builddoc, forcebuilddoc, remove_file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

    // Action to generate pdf from odt file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

    if ($object->element == 'digiriskstandard') {
        $object->ref = $previousRef;
    }
}

/*
 * View
 */

$title   = $langs->trans('ListingRisksDocuments');
$helpUrl = 'FR:Module_Digirisk#Impression_des_listings_de_risques';

digirisk_header($title, $helpUrl);

// Part to show record
saturne_get_fiche_head($object, 'elementListingRisksDocument', $title);

// Object card
// ------------------------------------------------------------
if ($type != 'standard') {
    list($moreHtmlref, $moreParams) = $object->getBannerTabContent();

    saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $moreHtmlref, true, $moreParams);
} else {
	// Project
	$moreHtmlref = '<div class="refidno">';
	$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
	$moreHtmlref .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
	$moreHtmlref .= '</div>';
	$moduleNameLowerCase = 'mycompany';
	saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $moreHtmlref, true);
	$moduleNameLowerCase = 'digiriskdolibarr';
}

print dol_get_fiche_end();

// Document Generation -- Génération des documents
$urlSource = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
if ($type != 'standard') {
	$objRef    = dol_sanitizeFileName($object->ref);
	$dirFiles  = ['listingrisksaction/' . $objRef, 'listingrisksphoto/' . $objRef, 'listingrisksdocument/' . $objRef, 'listingrisksenvironmentalaction/' . $objRef];
} else {
    $dirFiles   = ['listingrisksaction', 'listingrisksphoto', 'listingrisksdocument', 'listingrisksenvironmentalaction'];
	$urlSource .= '&type=standard';
}
$fileDir    = [$upload_dir . '/' . $dirFiles[0], $upload_dir . '/' . $dirFiles[1], $upload_dir . '/' . $dirFiles[2], $upload_dir . '/' . $dirFiles[3]];
$modulePart = 'digiriskdolibarr:ListingRisksDocument';

print saturne_show_documents($modulePart, $dirFiles, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, 0, 0, '', '', $langs->defaultlang, 0, $object);

// End of page
llxFooter();
$db->close();
