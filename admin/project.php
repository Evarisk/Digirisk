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
require_once DOL_DOCUMENT_ROOT."/custom/digiriskdolibarr/class/digiriskelement.class.php";
require_once '../lib/digiriskdolibarr.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/class/digiriskresources.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'adminsocial'; // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

if (!$user->admin) accessforbidden();

$error = 0;
$hookmanager->initHooks(array('admincompany', 'globaladmin'));

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

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_PROJECT_LINKED", $projectLinked, 'integer', 0, '', $conf->entity);


	if ($action != 'updateedit' && !$error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$help_url = 'FR:Module_DigiriskDolibarr';
$page_name = "DigiriskdolibarrSetup";
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
dol_fiche_head($head, 'project', '', -1, "digiriskdolibarr@digiriskdolibarr");

$countrynotdefined = '<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';

print '<span class="opacitymedium">'.$langs->trans("DigiriskMenu")."</span><br>\n";
print "<br>\n";

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

/*
				*** Participation Agreement -- Accords de participation ***
*/

print '<table class="noborder centpercent editmode">';

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("ParticipationAgreement").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

// 				* Terms And Conditions - Modalités *

print '<tr class="oddeven"><td><label for="modalites">'.$langs->trans("TermsAndConditions").'</label></td><td>';
print '<textarea name="modalites" id="modalites" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE ? $conf->global->DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE : '').'</textarea></td></tr>'."\n";

/*
				*** Exceptions to working hours -- Dérogations aux horaires de travail ***
*/

print '<table class="noborder centpercent editmode">';

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("ExceptionsToWorkingHours").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

// 				* Permanent - Permanentes *

print '<tr class="oddeven"><td><label for="permanent">'.$langs->trans("PermanentDerogation").'</label></td><td>';
print '<textarea name="permanent" id="permanent" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_DEROGATION_SCHEDULE_PERMANENT ? $conf->global->DIGIRISK_DEROGATION_SCHEDULE_PERMANENT : '').'</textarea></td></tr>'."\n";

// 				* Permanent - Permanentes *

print '<tr class="oddeven"><td><label for="occasional">'.$langs->trans("OccasionalDerogation").'</label></td><td>';
print '<textarea name="occasional" id="occasional" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL ? $conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL : '').'</textarea></td></tr>'."\n";

/*
				*** ESC -- CSE ***
*/

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("ESC").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

// 				* ESC Election Date - Date d'élection du CSE *

print '<tr class="oddeven"><td><label for="ElectionDateCSE">'.$langs->trans("ElectionDate").'</label></td><td>';
print $form->selectDate(strtotime($electionDateCSE) ? $electionDateCSE : -1, 'ElectionDateCSE', 0, 0, 0, 'social_form', 1, 1);

// 				* ESC Titulars - Titulaires CSE *

$userlist 	  = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$titulars_cse = $allLinks['TitularsCSE'];

print '<tr>';
print '<td>'.$form->editfieldkey('Titulars', 'TitularsCSE_id', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

print $form->multiselectarray('TitularsCSE', $userlist, $titulars_cse->id, null, null, null, null, "90%");

print '</td></tr>';

// 				* ESC Alternates - Suppléants CSE *

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

// 				* Staff Representatives Titulars - Titulaires Délégués du Personnel *

$userlist 	  = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, 'AND u.statut = 1', 0, '', '', 0, 1);
$titulars_dp = $allLinks['TitularsDP'];

print '<tr>';
print '<td>'.$form->editfieldkey('Titulars', 'TitularsDP_id', '', $object, 0).'</td>';
print '<td colspan="3" class="maxwidthonsmartphone">';

print $form->multiselectarray('TitularsDP', $userlist, $titulars_dp->id, null, null, null, null, "90%");

print '</td></tr>';

// 				* Staff Representatives Suppléants - Suppléants Délégués du Personnel *

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
