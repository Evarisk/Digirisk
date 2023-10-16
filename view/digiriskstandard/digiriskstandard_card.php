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
 *   	\file       view/digiriskstandard/digiriskstandard_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view digiriskstandard
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnedashboard.class.php';

require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskdolibarrdashboard.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $moduleNameLowerCase, $moduleNameUpperCase, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'alpha');

// Initialize technical objects
$object    = new DigiriskStandard($db);
$project   = new Project($db);
$dashboard = new SaturneDashboard($db, $moduleNameLowerCase);

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);

$hookmanager->initHooks(array('digiriskelementcard', 'digiriskstandardview', 'globalcard')); // Note that conf->hooks_modules contains array

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->digiriskstandard->read && $user->rights->digiriskdolibarr->riskassessmentdocument->read;
saturne_check_access($permissiontoread);

/*
 *  Actions
*/

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

/*
 * View
 */

$emptyobject = new stdClass();

$title    = $langs->trans("DigiriskStandardInformation");
$helpUrl  = 'FR:Module_Digirisk#DigiRisk_-_Document_Unique';

digirisk_header($title, $helpUrl); ?>

<div id="cardContent" value="">

<?php // Part to show record
if ((empty($action) || ($action != 'edit' && $action != 'create'))) {
	saturne_get_fiche_head($object, 'standardCard', $title);

	// Object card
	// Project
	$morehtmlref = '<div class="refidno">';
	$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
	$morehtmlref .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
	$morehtmlref .= '</div>';

	$moduleNameLowerCase = 'mycompany';
	saturne_banner_tab($object,'ref','none', 1, 'ref', 'ref', $morehtmlref, true);
	$moduleNameLowerCase = 'digiriskdolibarr';

	print '<div class="fichecenter">';
	print '<br>';

	$moreParams = [
		'loadAccident' => 0
	];

	$dashboard->show_dashboard($moreParams);

	print '<div class="fichecenter">';

	print dol_get_fiche_end();
}


// End of page
llxFooter();
$db->close();
