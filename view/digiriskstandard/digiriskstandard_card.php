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

require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../class/dashboarddigiriskstats.class.php';

global $db, $conf, $langs, $user,  $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'alpha');

// Initialize technical objects
$object  = new DigiriskStandard($db);
$stats   = new DashboardDigiriskStats($db);
$project = new Project($db);

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->riskassessmentdocument->read;

saturne_check_access($permissiontoread);

/*
 *  Actions
*/

if ($action == 'adddashboardinfo') {
	$data = json_decode(file_get_contents('php://input'), true);

	$dashboardWidgetName = $data['dashboardWidgetName'];

	$visible = json_decode($user->conf->DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO);
	unset($visible->$dashboardWidgetName);

	$tabparam['DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO'] = json_encode($visible);

	dol_set_user_param($db, $conf, $user, $tabparam);
	$action = '';
}

if ($action == 'closedashboardinfo') {
	$data = json_decode(file_get_contents('php://input'), true);

	$dashboardWidgetName = $data['dashboardWidgetName'];

	$visible = json_decode($user->conf->DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO);
	$visible->$dashboardWidgetName = 0;

	$tabparam['DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO'] = json_encode($visible);

	dol_set_user_param($db, $conf, $user, $tabparam);
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
	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">' . saturne_show_medias_linked('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 1, 0, 0, 0, 80, 80, 0, 0, 0, 'logos', $object, 'photo',0, 0) . '</div>';

	digirisk_banner_tab($object, '', '', 0, '', '', $morehtmlref, '', '', $morehtmlleft);

	print '<div class="fichecenter">';
	print '<br>';

	$stats->show_dashboard(1,1,1,0);

	print '<div class="fichecenter">';

	print dol_get_fiche_end();
}


// End of page
llxFooter();
$db->close();
