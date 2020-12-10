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
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'adminsecurity'; // To manage different context of search

$labourdoctor_id 				= GETPOST('labourdoctor_socid', 'int');
$labourdoctor_contactid 		= GETPOST('labourdoctor_contactid', 'int');
$labourdoctor_socpeopleassigned = GETPOST('labourdoctor_socpeopleassigned', 'array');

$labourinspector_id					= GETPOST('labourinspector_socid', 'int');
$labourinspector_contactid 			= GETPOST('labourinspector_contactid', 'int');
$labourinspector_socpeopleassigned 	= GETPOST('labourinspector_socpeopleassigned', 'array');

$samu_id		 	= GETPOST('samu_socid', 'int');
$pompiers_id 		= GETPOST('pompiers_socid', 'int');
$police_id 			= GETPOST('police_socid', 'int');
$touteurgence_id 	= GETPOST('touteurgence_socid', 'int');
$defenseur_id 		= GETPOST('defenseur_socid', 'int');
$antipoison_id 		= GETPOST('antipoison_socid', 'int');
$responsible_id 	= GETPOST('responsible_socid', 'int');

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

$contact = new Contact($db);


if (!$user->admin) accessforbidden();

$error = 0;
$hookmanager->initHooks(array('admincompany', 'globaladmin'));


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

	$labourdoctor_id 					= GETPOST('labourdoctor_socid', 'int') ? GETPOST('labourdoctor_socid', 'int') : $allLinks['LabourDoctor']->fk_soc ;
	$labourdoctor_socpeopleassigned 	= !empty(GETPOST('labourdoctor_socpeopleassigned', 'array')) ? GETPOST('labourdoctor_socpeopleassigned', 'array') : (GETPOST('labourdoctor_contactid', 'int') ? GETPOST('labourdoctor_contactid', 'int') : $allLinks['LabourDoctor']->fk_contact);

	$labourinspector_id					= GETPOST('labourinspector_socid', 'int') ? GETPOST('labourinspector_socid','int') : $allLinks['LabourInspector']->fk_soc;
	$labourinspector_socpeopleassigned 	= !empty(GETPOST('labourinspector_contactid', 'int')) ? GETPOST('labourinspector_contactid','int') : (GETPOST('labourinspector_contactid', 'int') ? GETPOST('labourinspector_contactid', 'int') : $allLinks['LabourInspector']->fk_contact);

	digirisk_dolibarr_set_links($db, 'LabourDoctor',  1, $labourdoctor_id,$labourdoctor_socpeopleassigned, $conf->entity);
	digirisk_dolibarr_set_links($db, 'LabourInspector',  1, $labourinspector_id,$labourinspector_socpeopleassigned, $conf->entity);

	$samu_id		 	= GETPOST('samu_socid', 'int') ? GETPOST('samu_socid', 'int') : $allLinks['SAMU']->fk_soc ;
	$pompiers_id 		= GETPOST('pompiers_socid', 'int') ? GETPOST('pompiers_socid','int') : $allLinks['Pompiers']->fk_soc;
	$police_id 			= GETPOST('police_socid', 'int') ? GETPOST('police_socid', 'int') : $allLinks['Police']->fk_soc ;
	$touteurgence_id 	= GETPOST('touteurgence_socid', 'int') ? GETPOST('touteurgence_socid','int') : $allLinks['AllEmergencies']->fk_soc;
	$defenseur_id 		= GETPOST('defenseur_socid', 'int') ? GETPOST('defenseur_socid', 'int') : $allLinks['RightsDefender']->fk_soc ;
	$antipoison_id 		= GETPOST('antipoison_socid', 'int') ? GETPOST('antipoison_socid','int') : $allLinks['Antipoison']->fk_soc;
	$responsible_id 	= GETPOST('responsible_socid', 'int') ? GETPOST('responsible_socid','int') : $allLinks['Responsible']->fk_soc;

	digirisk_dolibarr_set_links($db, 'SAMU',  1, $samu_id,0, 0, $conf->entity);
	digirisk_dolibarr_set_links($db, 'Pompiers',  1, $pompiers_id,0,0, $conf->entity);
	digirisk_dolibarr_set_links($db, 'Police',  1, $police_id,0,0, $conf->entity);
	digirisk_dolibarr_set_links($db, 'AllEmergencies',  1, $touteurgence_id,0, 0, $conf->entity);
	digirisk_dolibarr_set_links($db, 'RightsDefender',  1, $defenseur_id,0,0, $conf->entity);
	digirisk_dolibarr_set_links($db, 'Antipoison',  1, $antipoison_id,0, 0,$conf->entity);
	digirisk_dolibarr_set_links($db, 'Responsible',  1, $responsible_id,0, 0,$conf->entity);

	digirisk_dolibarr_set_const($db, "DETAILED_RULES_LOCATION", GETPOST("emplacementCD", 'none'), 'chaine', 0, '', $conf->entity);
	digirisk_dolibarr_set_const($db, "DESCRIPTION", GETPOST("description", 'none'), 'chaine', 0, '', $conf->entity);
	digirisk_dolibarr_set_const($db, "MOYENS_GENERAUX", GETPOST("moyensgeneraux", 'none'), 'chaine', 0, '', $conf->entity);
	digirisk_dolibarr_set_const($db, "CONSIGNES_GENERALES", GETPOST("consignesgenerales", 'none'), 'chaine', 0, '', $conf->entity);
	digirisk_dolibarr_set_const($db, "REGLEMENT_INTERIEUR_EMPLACEMENT", GETPOST("emplacementRI", 'none'), 'chaine', 0, '', $conf->entity);
	digirisk_dolibarr_set_const($db, "DOCUMENT_UNIQUE_EMPLACEMENT", GETPOST("emplacementDU", 'none'), 'chaine', 0, '', $conf->entity);

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

dol_fiche_head($head, 'security', $langs->trans("Company"), -1, 'company');

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);
$object = new DigiriskLink($db);
$digiriskconst = digirisk_dolibarr_fetch_const($db);

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

print '<table class="noborder centpercent editmode">';

if ($conf->societe->enabled)
{
	// MEDECIN DU TRAVAIL
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("LabourDoctor").'</th><th>.<i class="fas fa-briefcase-medical"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	$labourdoctor_links = digirisk_dolibarr_fetch_links($db, 'LabourDoctor');

	// Tiers
	if ($labourdoctor_links->ref == 'LabourDoctor')
	{
		$events = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourdoctor_contactid', 'params' => array('add-customer-contact' => 'disabled'));

		$societe = new Societe($db);
		$societe->fetch($labourdoctor_links->fk_soc);
		print $form->select_company($labourdoctor_links->fk_soc, 'labourdoctor_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');

	}
	else
	{
		$events = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourdoctor_contactid', 'params' => array('add-customer-contact' => 'disabled'));
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'labourdoctor_socid', '', 1, 1, 0, $events, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'labourdoctor_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	// Related contact
	print '<tr class="oddeven"><td class="nowrap">'.$langs->trans("ActionOnContact").'</td><td>';
	$labourdoctorpreselectedids = $labourdoctor_links->fk_contact;

	if ($labourdoctor_links->fk_contact) {
		print $form->selectcontacts($labourdoctor_links->fk_soc, $labourdoctor_links->fk_contact, 'labourdoctor_contactid', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourdoctor_contactid');
	}
	else
	{
		$labourdoctorpreselectedids = GETPOST('labourdoctor_contactid', 'int');
		if (GETPOST('labourdoctor_contactid', 'int')) $labourdoctorpreselectedids[GETPOST('labourdoctor_contactid', 'int')] = GETPOST('labourdoctor_contactid', 'int');
		print $form->selectcontacts(GETPOST('labourdoctor_socid', 'int'), $labourdoctorpreselectedids, 'labourdoctor_contactid', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourdoctor_contactid');
	}
	print '</td></tr>';

	// INSPECTEUR DU TRAVAIL
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("LabourInspector").'</th><th>.<i class="fas fa-search"></i></th></tr>'."\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	$labourinspector_links = digirisk_dolibarr_fetch_links($db, 'LabourInspector');

	// Tiers
	if ($labourinspector_links->ref == 'LabourInspector')
	{
		$events = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourinspector_contactid', 'params' => array('add-customer-contact' => 'disabled'));

		$societe = new Societe($db);
		$societe->fetch($labourinspector_links->fk_soc);
		print $form->select_company($labourinspector_links->fk_soc, 'labourinspector_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');

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

	// Related contacts
	print '<tr class="oddeven"><td class="nowrap">'.$langs->trans("ActionOnContact").'</td><td>';
	$preselectedids = $labourinspector_links->fk_contact;
	if ($labourinspector_links->fk_contact) {
		print $form->selectcontacts($labourinspector_links->fk_soc, $labourinspector_links->fk_contact , 'labourinspector_contactid', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourinspector_contactid');
	}
	else
	{
		$preselectedids = GETPOST('labourinspector_contactid', 'int');
		if (GETPOST('labourinspector_contactid', 'int')) $preselectedids[GETPOST('labourinspector_contactid', 'int')] = GETPOST('labourinspector_contactid', 'int');
		print $form->selectcontacts(GETPOST('labourinspector_socid', 'int'), $preselectedids, 'labourinspector_contactid', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourinspector_contactid');
	}
	print '</td></tr>';

	// SAMU
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("SAMU").'</th><th>.<i class="fas fa-hospital-alt"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	$samu_links = digirisk_dolibarr_fetch_links($db, 'SAMU');

	// Tiers
	if ($samu_links->ref == 'SAMU')
	{
		$societe = new Societe($db);
		$societe->fetch($samu_links->fk_soc);
		print $form->select_company($samu_links->fk_soc, 'samu_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

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

	// Pompiers
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Pompiers").'</th><th>.<i class="fas fa-ambulance"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	$pompiers_links = digirisk_dolibarr_fetch_links($db, 'Pompiers');

	// Tiers
	if ($pompiers_links->ref == 'Pompiers')
	{
		$societe = new Societe($db);
		$societe->fetch($pompiers_links->fk_soc);
		print $form->select_company($pompiers_links->fk_soc, 'pompiers_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

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

	// Police
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Police").'</th><th>.<i class="fas fa-car"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	$police_links = digirisk_dolibarr_fetch_links($db, 'Police');

	// Tiers
	if ($police_links->ref == 'Police')
	{
		$societe = new Societe($db);
		$societe->fetch($police_links->fk_soc);
		print $form->select_company($police_links->fk_soc, 'police_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

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

	// Toute Urgence
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("AllEmergencies").'</th><th>.<i class="fas fa-phone"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	$touteurgence_links = digirisk_dolibarr_fetch_links($db, 'AllEmergencies');

	// Tiers
	if ($touteurgence_links->ref == 'AllEmergencies')
	{
		$societe = new Societe($db);
		$societe->fetch($touteurgence_links->fk_soc);
		print $form->select_company($touteurgence_links->fk_soc, 'touteurgence_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

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

	// Défenseur des droits
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("RightsDefender").'</th><th>.<i class="fas fa-gavel"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	$defenseur_links = digirisk_dolibarr_fetch_links($db, 'RightsDefender');

	// Tiers
	if ($defenseur_links->ref == 'RightsDefender')
	{
		$societe = new Societe($db);
		$societe->fetch($defenseur_links->fk_soc);
		print $form->select_company($defenseur_links->fk_soc, 'defenseur_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

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

	// Antipoison
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Antipoison").'</th><th>.<i class="fas fa-skull-crossbones"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	$antipoison_links = digirisk_dolibarr_fetch_links($db, 'Antipoison');

	// Tiers
	if ($antipoison_links->ref == 'Antipoison')
	{
		$societe = new Societe($db);
		$societe->fetch($antipoison_links->fk_soc);
		print $form->select_company($antipoison_links->fk_soc, 'antipoison_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

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

// Consignes de sécurité
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Consignes de sécurité").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

// Responsable à prévenir

print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("Responsable à prévenir").'</td><td>';
$responsible_links = digirisk_dolibarr_fetch_links($db, 'Responsible');

// Tiers
if ($responsible_links->ref == 'Responsible' && $responsible_links->fk_soc > 0)
{
	$societe = new Societe($db);
	$societe->fetch($responsible_links->fk_soc);

	print $form->select_company($responsible_links->fk_soc, 'responsible_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
// Téléphone
	print '<tr class="oddeven"><td><label for="name">'.$langs->trans("Téléphone").'</label></td><td>';
	print $societe->phone;
	print '</td></tr>';

}
else
{
	//For external user force the company to user company
	if (!empty($user->socid)) {
		print $form->select_company($user->socid, 'responsible_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
	} else {
		print $form->select_company('', 'responsible_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
	}
}
print '</td></tr>';

// Emplacement de la consigne détaillée
print '<tr class="oddeven"><td><label for="emplacementCD">'.$langs->trans("Emplacement de la consigne détaillée").'</label></td><td>';
print '<textarea name="emplacementCD" id="emplacementCD" class="minwidth300" rows="'.ROWS_3.'">'.($digiriskconst->DETAILED_RULES_LOCATION ? $digiriskconst->DETAILED_RULES_LOCATION : '').'</textarea></td></tr>'."\n";

print '<tr class="liste_titre"><th class="titlefield">'.$langs->trans("Informations complémentaires de la société").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

// Description
print '<tr class="oddeven"><td><label for="description">'.$langs->trans("Description").'</label></td><td>';
print '<textarea name="description" id="description" class="minwidth300" rows="'.ROWS_3.'">'.($digiriskconst->DESCRIPTION ? $digiriskconst->DESCRIPTION : '').'</textarea></td></tr>'."\n";

// Moyens généraux mis à disposition
print '<tr class="oddeven"><td><label for="moyensgeneraux">'.$langs->trans("Moyens généraux mis à disposition").'</label></td><td>';
print '<textarea name="moyensgeneraux" id="moyensgeneraux" class="minwidth300" rows="'.ROWS_3.'">'.($digiriskconst->MOYENS_GENERAUX ? $digiriskconst->MOYENS_GENERAUX : '').'</textarea></td></tr>'."\n";

// Consignes générales
print '<tr class="oddeven"><td><label for="consignesgenerales">'.$langs->trans(" Consignes générales").'</label></td><td>';
print '<textarea name="consignesgenerales" id="consignesgenerales" class="minwidth300" rows="'.ROWS_3.'">'.($digiriskconst->CONSIGNES_GENERALES ? $digiriskconst->CONSIGNES_GENERALES : '').'</textarea></td></tr>'."\n";

// RI
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Règlement intérieur").'</th><th>'.$langs->trans("").'</th></tr>'."\n";
// Emplacement
print '<tr class="oddeven"><td><label for="emplacementRI">'.$langs->trans("Emplacement").'</label></td><td>';
print '<textarea name="emplacementRI" id="emplacementRI" class="minwidth300" rows="'.ROWS_3.'">'.($digiriskconst->REGLEMENT_INTERIEUR_EMPLACEMENT ? $digiriskconst->REGLEMENT_INTERIEUR_EMPLACEMENT : '').'</textarea></td></tr>'."\n";

// DU
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Document Unique").'</th><th>'.$langs->trans("").'</th></tr>'."\n";
// Emplacement
print '<tr class="oddeven"><td><label for="emplacementDU">'.$langs->trans("Emplacement").'</label></td><td>';
print '<textarea name="emplacementDU" id="emplacementDU" class="minwidth300" rows="'.ROWS_3.'">'.($digiriskconst->DOCUMENT_UNIQUE_EMPLACEMENT ? $digiriskconst->DOCUMENT_UNIQUE_EMPLACEMENT : '').'</textarea></td></tr>'."\n";

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
