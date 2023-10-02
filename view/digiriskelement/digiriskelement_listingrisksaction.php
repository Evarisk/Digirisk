<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 *   	\file       view/digiriskelement/digiriskelement_listingrisksaction.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to view listingrisksaction
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
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/listingrisksaction.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other']);
// Get parameters
$id     = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$type   = GETPOST('type', 'aZ09');

// Initialize technical objects
$document = new ListingRisksAction($db);
$hookmanager->initHooks(array('digiriskelementlistingrisksaction', 'digiriskelementview', 'digiriskstandardview', 'globalcard')); // Note that conf->hooks_modules contains array

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
$permissiontoread   = $user->rights->digiriskdolibarr->listingrisksaction->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->listingrisksaction->write;
$permissiontodelete = $user->rights->digiriskdolibarr->listingrisksaction->delete;

saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$previousElement = $object->element;
	if ($object->element == 'digiriskstandard') {
		$object->ref = '';
	}
	$object->element = 'listingrisksaction';
	$removeDocumentFromName = 1;

	// Actions builddoc, forcebuilddoc, remove_file.
	require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

	// Action to generate pdf from odt file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';
	$object->element = $previousElement;
}

/*
 * View
 */

$emptyobject = new stdClass();

$title    = $langs->trans('ListingRisksAction');
$helpUrl  = 'FR:Module_Digirisk#Impression_des_listings_de_risques';

digirisk_header($title, $helpUrl); ?>

<div id="cardContent" value="">

<?php $res = $object->fetch_optionals();

// Part to show record
saturne_get_fiche_head($object, 'elementListingRisksAction', $title);

// Object card
// ------------------------------------------------------------
if ($type != 'standard') {
    list($morehtmlref, $moreParams) = $object->getBannerTabContent();

    saturne_banner_tab($object,'ref','', 1, 'ref', 'ref', $morehtmlref, true, $moreParams);
} else {
	// Project
	$morehtmlref = '<div class="refidno">';
	$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
	$morehtmlref .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
	$morehtmlref .= '</div>';

	$moduleNameLowerCase = 'mycompany';
	saturne_banner_tab($object,'ref','', 1, 'ref', 'ref', $morehtmlref, true);
	$moduleNameLowerCase = 'digiriskdolibarr';
}

unset($object->fields['element_type']);
unset($object->fields['fk_parent']);
unset($object->fields['last_main_doc']);
unset($object->fields['entity']);

print '<div class="fichecenter">';
print '<table class="border centpercent tableforfield">' . "\n";

// Common attributes
unset($object->fields['import_key']);
unset($object->fields['json']);
unset($object->fields['import_key']);
unset($object->fields['model_odt']);
unset($object->fields['type']);
unset($object->fields['last_main_doc']);
unset($object->fields['label']);
unset($object->fields['description']);

print '</table>';
print '</div>';

print dol_get_fiche_end();

// Document Generation -- Génération des documents
if ($type != 'standard') {
	$objref    = dol_sanitizeFileName($object->ref);
	$dirFiles  = 'listingrisksaction/' . $objref;
	$filedir   = $upload_dir . '/' . $dirFiles;
	$urlsource = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
} else {
	$dirFiles  = 'listingrisksaction';
	$filedir   = $upload_dir . '/' . $dirFiles;
	$urlsource = $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&type=standard';
}

$modulepart = 'digiriskdolibarr:ListingRisksAction';

if ($permissiontoadd || $permissiontoread) {
	$genallowed = 1;
}

print saturne_show_documents($modulepart, $dirFiles, $filedir, $urlsource, 1,1, '', 1, 0, 0, 0, 0, '', 0, '', empty($soc->default_lang) ? '' : $soc->default_lang, $object);

// End of page
llxFooter();
$db->close();
