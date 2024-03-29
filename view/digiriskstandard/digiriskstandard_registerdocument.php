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
 *   	\file       view/digiriskstandard/digiriskstandard_registerdocument.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to view registerdocument
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
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/registerdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $db, $conf, $langs, $hookmanager, $user;

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$action    = GETPOST('action', 'aZ09');
$subaction = GETPOST('subaction', 'aZ09');

// Initialize technical objects
$object   = new DigiriskStandard($db);
$document = new RegisterDocument($db);
$contact  = new Contact($db);
$project  = new Project($db);

$hookmanager->initHooks(array('digiriskelementregisterdocument', 'digiriskstandardview', 'globalcard')); // Note that conf->hooks_modules contains array

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);

$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1];

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->digiriskstandard->read && $user->rights->digiriskdolibarr->registerdocument->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->registerdocument->write;
$permissiontodelete = $user->rights->digiriskdolibarr->registerdocument->delete;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;
    $previousRef = $object->ref;
    $object->ref = '';

	// Actions builddoc, forcebuilddoc, remove_file.
	require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

	// Action to generate pdf from odt file
	require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

    $object->ref = $previousRef;
}

/*
 * View
 */

$title   = $langs->trans('RegisterDocument');
$helpUrl = 'FR:Module_Digirisk#Soci.C3.A9t.C3.A9.2FOrganisation';

digirisk_header($title, $helpUrl); ?>

<div id="cardContent" value="">

<?php // Part to show record
$res  = $object->fetch_optionals();

saturne_get_fiche_head($object, 'standardRegisterDocument', $title);

// Object card
// ------------------------------------------------------------
// Project
$morehtmlref = '<div class="refidno">';
$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
$morehtmlref .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
$morehtmlref .= '</div>';

$moduleNameLowerCase = 'mycompany';
saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true);
$moduleNameLowerCase = 'digiriskdolibarr';

print '<div class="fichecenter">';
print '<table class="border centpercent tableforfield">' . "\n";

print '</table>';
print '</div>';

print dol_get_fiche_end();

// Document Generation -- Génération des documents
$dirFiles   = 'registerdocument';
$filedir    = $upload_dir . '/' . $dirFiles ;
$urlsource  = $_SERVER["PHP_SELF"];
$modulepart = 'digiriskdolibarr:RegisterDocument';

if ($permissiontoadd || $permissiontoread) {
	$genallowed = 1;
}

print saturne_show_documents($modulepart, $dirFiles, $filedir, $urlsource, 1,1, '', 1, 0, 0, 0, 0, '', 0, '', empty($soc->default_lang) ? '' : $soc->default_lang, $object);

// End of page
llxFooter();
$db->close();
