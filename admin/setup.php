<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * \file    digiriskdolibarr/admin/setup.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr setup page.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/mod_project_simple.php';
dol_include_once('/custom/digiriskdolibarr/lib/digiriskdolibarr.lib.php');

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Initialize technical objects
$project     = new Project($db);
$third_party = new Societe($db);
$projectRef  = new $conf->global->PROJECT_ADDON();

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$backtopage = GETPOST('backtopage', 'alpha');

$setupnotempty = 0;

/*
 * View
 */

$page_name = "DigiriskdolibarrSetup";
$help_url = 'FR:Module_DigiriskDolibarr#Configuration';

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "digiriskdolibarr@digiriskdolibarr");

if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("AgendaModuleRequired");
}

//Check projet
$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);

if ( $conf->global->DIGIRISKDOLIBARR_DU_PROJECT == 0 || $project->statut == 2 ) {
	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('RiskAssessmentDocument');
	$project->description = $langs->trans('RiskAssessmentDocumentDescription');
	$project->date_c      = dol_now();
	$currentYear = date('Y');
	$fiscalMonthStart = date('m', strtotime($conf->global->SOCIETE_FISCAL_MONTH_START));
	$startdate = $currentYear . '-' . $test . '-01';
	$project->date_start = $startdate;
	$project->usage_task  = 1;
	$enddate = date('Y-m-d', strtotime(date("Y-m-d", strtotime($startdate)) . " + 1 year"));
	$project->date_end = $enddate;
	$project->statut      = 1;
	$project_id = $project->create($user);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DU_PROJECT', $project_id, 'integer', 1, '',$conf->entity);
}

// Page end
dol_fiche_end();

llxFooter();
$db->close();
