<?php
/* Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *	\file       htdocs/admin/accountant.php
 *	\ingroup    accountant
 *	\brief      Setup page to configure accountant / auditor
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/digiriskdolibarr.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/class/digiriskresources.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'adminsocial'; // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

if (!$user->admin) accessforbidden();

$error = 0;
$hookmanager->initHooks(array('globaladmin'));

global $conf, $db;

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
	|| ($action == 'updateedit'))
{

	$projectLinked = GETPOST('projectLinked', 'none');
	$projectLinked  = preg_split('/_/', $projectLinked);

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_PROJECT_LINKED", $projectLinked[0], 'integer', 0, '', $conf->entity);


	if ($action != 'updateedit' && !$error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$help_url = 'FR:Module_DigiriskDolibarr';
$page_name = "DigiriskdolibarrSetup";
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
dol_fiche_head($head, 'risk', '', -1, "digiriskdolibarr@digiriskdolibarr");

print '<span class="opacitymedium">'.$langs->trans("DigiriskMenu")."</span><br>\n";
print "<br>\n";
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="social_form">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("TasksManagement").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

// Project
if (!empty($conf->projet->enabled))
{
	$langs->load("projects");
	print '<tr class="oddeven"><td><label for="projectLinked">'.$langs->trans("ProjectLinked").'</label></td><td>';
	$numprojet = $formproject->select_projects(0,  $conf->global->DIGIRISKDOLIBARR_PROJECT_LINKED, 'projectLinked', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
	print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'"><span class="fa fa-plus-circle valignmiddle" title="'.$langs->trans("AddProject").'"></span></a>';
	print '</td></tr>';
}

print '</table>';
print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
print '</div>';
print '</form>';

llxFooter();
$db->close();
