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
 * \file    view/digiriskstandard/digiriskstandard_legaldisplay.php
 * \ingroup digiriskdolibarr
 * \brief  Page to view legaldisplay
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturneschedules.class.php';
require_once __DIR__ . '/../../../saturne/class/saturnedashboard.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/legaldisplay.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action    = GETPOST('action', 'aZ09');
$subaction = GETPOST('subaction', 'aZ09');

// Initialize technical objects
$object    = new DigiriskStandard($db);
$document  = new LegalDisplay($db);
$usertmp   = new User($db);
$contact   = new Contact($db);
$project   = new Project($db);
$dashboard = new SaturneDashboard($db, $moduleNameLowerCase);

$hookmanager->initHooks(['digiriskelementlegaldisplay', 'digiriskstandardview', 'globalcard']); // Note that conf->hooks_modules contains array

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

// Security check - Protection if external user
$permissionToRead   = $user->rights->digiriskdolibarr->digiriskstandard->read && $user->rights->digiriskdolibarr->legaldisplay->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->legaldisplay->write;
$permissiontodelete = $user->rights->digiriskdolibarr->legaldisplay->delete;
saturne_check_access($permissionToRead);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    $previousRef = $object->ref;
    $object->ref = '';

    // Actions builddoc, forcebuilddoc, remove_file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

    // Action to generate pdf from odt file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';

    $object->ref = $previousRef;
}

/*
 * View
 */

$title   = $langs->trans('LegalDisplay');
$helpUrl = 'FR:Module_Digirisk#Soci.C3.A9t.C3.A9.2FOrganisation';

digirisk_header($title, $helpUrl);

saturne_get_fiche_head($object, 'standardLegalDisplay', $title);

// Object card
// ------------------------------------------------------------
// Project
$moreHtmlRef = '<div class="refidno">';
$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
$moreHtmlRef .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
$moreHtmlRef .= '</div>';

$moduleNameLowerCase = 'mycompany';
saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $moreHtmlRef, true);
$moduleNameLowerCase = 'digiriskdolibarr';

//print '<div class="valignmiddle"> <a href="../../admin/securityconf.php" target="_blank">' . $langs->trans('ConfigureSecurityAndSocialData') . ' <i class="fas fa-external-link-alt"></i></a></div>';
print '<div class="fichecenter">';
$moreParams = ['loadLegalDisplay' => 1];
$dashboard->show_dashboard($moreParams);
print '</div>';

print dol_get_fiche_end();

// Document Generation -- Génération des documents
$dirFiles   = 'legaldisplay';
$fileDir    = $upload_dir . '/' . $dirFiles;
$urlSource  = $_SERVER["PHP_SELF"];
$modulePart = 'digiriskdolibarr:LegalDisplay';

print saturne_show_documents($modulePart, $dirFiles, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, 0, 0, '', '', $langs->defaultlang, 0, $object);

// End of page
llxFooter();
$db->close();
