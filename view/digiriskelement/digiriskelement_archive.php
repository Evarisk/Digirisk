<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       view/digiriskelement/digiriskelement_archive.php
 *  \ingroup    digiriskdolibarr
 *  \brief      Archive tab of a digirisk element: archived risks and archived sub elements
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $hookmanager, $langs, $user;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id        = GETPOSTINT('id');
$ref       = GETPOST('ref', 'alpha');
$action    = GETPOST('action', 'aZ09');
$elementID = GETPOSTINT('element_id');
$riskID    = GETPOSTINT('risk_id');

// Initialize technical objects
$object      = new DigiriskElement($db);
$risk        = new Risk($db);
$evaluation  = new RiskAssessment($db);
$extrafields = new ExtraFields($db);

$hookmanager->initHooks(['digiriskelementarchive', 'digiriskelementview', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once

// Permissions
$permissionToRead        = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissionToArchive     = $user->rights->digiriskdolibarr->digiriskelement->write;
$permissionToArchiveRisk = $user->rights->digiriskdolibarr->risk->write;

// Security check
saturne_check_access($permissionToRead, $object);

/*
 * Actions
 */

$parameters = ['id' => $id];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    $backToPage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_archive.php', 1) . '?id=' . $object->id;

    // Restore an archived sub element, with everything that was archived below it
    if ($action == 'unarchive_element' && $permissionToArchive) {
        $archivedElement = new DigiriskElement($db);
        // Only a direct child of the displayed element can be restored from here
        if ($archivedElement->fetch($elementID) > 0 && $archivedElement->fk_parent == $object->id) {
            if ($archivedElement->unarchive($user) > 0) {
                setEventMessages($langs->trans('ElementUnarchived', $archivedElement->ref), null);
            } else {
                setEventMessages($archivedElement->error, $archivedElement->errors, 'errors');
            }
        } else {
            setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
        }

        header('Location: ' . $backToPage);
        exit;
    }

    // Restore an archived risk into the risk list of the element
    if ($action == 'unarchive_risk' && $permissionToArchiveRisk) {
        $archivedRisk = new Risk($db);
        if ($archivedRisk->fetch($riskID) > 0 && $archivedRisk->fk_element == $object->id) {
            if ($archivedRisk->setUnarchived($user, 1) > 0) {
                setEventMessages($langs->trans('RiskUnarchived', $archivedRisk->ref), null);
            } else {
                setEventMessages($archivedRisk->error, $archivedRisk->errors, 'errors');
            }
        } else {
            setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
        }

        header('Location: ' . $backToPage);
        exit;
    }
}

/*
 * View
 */

$form    = new Form($db);
$title   = $langs->trans('Archives');
$helpUrl = 'FR:Module_Digirisk';

digirisk_header($title, $helpUrl);

if ($object->id > 0) {
    print '<div id="cardContent" value="">';

    saturne_get_fiche_head($object, 'elementArchive', $title);

    // Object card
    // ------------------------------------------------------------
    list($morehtmlref, $moreParams) = $object->getBannerTabContent();

    saturne_banner_tab($object, 'ref', 'none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);

    require_once __DIR__ . '/../../core/tpl/digiriskelement/digiriskelement_archive_view.tpl.php';

    print dol_get_fiche_end();

    print '</div>';
    print '<!-- End div class="cardcontent" -->';
}

// End of page
llxFooter();
$db->close();
