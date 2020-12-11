<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 Eoxia <dev@eoxia.com>
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
 * \file    digiriskdolibarr/admin/accronym.php
 * \ingroup digiriskdolibarr
 * \brief   Accronym page of module DigiriskDolibarr.
 */

// Load Dolibarr environment
require "../../../main.inc.php";

// Libraries
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/lib/digiriskdolibarr.lib.php';

// Translations
$langs->loadLangs(array("errors", "admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

global $conf;
$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
	|| ($action == 'updateedit'))
{
	$allLinks = digirisk_dolibarr_fetch_links($db, 'all');

	digirisk_dolibarr_set_const($db, "ACCRONYM_LEGAL_DISPLAY", GETPOST("accronym_legal_display", 'none'), 'chaine', 0, '', $conf->entity);
	digirisk_dolibarr_set_const($db, "ACCRONYM_INFORMATIONS_SHARING", GETPOST("accronym_informations_sharing", 'none'), 'chaine', 0, '', $conf->entity);

	if ($action != 'updateedit' && !$error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

$page_name = "DigiriskDolibarrSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
dol_fiche_head($head, 'accronym', $langs->trans("Accronym"), 0, 'digiriskdolibarr@digiriskdolibarr');

dol_include_once('/digiriskdolibarr/core/modules/modDigiriskDolibarr.class.php');

$digiriskconst = digirisk_dolibarr_fetch_const($db);

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent editmode">';

print '<tr class="oddeven"><td><label for="accronym_legal_display">'.$langs->trans("AccronymLegalDisplay").'</label></td><td>';
print '<input name="accronym_legal_display" id="accronym_legal_display" class="minwidth200" value="'.($digiriskconst->ACCRONYM_LEGAL_DISPLAY ? $digiriskconst->ACCRONYM_LEGAL_DISPLAY : '').'"'.(empty($digiriskconst->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td><label for="accronym_informations_sharing">'.$langs->trans("AccronymInformationsSharing").'</label></td><td>';
print '<input name="accronym_informations_sharing" id="accronym_informations_sharing" class="minwidth200" value="'.($digiriskconst->ACCRONYM_INFORMATIONS_SHARING ? $digiriskconst->ACCRONYM_INFORMATIONS_SHARING : '').'"'.(empty($digiriskconst->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
print '</div>';
print '</form>';

// Page end
dol_fiche_end();
llxFooter();
$db->close();
