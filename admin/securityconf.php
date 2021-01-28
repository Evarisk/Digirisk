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

require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/class/digiriskresources.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'adminsecurity'; // To manage different context of search

$id = GETPOST('id', 'int');

$origin = GETPOST('origin', 'alpha');
$originid = GETPOST('originid', 'int');
$confirm = GETPOST('confirm', 'alpha');

$fulldayevent = GETPOST('fullday');

$aphour = GETPOST('aphour');
$apmin = GETPOST('apmin');
$p2hour = GETPOST('p2hour');
$p2min = GETPOST('p2min');

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

if (!$user->admin) accessforbidden();

global $conf, $db;

$contact = new Contact($db);

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

	$resources = new DigiriskResources($db);
	$allLinks = $resources->digirisk_dolibarr_fetch_resources();


	$labourdoctor_id[0] 					= GETPOST('labourdoctor_socid', 'int') > 0 ? GETPOST('labourdoctor_socid', 'int') : 0 ;
	$labourinspector_id[0]					= GETPOST('labourinspector_socid', 'int') > 0 ? GETPOST('labourinspector_socid','int') : 0;

	$labourdoctor_socpeopleassigned 	= !empty(GETPOST('labourdoctor_contactid', 'array')) ? GETPOST('labourdoctor_contactid', 'array') : (GETPOST('labourdoctor_contactid', 'int') > 0 ? GETPOST('labourdoctor_contactid', 'int') : 0);
	$labourinspector_socpeopleassigned 	= !empty(GETPOST('labourinspector_contactid', 'array')) ? GETPOST('labourinspector_contactid','array') : (GETPOST('labourinspector_contactid', 'int') > 0 ? GETPOST('labourinspector_contactid', 'int') : 0);

	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'LabourDoctorSociety',  'societe', $labourdoctor_id, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'LabourInspectorSociety',  'societe', $labourinspector_id, $conf->entity);

	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'LabourDoctorContact',  'socpeople', $labourdoctor_socpeopleassigned, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'LabourInspectorContact',  'socpeople', $labourinspector_socpeopleassigned, $conf->entity);

	$samu_id[0]		 		= GETPOST('samu_socid', 'int') ? GETPOST('samu_socid', 'int') : $allLinks['SAMU']->id[0];
	$pompiers_id[0] 		= GETPOST('pompiers_socid', 'int') ? GETPOST('pompiers_socid','int') : $allLinks['Pompiers']->id[0];
	$police_id[0] 			= GETPOST('police_socid', 'int') ? GETPOST('police_socid', 'int') : $allLinks['Police']->id[0] ;
	$touteurgence_id[0] 	= GETPOST('touteurgence_socid', 'int') ? GETPOST('touteurgence_socid','int') : $allLinks['AllEmergencies']->id[0];
	$defenseur_id[0] 		= GETPOST('defenseur_socid', 'int') ? GETPOST('defenseur_socid', 'int') : $allLinks['RightsDefender']->id[0] ;
	$antipoison_id[0] 		= GETPOST('antipoison_socid', 'int') ? GETPOST('antipoison_socid','int') : $allLinks['Antipoison']->id[0];
	$responsible_id[0]		= GETPOST('responsible_socid', 'int') ? GETPOST('responsible_socid','int') : $allLinks['Responsible']->id[0];

	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'SAMU',  'societe', $samu_id, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'Pompiers',  'societe', $pompiers_id, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'Police',  'societe', $police_id, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'AllEmergencies',  'societe', $touteurgence_id, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'RightsDefender',  'societe', $defenseur_id, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'Antipoison',  'societe', $antipoison_id, $conf->entity);
	$resources->digirisk_dolibarr_set_resources($db,$user->id,  'Responsible',  'societe', $responsible_id, $conf->entity);

	dolibarr_set_const($db, "DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION", GETPOST("emplacementCD", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_SOCIETY_DESCRIPTION", GETPOST("description", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_GENERAL_MEANS", GETPOST("moyensgeneraux", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_GENERAL_RULES", GETPOST("consignesgenerales", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_RULES_LOCATION", GETPOST("emplacementRI", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_DUER_LOCATION", GETPOST("emplacementDU", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION", GETPOST("emplacementCC", 'none'), 'chaine', 0, '', $conf->entity);

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

dol_fiche_head($head, 'security', $langs->trans("Company"), -1, 'company');

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);
$resources = new DigiriskResources($db);
$allLinks = $resources->digirisk_dolibarr_fetch_resources();


$countrynotdefined = '<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';
print '<span class="opacitymedium">'.$langs->trans("DigiriskMenu")."</span><br>\n";
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

print '<table class="noborder centpercent editmode">';

if ($conf->societe->enabled)
{
	/*
				*** Labour Doctor -- Médecin du travail ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("LabourDoctor").'</th><th>.<i class="fas fa-briefcase-medical"></i></th></tr>'."\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';

	$labour_doctor_society = $allLinks['LabourDoctorSociety'];

	// 			* Third party concerned - Tiers concerné *

	if ($labour_doctor_society->ref == 'LabourDoctorSociety')
	{
		$events 	= array();
		$events[] 	= array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourdoctor_contactid', 'params' => array('add-customer-contact' => 'disabled'));
		$societe 	= new Societe($db);
		$societe->fetch($labour_doctor_society->id);

		print $form->select_company($labour_doctor_society->id, 'labourdoctor_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	}
	else
	{
		$events 	= array();
		$events[] 	= array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourdoctor_contactid', 'params' => array('add-customer-contact' => 'disabled'));

		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'labourdoctor_socid', '', 1, 1, 0, $events, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'labourdoctor_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	//			* Related contacts - Contacts associés *

	print '<tr class="oddeven"><td class="nowrap">'.$langs->trans("ActionOnContact").'</td><td>';

	$labour_doctor_contact 		= $allLinks['LabourDoctorContact'];
	$labourdoctorpreselectedids = $labour_doctor_contact->id;

	if ($labour_doctor_contact->id) {
		print $form->selectcontacts($labour_doctor_society->id[0], $labour_doctor_contact->id, 'labourdoctor_contactid[]', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourdoctor_contactid');
	}
	else
	{
		$labourdoctorpreselectedids = GETPOST('labourdoctor_contactid', 'array');
		if (GETPOST('labourdoctor_contactid', 'array')) $labourdoctorpreselectedids[GETPOST('labourdoctor_contactid', 'array')] = GETPOST('labourdoctor_contactid', 'array');
		print $form->selectcontacts(GETPOST('labourdoctor_socid', 'int'), $labourdoctorpreselectedids, 'labourdoctor_contactid[]', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourdoctor_contactid');
	}
	print '</td></tr>';

	/*
				*** Labour Inspector -- Inspecteur du travail ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("LabourInspector").'</th><th>.<i class="fas fa-search"></i></th></tr>'."\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';

	$labour_inspector_societe = $allLinks['LabourInspectorSociety'];

	// 			* Third party concerned - Tiers concerné *

	if ($labour_inspector_societe->ref == 'LabourInspectorSociety')
	{
		$events 	= array();
		$events[] 	= array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourinspector_contactid', 'params' => array('add-customer-contact' => 'disabled'));
		$societe 	= new Societe($db);
		$societe->fetch($labour_inspector_societe->id[0]);

		print $form->select_company($labour_inspector_societe->id[0], 'labourinspector_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	}
	else
	{
		$events = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourinspector_contactid', 'params' => array('add-customer-contact' => 'disabled'));
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'labourinspector_socid', '', 1, 1, 0, $events, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'labourinspector_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	//			* Related contacts - Contacts associés *

	print '<tr class="oddeven"><td class="nowrap">'.$langs->trans("ActionOnContact").'</td><td>';

	$labour_inspector_contact 	=  $allLinks['LabourInspectorContact'];
	$preselectedids 			= $labour_inspector_contact->id;

	if ($labour_inspector_contact->id) {
		print $form->selectcontacts($labour_inspector_societe->id[0], $labour_inspector_contact->id , 'labourinspector_contactid[]', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourinspector_contactid');
	}
	else
	{
		$preselectedids = GETPOST('labourinspector_contactid', 'array');
		if (GETPOST('labourinspector_contactid', 'array')) $preselectedids[GETPOST('labourinspector_contactid', 'array')] = GETPOST('labourinspector_contactid', 'array');
		print $form->selectcontacts(GETPOST('labourinspector_socid', 'int'), $preselectedids, 'labourinspector_contactid[]', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourinspector_contactid');
	}
	print '</td></tr>';

	/*
				*** Emergencies -- SAMU ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("SAMU").'</th><th>.<i class="fas fa-hospital-alt"></i></th></tr>'."\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';

	$samu_resources =  $allLinks['SAMU'];

	// 			* Third party concerned - Tiers concerné *

	if ($samu_resources->ref == 'SAMU')
	{
		$societe = new Societe($db);
		$societe->fetch($samu_resources->id[0]);

		print $form->select_company($samu_resources->id[0], 'samu_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'samu_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'samu_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	/*
				*** Fire Brigade -- Pompiers ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("FireBrigade").'</th><th>.<i class="fas fa-ambulance"></i></th></tr>'."\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';

	$pompiers_resources = $allLinks['Pompiers'];

	// 			* Third party concerned - Tiers concerné *

	if ($pompiers_resources->ref == 'Pompiers')
	{
		$societe = new Societe($db);
		$societe->fetch($pompiers_resources->id[0]);
		print $form->select_company($pompiers_resources->id[0], 'pompiers_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'pompiers_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'pompiers_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	/*
				*** Police -- Police ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Police").'</th><th>.<i class="fas fa-car"></i></th></tr>'."\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';

	$police_resources = $allLinks['Police'];

	// 			* Third party concerned - Tiers concerné *

	if ($police_resources->ref == 'Police')
	{
		$societe = new Societe($db);
		$societe->fetch($police_resources->id[0]);
		print $form->select_company($police_resources->id[0], 'police_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'police_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'police_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	/*
				*** For any emergency -- Pour toute urgence ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("AllEmergencies").'</th><th>.<i class="fas fa-phone"></i></th></tr>'."\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';

	$touteurgence_resources = $allLinks['AllEmergencies'];

	// 			* Third party concerned - Tiers concerné *

	if ($touteurgence_resources->ref == 'AllEmergencies')
	{
		$societe = new Societe($db);
		$societe->fetch($touteurgence_resources->id[0]);
		print $form->select_company($touteurgence_resources->id[0], 'touteurgence_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'touteurgence_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'touteurgence_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	/*
				*** Rights defender -- Défenseur des droits ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("RightsDefender").'</th><th>.<i class="fas fa-gavel"></i></th></tr>'."\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';

	$defenseur_resources = $allLinks['RightsDefender'];

	// 			* Third party concerned - Tiers concerné *

	if ($defenseur_resources->ref == 'RightsDefender')
	{
		$societe = new Societe($db);
		$societe->fetch($defenseur_resources->id[0]);
		print $form->select_company($defenseur_resources->id[0], 'defenseur_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'defenseur_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'defenseur_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	/*
				*** Poison control center -- Centre antipoison ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("PoisonControlCenter").'</th><th>.<i class="fas fa-skull-crossbones"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	$antipoison_resources = $allLinks['Antipoison'];

	// 			* Third party concerned - Tiers concerné *

	if ($antipoison_resources->ref == 'Antipoison')
	{
		$societe = new Societe($db);
		$societe->fetch($antipoison_resources->id[0]);
		print $form->select_company($antipoison_resources->id[0], 'antipoison_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'antipoison_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'antipoison_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';
}

/*
			*** Safety instructions -- Consignes de sécurité ***
*/

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("SafetyInstructions").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

// 			* Responsible to notify - Responsable à prévenir *

print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ResponsibleToNotify").'</td><td>';
$responsible_resources = $allLinks['Responsible'];

// 			* Third party concerned - Tiers concerné *

if ($responsible_resources->ref == 'Responsible' && $responsible_resources->id[0] > 0)
{
	$user = new User($db);
	$user->fetch($responsible_resources->id[0]);

	print $form->select_dolusers($responsible_resources->id[0], 'responsible_socid', 0, null, 0, 0, 0, 0, 'minwidth300');

	// 			* Phone number - Numéro de téléphone *

	print '<tr class="oddeven"><td><label for="name">'.$langs->trans("Phone").'</label></td><td>';
	print $user->office_phone;
	print '</td></tr>';

}
else //id = 0
{
	//For external user force the company to user company
	if (!empty($user->socid)) {
		print $form->select_dolusers($user->socid, 'responsible_socid', '', 1, 0, 0, 0, 0, 'minwidth300');
	} else {
		print $form->select_dolusers('', 'responsible_socid', '', '', 0, 0, 0 , 0, 'minwidth300');
	}
}
print '</td></tr>';

// 			* Location of detailed instructions - Emplacement de la consigne détaillée *

print '<tr class="oddeven"><td><label for="emplacementCD">'.$langs->trans("LocationOfDetailedInstructions").'</label></td><td>';
print '<textarea name="emplacementCD" id="emplacementCD" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION ? $conf->global->DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION : '').'</textarea></td></tr>'."\n";

/*
			*** Society additional details -- Informations complémentaires de la société ***
*/

print '<tr class="liste_titre"><th class="titlefield">'.$langs->trans("SocietyAdditionalDetails").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

// 			* Description - Emplacement de la consigne détaillée *

print '<tr class="oddeven"><td><label for="description">'.$langs->trans("Description").'</label></td><td>';
print '<textarea name="description" id="description" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_SOCIETY_DESCRIPTION ? $conf->global->DIGIRISK_SOCIETY_DESCRIPTION : '').'</textarea></td></tr>'."\n";

// 			* General means at disposal - Moyens généraux mis à disposition *

print '<tr class="oddeven"><td><label for="moyensgeneraux">'.$langs->trans("GeneralMeansAtDisposal").'</label></td><td>';
print '<textarea name="moyensgeneraux" id="moyensgeneraux" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_GENERAL_MEANS ? $conf->global->DIGIRISK_GENERAL_MEANS : '').'</textarea></td></tr>'."\n";

// 			* General instructions - Consignes générales *

print '<tr class="oddeven"><td><label for="consignesgenerales">'.$langs->trans("GeneralInstructions").'</label></td><td>';
print '<textarea name="consignesgenerales" id="consignesgenerales" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_GENERAL_RULES ? $conf->global->DIGIRISK_GENERAL_RULES : '').'</textarea></td></tr>'."\n";

// 			* Rules of procedure - Règlement intérieur *

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("RulesOfProcedure").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

// 			* Rules of procedure location - Emplacement du règlement intérieur *

print '<tr class="oddeven"><td><label for="emplacementRI">'.$langs->trans("Location").'</label></td><td>';
print '<textarea name="emplacementRI" id="emplacementRI" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_RULES_LOCATION ? $conf->global->DIGIRISK_RULES_LOCATION : '').'</textarea></td></tr>'."\n";

// 			* Risks evaluation - Document Unique *

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("RisksEvaluation").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

// 			* Risks evaluation location - Emplacement du Document Unique *

print '<tr class="oddeven"><td><label for="emplacementDU">'.$langs->trans("Location").'</label></td><td>';
print '<textarea name="emplacementDU" id="emplacementDU" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_DUER_LOCATION ? $conf->global->DIGIRISK_DUER_LOCATION : '').'</textarea></td></tr>'."\n";


// 			* Collective Agreement - Convention collective *

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("CollectiveAgreement").'</th><th>'.$langs->trans("").'</th></tr>'."\n";

// 			* Collective Agreement location - Emplacement de la Convention collective *

print '<tr class="oddeven"><td><label for="emplacementCC">'.$langs->trans("Location").'</label></td><td>';
print '<textarea name="emplacementCC" id="emplacementCC" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION ? $conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION : '').'</textarea></td></tr>'."\n";

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
print '</div>';
print '</form>';

llxFooter();
$db->close();
