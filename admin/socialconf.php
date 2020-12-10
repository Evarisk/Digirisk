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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/class/links.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/lib/digiriskdolibarr.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'adminsocial'; // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

if (!$user->admin) accessforbidden();

$error = 0;
$hookmanager->initHooks(array('admincompany', 'globaladmin'));

$date_cse = dol_mktime(0, 0, 0, GETPOST('date_debutmonth', 'int'), GETPOST('date_debutday', 'int'), GETPOST('date_debutyear', 'int'));
$date_dp = dol_mktime(0, 0, 0, GETPOST('date_finmonth', 'int'), GETPOST('date_finday', 'int'), GETPOST('date_finyear', 'int'));
$date = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
	|| ($action == 'updateedit'))
{

	$date_cse = GETPOST('dateelectionCSE', 'none');
	$date_cse = explode('/',$date_cse);
	$date_cse = $date_cse[2] . '-' . $date_cse[1] . '-' . $date_cse[0];

	$date_dp = GETPOST('dateelectionDP', 'none');
	$date_dp = explode('/',$date_dp);
	$date_dp = $date_dp[2] . '-' . $date_dp[1] . '-' . $date_dp[0];

	digirisk_dolibarr_set_const($db, "MODALITES_INFORMATIONS", GETPOST("modalites", 'none'), 'chaine', 0, '', $conf->entity);
	digirisk_dolibarr_set_const($db, "DATE_ELECTION_CSE", $date_cse, 'date', 0, '', $conf->entity);
	digirisk_dolibarr_set_const($db, "DATE_ELECTION_DP", $date_dp, 'date', 0, '', $conf->entity);

	$allLinks = digirisk_dolibarr_fetch_links($db, 'all');

	$CSEtitulaires		= GETPOST('titulairesCSE', 'array') ? GETPOST('titulairesCSE', 'array') : $allLinks['titulairesCSE']->fk_user ;
	$CSEsuppleants 		= GETPOST('suppleantsCSE', 'array') ? GETPOST('suppleantsCSE','array') : $allLinks['suppleantsCSE']->fk_user;
	$DPtitulaires 		= GETPOST('DPtitulaires', 'array') ? GETPOST('DPtitulaires', 'array') : $allLinks['DPtitulaires']->fk_user ;
	$DPsuppleants 		= GETPOST('DPsuppleants', 'array') ? GETPOST('DPsuppleants','array') : $allLinks['DPsuppleants']->fk_user;

	digirisk_dolibarr_set_links($db, 'titulairesCSE',  1,0,0, $CSEtitulaires, $conf->entity);
	digirisk_dolibarr_set_links($db, 'suppleantsCSE',  1, 0,0, $CSEsuppleants, $conf->entity);
	digirisk_dolibarr_set_links($db, 'DPtitulaires',  1, 0,0,$DPtitulaires, $conf->entity);
	digirisk_dolibarr_set_links($db, 'DPsuppleants',  1, 0,0,$DPsuppleants, $conf->entity);

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

$form 			= new Form($db);
$formother 		= new FormOther($db);
$formcompany 	= new FormCompany($db);
$digiriskconst 	= digirisk_dolibarr_fetch_const($db);


$date_cse 	= $digiriskconst->DATE_ELECTION_CSE;
$date_dp 	= $digiriskconst->DATE_ELECTION_DP;

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

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="social_form">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

//Accords de participation
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Accords de participation").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

print '<tr class="oddeven"><td><label for="modalites">'.$langs->trans("Modalities").'</label></td><td>';
print '<textarea name="modalites" id="modalites" class="minwidth300" rows="'.ROWS_3.'">'.($digiriskconst->MODALITES_INFORMATIONS ? $digiriskconst->MODALITES_INFORMATIONS : '').'</textarea></td></tr>'."\n";

//CSE
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("CSE").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

//Date
print '<tr class="oddeven"><td><label for="dateelectionCSE">'.$langs->trans("CSEElectionDate").'</label></td><td>';
print $form->selectDate(strtotime($date_cse) ? $date_cse : -1, 'dateelectionCSE', 0, 0, 0, 'social_form', 1, 1);

//Titulaires
print '<tr>';
print '<td>'.$form->editfieldkey('Titulaires', 'titulairesCSE_id', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$users_links = digirisk_dolibarr_fetch_links($db, 'titulairesCSE');
$selected = (count(GETPOST('titulairesCSE', 'array')) > 0 ? GETPOST('titulairesCSE', 'array') : (GETPOST('titulairesCSE', 'int') > 0 ? array(GETPOST('titulairesCSE', 'int')) : $users_links->fk_user ));
print $form->multiselectarray('titulairesCSE', $userlist, $selected, null, null, null, null, "90%");

print '</td></tr>';

//suppleants
print '<tr>';
print '<td>'.$form->editfieldkey('Suppléants', 'suppleantsCSE_id', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$users_links = digirisk_dolibarr_fetch_links($db, 'suppleantsCSE');
$selected = (count(GETPOST('suppleantsCSE', 'array')) > 0 ? GETPOST('suppleantsCSE', 'array') : (GETPOST('suppleantsCSE', 'int') > 0 ? array(GETPOST('suppleantsCSE', 'int')) : ($users_links->fk_user ? $users_links->fk_user : 0) ));
print $form->multiselectarray('suppleantsCSE', $userlist, $selected, null, null, null, null, "90%");

print '</td></tr>';


//Délégués du personnel
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Délégués du personnel").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";
//Date
print '<tr class="oddeven"><td><label for="dateelectionDP">'.$langs->trans("DPElectionDate").'</label></td><td>';
print $form->selectDate(strtotime($date_dp) ? $date_dp : -1, 'dateelectionDP', 0, 0, 0, 'social_form', 1, 1);

//Titulaires
print '<tr>';
print '<td>'.$form->editfieldkey('Titulaires', 'DPtitulaires_id', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$users_links = digirisk_dolibarr_fetch_links($db, 'DPtitulaires');
$selected = (count(GETPOST('DPtitulaires', 'array')) > 0 ? GETPOST('DPtitulaires', 'array') : (GETPOST('DPtitulaires', 'int') > 0 ? array(GETPOST('DPtitulaires', 'int')) : $users_links->fk_user ));
print $form->multiselectarray('DPtitulaires', $userlist, $selected, null, null, null, null, "90%");

print '</td></tr>';

//suppleants
print '<tr>';
print '<td>'.$form->editfieldkey('Suppléants', 'DPsuppleants', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$users_links = digirisk_dolibarr_fetch_links($db, 'DPsuppleants');
$selected = (count(GETPOST('DPsuppleants', 'array')) > 0 ? GETPOST('DPsuppleants', 'array') : (GETPOST('DPsuppleants', 'int') > 0 ? array(GETPOST('DPsuppleants', 'int')) : ($users_links->fk_user ? $users_links->fk_user : 0) ));
print $form->multiselectarray('DPsuppleants', $userlist, $selected, null, null, null, null, "90%");

print '</td></tr>';

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
