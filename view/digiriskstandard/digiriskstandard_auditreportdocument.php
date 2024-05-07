<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    view/digiriskstandard/digiriskstandard_auditreportdocument.php
 * \ingroup digiriskdolibarr
 * \brief   Page to view auditreportdocument
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

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/auditreportdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action    = GETPOST('action', 'aZ09');
$subaction = GETPOST('subaction', 'aZ09');

// Initialize technical objects
$object   = new DigiriskStandard($db);
$document = new AuditReportDocument($db);
$project  = new Project($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks(['digiriskstandardauditreportdocument', 'digiriskstandardview', 'globalcard']); // Note that conf->hooks_modules contains array

$object->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_ACTIVE_STANDARD'));

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

// Security check - Protection if external user
$permissionToRead   = $user->rights->digiriskdolibarr->digiriskstandard->read && $user->rights->digiriskdolibarr->auditreportdocument->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->auditreportdocument->write;
$permissiontodelete = $user->rights->digiriskdolibarr->auditreportdocument->delete;
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
    // Actions builddoc, forcebuilddoc, remove_file
    if (($action == 'builddoc' || GETPOST('forcebuilddoc')) && $permissiontoadd) {
        if (GETPOST('daterange')) {
            $moreParams['dateStart'] = dol_mktime(0, 0, 0, GETPOST('datestartmonth', 'int'), GETPOST('datestartday', 'int'), GETPOST('datestartyear', 'int'));
            $moreParams['dateEnd']   = dol_mktime(0, 0, 0, GETPOST('dateendmonth', 'int'), GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'));
        }
    }

    // Actions builddoc, forcebuilddoc, remove_file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

    // Action to generate pdf from odt file
    require_once __DIR__ . '/../../../saturne/core/tpl/documents/saturne_manual_pdf_generation_action.tpl.php';
}

/*
 * View
 */

$title    = $langs->trans('AuditReportDocument');
$help_url = 'FR:Module_Digirisk';

digirisk_header($title, $help_url);

// Part to show record
saturne_get_fiche_head($object, 'standardAuditReportDocument', $title);

$moreHtmlRef = '<div class="refidno">';
$project->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT'));
$moreHtmlRef .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
$moreHtmlRef .= '</div>';

$moduleNameLowerCase = 'mycompany';
saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $moreHtmlRef, true);
$moduleNameLowerCase = 'digiriskdolibarr';

print dol_get_fiche_end();

print load_fiche_titre($langs->trans('Config'), '', '');

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '#builddoc" id="builddoc_form">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

// DateRange -- Plage de date
$dateStart = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') ? dol_mktime(0, 0, 0, getDolGlobalInt('SOCIETE_FISCAL_MONTH_START'), 1, strftime("%Y", dol_now())) : dol_now();
print '<tr class="oddeven"><td>' . $langs->trans("DateRange") . '</td>';
print '<td>' . $langs->trans('From') . $form->selectDate($dateStart, 'datestart');
print $langs->trans('At') . $form->selectDate(dol_time_plus_duree($dateStart, 1, 'y'), 'dateend');
print $langs->trans('UseDateRange');
print '<input type="checkbox" id="daterange" name="daterange" checked>';
print '</td></tr>';
print '</table>';

$objRef    = dol_sanitizeFileName($object->ref);
$dirFiles  = 'auditreportdocument/' . $objRef;
$fileDir   = $upload_dir . '/' . $dirFiles;
$urlSource = $_SERVER['PHP_SELF'];

print saturne_show_documents('digiriskdolibarr:AuditReportDocument', $dirFiles, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, 1, 0, '', '', $langs->defaultlang, '', $object);
print '</form>';

// End of page
llxFooter();
$db->close();
