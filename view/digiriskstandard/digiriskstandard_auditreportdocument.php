<?php
/* Copyright (C) 2024-2025 EVARISK <technique@evarisk.com>
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

// Better performance by disabling some features not used in this page
if (!defined('DISABLE_CKEDITOR')) {
    define('DISABLE_CKEDITOR', 1);
}
if (!defined('DISABLE_JQUERY_TABLEDND')) {
    define('DISABLE_JQUERY_TABLEDND', 1);
}
if (!defined('DISABLE_JQUERY_JNOTIFY')) {
    define('DISABLE_JQUERY_JNOTIFY', 1);
}
if (!defined('DISABLE_JS_GRAPH')) {
    define('DISABLE_JS_GRAPH', 1);
}
if (!defined('DISABLE_MULTISELECT')) {
    define('DISABLE_MULTISELECT', 1);
}

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/auditreportdocument.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdocuments/riskassessmentdocument.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id        = GETPOSTINT('id');
$action    = GETPOST('action', 'aZ09');
$subaction = GETPOST('subaction', 'aZ09');

// Initialize technical objects
$object   = new DigiriskStandard($db);
$document = new AuditReportDocument($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$object->element . $document->element, $object->element . 'view', 'globalcard']); // Note that conf->hooks_modules contains array

if (empty($action) && empty($id)) {
    $action = 'view';
}

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once
$object->fk_project = getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT');

$upload_dir = $conf->{$object->module}->multidir_output[$object->entity ?? 1];

// Security check - Protection if external user
$permissionToRead   = $user->hasRight($object->module, $object->element, 'read') && $user->hasRight($object->module, $document->element, 'read');
$permissiontoadd    = $user->hasRight($object->module, $document->element, 'write');
$permissiontodelete = $user->hasRight($object->module, $document->element, 'delete');
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
            $moreParams['recipient'] = GETPOST('recipient');
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
if ($object->id > 0) {
    saturne_get_fiche_head($object, 'standardAuditReportDocument', $title);
    saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', '', true);

    print load_fiche_titre($langs->trans('Config'), '', '');

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '#builddoc" id="builddoc_form">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Parameters') . '</td>';
    print '<td>' . $langs->trans('Value') . '</td>';
    print '</tr>';

    // DateRange -- Plage de date
    $firstDayOfTheYear = dol_get_first_day(date('Y)'));
    print '<tr class="oddeven"><td>' . $langs->trans("DateRange") . '</td>';
    print '<td>' . $langs->trans('From') . $form->selectDate($firstDayOfTheYear, 'datestart');
    print $langs->trans('At') . $form->selectDate(dol_now(), 'dateend');
    print $langs->trans('UseDateRange');
    print '<input type="checkbox" id="daterange" name="daterange" checked>';
    print '</td></tr>';

    // Destinataire
    $userRecipient = json_decode(getDolGlobalString('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT'));
    print '<tr class="oddeven"><td>' . $langs->trans('Recipient') . '</td>';
    print '<td>' . $form->select_dolusers($userRecipient, 'recipient', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'minwidth400', 0, 0, true);
    print '</td></tr>';
    print '</table>';

    print dol_get_fiche_end();

    print '<div class="fichecenter"><div class="fichehalfleft">';

    $objRef    = dol_sanitizeFileName($object->ref);
    $dirFiles  = $document->element . '/' . $objRef;
    $fileDir   = $upload_dir . '/' . $dirFiles;
    $urlSource =  $_SERVER['PHP_SELF'] .'?id=' . $object->id;

    print saturne_show_documents($object->module . ':AuditReportDocument', $dirFiles, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, 1, 0, '', '', $langs->defaultlang, '', $object);
    print '</form>';

    print '</div><div class="fichehalfright">';

    $moreHtmlCenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saturne/view/saturne_agenda.php', 1) . '?id=' . $object->id . '&module_name=DigiriskDolibarr&object_type=' . $object->element . '&show_nav=0&handle_photo=true');

    // List of actions on element
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
    $formActions = new FormActions($db);
    $formActions->showactions($object, $object->element . '@' . $object->module, 0, 1, '', 10, '', $moreHtmlCenter);

    print '</div></div>';
}

// End of page
llxFooter();
$db->close();
