<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    view/digiriskelement/digiriskelement_jobsheet.php
 * \ingroup digiriskdolibarr
 * \brief   Tab of a GP/UT to design its job sheet ("fiche de poste") after the INRS ED 126 model
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Digirisk libraries
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/class/jobsheet.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskdolibarrdocuments/jobsheetdocument.class.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php');

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id     = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object   = new DigiriskElement($db);
$document = new JobSheetDocument($db);
$jobSheet = new JobSheet($db);

$hookmanager->initHooks(['digiriskelementjobsheet', 'digiriskelementview', 'globalcard']);

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

// Security check
$permissionToRead   = $user->hasRight('digiriskdolibarr', 'digiriskelement', 'read');
$permissiontoadd    = $user->hasRight('digiriskdolibarr', 'digiriskelement', 'write');
$permissiontodelete = $user->hasRight('digiriskdolibarr', 'digiriskelement', 'delete');
saturne_check_access($permissionToRead, $object);

// Machine of the sheet: the one asked when it is linked to the GP/UT, the first linked one by default, none when 0
$products  = $jobSheet->getLinkedProducts($object);
$productId = GETPOSTISSET('fk_product') ? GETPOSTINT('fk_product') : (int) array_key_first($products);
$product   = $products[$productId] ?? null;
$productId = is_object($product) ? (int) $product->id : 0;

/*
 * Actions
 */

$parameters = ['product' => $product];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // The machine of the generation: kept in the JSON of the document for the versions, given to the PDF model
    $document->json           = json_encode(['fk_product' => $productId]);
    $moreParams['fk_product'] = $productId;

    // Actions builddoc, forcebuilddoc, remove_file
    require_once dol_buildpath('/saturne/core/tpl/documents/documents_action.tpl.php');
}

/*
 * View
 */

$title   = $langs->trans('JobSheet');
$helpUrl = 'FR:Module_Digirisk';

$data      = $jobSheet->load($object, $product);
$checklist = $jobSheet->getChecklist($data);

// Documents block of Saturne, the machine stays selected through the generation
$dirFiles  = $document->element . '/' . dol_sanitizeFileName($object->ref);
$fileDir   = $upload_dir . '/' . $dirFiles;
$urlSource = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&fk_product=' . $productId;

digirisk_header($title, $helpUrl);

saturne_get_fiche_head($object, 'elementJobSheet', $title);

list($morehtmlref, $moreParams) = $object->getBannerTabContent();
saturne_banner_tab($object, 'ref', 'none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);

require_once dol_buildpath('/digiriskdolibarr/core/tpl/digiriskelement/digiriskelement_jobsheet_view.tpl.php');

print dol_get_fiche_end();

llxFooter();
$db->close();
