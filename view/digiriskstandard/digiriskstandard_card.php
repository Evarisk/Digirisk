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
 * \file    view/digiriskstandard/digiriskstandard_card.php
 * \ingroup digiriskdolibarr
 * \brief   Page to digiriskstandard informations and dashboard
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedashboard.class.php';

// Load Digirisk libraries
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleName, $moduleNameLowerCase, $moduleNameUpperCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action    = GETPOST('action', 'alpha');
$subaction = GETPOST('subaction', 'alpha');

// Initialize technical objects
$object    = new DigiriskStandard($db);
$project   = new Project($db);
$dashboard = new SaturneDashboard($db, $moduleNameLowerCase);

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity ?? 1];

$hookmanager->initHooks(['digiriskstandardcard', 'digiriskstandardview', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
$object->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_ACTIVE_STANDARD'));

// Security check
$permissiontoread = $user->rights->digiriskdolibarr->digiriskstandard->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Actions adddashboardinfo, closedashboardinfo, generate_csv
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/dashboard_actions.tpl.php';
}

/*
 * View
 */

$title   = $langs->trans('Informations');
$helpUrl = 'FR:Module_Digirisk';

digirisk_header($title, $helpUrl);

// Part to show record
saturne_get_fiche_head($object, 'standardCard', $title);

// Object card
// Project
$moreHtmlRef = '<div class="refidno">';
$project->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT'));
$moreHtmlRef .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
$moreHtmlRef .= '</div>';

saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $moreHtmlRef, true);
$moduleNameLowerCase = 'digiriskdolibarr';

print '<div class="fichecenter"><br>';

$moreParams = ['LoadRiskAssessmentDocument' => 1];
$dashboard->show_dashboard($moreParams);

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
