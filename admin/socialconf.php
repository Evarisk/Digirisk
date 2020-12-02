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
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'adminsocial'; // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

if (!$user->admin) accessforbidden();

$error = 0;
$hookmanager->initHooks(array('admincompany', 'globaladmin'));


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
	|| ($action == 'updateedit'))
{

	if ($action != 'updateedit' && !$error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

$help_url = '';
llxHeader('', $langs->trans("CompanyFoundation"), $help_url);

print load_fiche_titre($langs->trans("CompanyFoundation"), '', 'title_setup');

$head = company_admin_prepare_head();

dol_fiche_head($head, 'social', $langs->trans("Company"), -1, 'company');

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);

$countrynotdefined = '<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';
print '<span class="opacitymedium">'.$langs->trans("AccountantDesc")."</span><br>\n";
print "<br>\n";
/**
 * Edit parameters
 */
print "\n".'<script type="text/javascript" language="javascript">';
print '$(document).ready(function () {
		  $("#selectcountry_id").change(function() {
			document.form_index.action.value="updateedit";
			document.form_index.submit();
		  });
	  });';
print '</script>'."\n";

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

//Accords de participation
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Accords de participation").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td><label for="name">'.$langs->trans("Modalités d\'informations").'</label></td><td>';
print '<input name="nom" id="name" class="minwidth200" value="'.($conf->global->MAIN_INFO_ACCOUNTANT_NAME ? $conf->global->MAIN_INFO_ACCOUNTANT_NAME : GETPOST("nom", 'nohtml')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

//CSE
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("CSE").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td><label for="address">'.$langs->trans("Date d'élection").'</label></td><td>';
print '<textarea name="address" id="address" class="quatrevingtpercent" rows="'.ROWS_3.'">'.($conf->global->MAIN_INFO_ACCOUNTANT_ADDRESS ? $conf->global->MAIN_INFO_ACCOUNTANT_ADDRESS : GETPOST("address", 'nohtml')).'</textarea></td></tr>'."\n";

print '<tr class="oddeven"><td><label for="zipcode">'.$langs->trans("Titulaires").'</label></td><td>';
print '<input class="minwidth100" name="zipcode" id="zipcode" value="'.($conf->global->MAIN_INFO_ACCOUNTANT_ZIP ? $conf->global->MAIN_INFO_ACCOUNTANT_ZIP : GETPOST("zipcode", 'alpha')).'"></td></tr>'."\n";

print '<tr class="oddeven"><td><label for="town">'.$langs->trans("Suppléants").'</label></td><td>';
print '<input name="town" class="minwidth100" id="town" value="'.($conf->global->MAIN_INFO_ACCOUNTANT_TOWN ? $conf->global->MAIN_INFO_ACCOUNTANT_TOWN : GETPOST("town", 'nohtml')).'"></td></tr>'."\n";

//Délégués du personnel
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Délégués du personnel").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td><label for="address">'.$langs->trans("Date d\'élection").'</label></td><td>';
print '<textarea name="address" id="address" class="quatrevingtpercent" rows="'.ROWS_3.'">'.($conf->global->MAIN_INFO_ACCOUNTANT_ADDRESS ? $conf->global->MAIN_INFO_ACCOUNTANT_ADDRESS : GETPOST("address", 'nohtml')).'</textarea></td></tr>'."\n";

print '<tr class="oddeven"><td><label for="zipcode">'.$langs->trans("Titulaires").'</label></td><td>';
print '<input class="minwidth100" name="zipcode" id="zipcode" value="'.($conf->global->MAIN_INFO_ACCOUNTANT_ZIP ? $conf->global->MAIN_INFO_ACCOUNTANT_ZIP : GETPOST("zipcode", 'alpha')).'"></td></tr>'."\n";

print '<tr class="oddeven"><td><label for="town">'.$langs->trans("Suppléants").'</label></td><td>';
print '<input name="town" class="minwidth100" id="town" value="'.($conf->global->MAIN_INFO_ACCOUNTANT_TOWN ? $conf->global->MAIN_INFO_ACCOUNTANT_TOWN : GETPOST("town", 'nohtml')).'"></td></tr>'."\n";

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
//print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
//print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
print '</div>';
//print '<br>';

print '</form>';


llxFooter();

$db->close();
