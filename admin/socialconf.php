<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * \file    digiriskdolibarr/admin/socialconf.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr setup page for social data configuration.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

dol_include_once('/custom/digiriskdolibarr/class/digiriskresources.class.php');

global $conf, $db;

// Translations
$langs->loadLangs(array('admin', 'companies', "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'aZ09');
$error  = 0;

// Initialize technical objects
$resources = new DigiriskResources($db);

$allLinks = $resources->digirisk_dolibarr_fetch_resources();

$hookmanager->initHooks(array('admincompany', 'globaladmin'));

$electionDateCSE = dol_mktime(0, 0, 0, GETPOST('date_debutmonth', 'int'), GETPOST('date_debutday', 'int'), GETPOST('date_debutyear', 'int'));
$electionDateDP  = dol_mktime(0, 0, 0, GETPOST('date_finmonth', 'int'), GETPOST('date_finday', 'int'), GETPOST('date_finyear', 'int'));
$date            = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
	|| ($action == 'updateedit'))
{

	$electionDateCSE = GETPOST('ElectionDateCSE', 'none');
	$electionDateCSE = explode('/',$electionDateCSE);
	$electionDateCSE = $electionDateCSE[2] . '-' . $electionDateCSE[1] . '-' . $electionDateCSE[0];

	$electionDateDP = GETPOST('ElectionDateDP', 'none');
	$electionDateDP = explode('/',$electionDateDP);
	$electionDateDP = $electionDateDP[2] . '-' . $electionDateDP[1] . '-' . $electionDateDP[0];

	dolibarr_set_const($db, "DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE", GETPOST("modalites", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_DEROGATION_SCHEDULE_PERMANENT", GETPOST("permanent", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL", GETPOST("occasional", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_CSE_ELECTION_DATE", $electionDateCSE, 'date', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_DP_ELECTION_DATE", $electionDateDP, 'date', 0, '', $conf->entity);

	$CSEtitulaires 	= !empty(GETPOST('TitularsCSE', 'array')) ? GETPOST('TitularsCSE','array') : (GETPOST('TitularsCSE', 'int') > 0 ? GETPOST('TitularsCSE', 'int') : 0);
	$CSEsuppleants 	= !empty(GETPOST('AlternatesCSE', 'array')) ? GETPOST('AlternatesCSE','array') : (GETPOST('AlternatesCSE', 'int') > 0 ? GETPOST('AlternatesCSE', 'int') : 0);
	$TitularsDP 	= !empty(GETPOST('TitularsDP', 'array')) ? GETPOST('TitularsDP','array') : (GETPOST('TitularsDP', 'int') > 0 ? GETPOST('TitularsDP', 'int') : 0);
	$AlternatesDP 	= !empty(GETPOST('AlternatesDP', 'array')) ? GETPOST('AlternatesDP','array') : (GETPOST('AlternatesDP', 'int') > 0 ? GETPOST('AlternatesDP', 'int') : 0);

	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'TitularsCSE',  'societe', $CSEtitulaires, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'AlternatesCSE',  'societe', $CSEsuppleants, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'TitularsDP',  'societe', $TitularsDP, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'AlternatesDP',  'societe', $AlternatesDP, $conf->entity);

	if ($action != 'updateedit' && !$error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr';
llxHeader('', $langs->trans("CompanyFoundation"), $help_url);

print load_fiche_titre($langs->trans("CompanyFoundation"), '', 'title_setup');

$head = company_admin_prepare_head();

dol_fiche_head($head, 'social', $langs->trans("Company"), -1, 'company');

$form 		 = new Form($db);
$resources 	 = new DigiriskResources($db);

$allLinks = $resources->digirisk_dolibarr_fetch_resources();

$electionDateCSE 	= $conf->global->DIGIRISK_CSE_ELECTION_DATE;
$electionDateDP 	= $conf->global->DIGIRISK_DP_ELECTION_DATE;

print '<span class="opacitymedium">'.$langs->trans("DigiriskMenu")."</span><br>\n";
print "<br>\n";

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="social_form">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

/*
*** Participation Agreement -- Accords de participation ***
*/

print '<table class="noborder centpercent editmode">';

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("ParticipationAgreement").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

// * Terms And Conditions - Modalités *

print '<tr class="oddeven"><td><label for="modalites">'.$langs->trans("TermsAndConditions").'</label></td><td>';
print '<textarea name="modalites" id="modalites" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE ? $conf->global->DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE : '').'</textarea></td></tr>'."\n";

/*
*** Exceptions to working hours -- Dérogations aux horaires de travail ***
*/

print '<table class="noborder centpercent editmode">';

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("ExceptionsToWorkingHours").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

// * Permanent - Permanentes *

print '<tr class="oddeven"><td><label for="permanent">'.$langs->trans("PermanentDerogation").'</label></td><td>';
print '<textarea name="permanent" id="permanent" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_DEROGATION_SCHEDULE_PERMANENT ? $conf->global->DIGIRISK_DEROGATION_SCHEDULE_PERMANENT : '').'</textarea></td></tr>'."\n";

// * Permanent - Permanentes *

print '<tr class="oddeven"><td><label for="occasional">'.$langs->trans("OccasionalDerogation").'</label></td><td>';
print '<textarea name="occasional" id="occasional" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL ? $conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL : '').'</textarea></td></tr>'."\n";

/*
*** ESC -- CSE ***
*/

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("ESC").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

// * ESC Election Date - Date d'élection du CSE *

print '<tr class="oddeven"><td><label for="ElectionDateCSE">'.$langs->trans("ElectionDate").'</label></td><td>';
print $form->selectDate(strtotime($electionDateCSE) ? $electionDateCSE : -1, 'ElectionDateCSE', 0, 0, 0, 'social_form', 1, 1);

// * ESC Titulars - Titulaires CSE *

$userlist 	  = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$titulars_cse = $allLinks['TitularsCSE'];

print '<tr>';
print '<td>'.$form->editfieldkey('Titulars', 'TitularsCSE_id', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

print $form->multiselectarray('TitularsCSE', $userlist, $titulars_cse->id, null, null, null, null, "90%");

print '</td></tr>';

// * ESC Alternates - Suppléants CSE *

$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$alternates_cse = $allLinks['AlternatesCSE'];

print '<tr>';
print '<td>'.$form->editfieldkey('Alternates', 'AlternatesCSE_id', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

print $form->multiselectarray('AlternatesCSE', $userlist, $alternates_cse->id, null, null, null, null, "90%");

print '</td></tr>';

/*
*** Staff Representative -- Délégués du Personnel ***
*/

print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("StaffRepresentatives").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td><label for="ElectionDateDP">'.$langs->trans("ElectionDate").'</label></td><td>';
print $form->selectDate(strtotime($electionDateDP) ? $electionDateDP : -1, 'ElectionDateDP', 0, 0, 0, 'social_form', 1, 1);

// * Staff Representatives Titulars - Titulaires Délégués du Personnel *

$userlist 	  = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$titulars_dp = $allLinks['TitularsDP'];

print '<tr>';
print '<td>'.$form->editfieldkey('Titulars', 'TitularsDP_id', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

print $form->multiselectarray('TitularsDP', $userlist, $titulars_dp->id, null, null, null, null, "90%");

print '</td></tr>';

// * Staff Representatives Suppléants - Suppléants Délégués du Personnel *

$userlist 	  = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$alternates_dp = $allLinks['AlternatesDP'];

print '<tr>';
print '<td>'.$form->editfieldkey('Alternates', 'AlternatesDP', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

print $form->multiselectarray('AlternatesDP', $userlist, $alternates_dp->id, null, null, null, null, "90%");

print '</td></tr>';

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
print '</div>';
print '</form>';

llxFooter();
$db->close();
