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
 * \file    view/digiriskelement/digiriskelement_informations.php
 * \ingroup digiriskdolibarr
 * \brief   Page to view digiriskelement informations and dashboard
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedashboard.class.php';

// Load Digirisk libraries
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $moduleNameLowerCase, $moduleNameUpperCase, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id     = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object    = new DigiriskElement($db);
$dashboard = new SaturneDashboard($db, $moduleNameLowerCase);

$hookmanager->initHooks(['digiriskelementinformations', 'digiriskelementview', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

// Security check
$permissionToRead = $user->rights->digiriskdolibarr->digiriskelement->read;
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
    if ($action == 'adddashboardinfo' || $action == 'closedashboardinfo') {
        $data                = json_decode(file_get_contents('php://input'), true);
        $dashboardWidgetName = $data['dashboardWidgetName'];
        $confName            = $moduleNameUpperCase . '_DISABLED_DASHBOARD_INFO';
        $visible             = json_decode($user->conf->$confName);

        if ($action == 'adddashboardinfo') {
            unset($visible->$dashboardWidgetName);
        } else {
            $visible->$dashboardWidgetName = 0;
        }

        $tabParam[$confName] = json_encode($visible);

        dol_set_user_param($db, $conf, $user, $tabParam);
        $action = '';
    }
}

/*
 * View
 */

$title   = $langs->trans('Informations');
$helpUrl = 'FR:Module_Digirisk';

digirisk_header($title, $helpUrl);

// Part to show record
saturne_get_fiche_head($object, 'elementInformations', $title);

// Object card
// ------------------------------------------------------------
list($morehtmlref, $moreParams) = $object->getBannerTabContent();

saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);

print '<div class="fichecenter"><br>';

$moreParams = [
    'LoadDigiriskElement' => 1
];

$dashboard->show_dashboard($moreParams);

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
