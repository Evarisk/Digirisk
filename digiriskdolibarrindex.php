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
 *	\file       digiriskdolibarrindex.php
 *	\ingroup    digiriskdolibarr
 *	\brief      Home page of digiriskdolibarr top menu
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr"));
if ( $conf->global->DIGIRISKDOLIBARR_DU_PROJECT == 0 ) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/modules/project/mod_project_simple.php';

	$project     = new Project($db);
	$third_party = new Societe($db);
	$projectRef  = new $conf->global->PROJECT_ADDON();

	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('RiskAssessmentDocument');
	$project->description = $langs->trans('RiskAssessmentDocumentDescription');
	$project->date_c      = dol_now();
	//$project->date_start = dol_now(); -> option
	$project->usage_task  = 1;
	//$project->date_end = dol_now(); -> option
	$project->statut      = 1;
	$project_id = $project->create($user);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DU_PROJECT', $project_id, 'integer', 1, '',$conf->entity);

}
// Security check
//if (!$user->rights->digiriskdolibarr->read) accessforbidden();

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr';

llxHeader("", $langs->trans("DigiriskDolibarrArea"), $help_url);

print load_fiche_titre($langs->trans("DigiriskDolibarrArea"), '', 'digiriskdolibarr.png@digiriskdolibarr');

// End of page
llxFooter();
$db->close();
