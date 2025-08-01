<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    admin/securityconf.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr setup page for security data configuration.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $langs, $user, $hookmanager;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

require_once __DIR__ . '/../class/digiriskresources.class.php';

// Translations
saturne_load_langs(["admin", "companies"]);

// Parameters
$action = GETPOST('action', 'aZ09');
$error  = 0;

// Initialize technical objects
$contact   = new Contact($db);
$societe   = new Societe($db);
$usertmp   = new User($db);
$resources = new DigiriskResources($db);

$allLinks = $resources->fetchDigiriskResources();

$hookmanager->initHooks(array('admincompany', 'globaladmin'));

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$labourDoctorId[0]    = GETPOST('labourdoctor_socid', 'int') > 0 ? GETPOST('labourdoctor_socid', 'int') : 0 ;
	$labourInspectorId[0] = GETPOST('labourinspector_socid', 'int') > 0 ? GETPOST('labourinspector_socid', 'int') : 0;

	$labourDoctorSocpeopleAssigned    = ! empty(GETPOST('labourdoctor_contactid', 'array')) ? GETPOST('labourdoctor_contactid', 'array') : (GETPOST('labourdoctor_contactid', 'int') > 0 ? array(GETPOST('labourdoctor_contactid', 'int')) : array());
	$labourInspectorSocpeopleAssigned = ! empty(GETPOST('labourinspector_contactid', 'array')) ? GETPOST('labourinspector_contactid', 'array') : (GETPOST('labourinspector_contactid', 'int') > 0 ? array(GETPOST('labourinspector_contactid', 'int')) :  array());

	$resources->setDigiriskResources($db, $user->id,  'LabourDoctorSociety',  'societe', $labourDoctorId, $conf->entity);
	$resources->setDigiriskResources($db, $user->id,  'LabourInspectorSociety',  'societe', $labourInspectorId, $conf->entity);

	$resources->setDigiriskResources($db, $user->id,  'LabourDoctorContact',  'socpeople', $labourDoctorSocpeopleAssigned, $conf->entity);
	$resources->setDigiriskResources($db, $user->id,  'LabourInspectorContact',  'socpeople', $labourInspectorSocpeopleAssigned, $conf->entity);

	$samuId[0]          = GETPOST('samu_socid', 'int') ? GETPOST('samu_socid', 'int') : $allLinks['SAMU']->id[0];
	$pompiersId[0]      = GETPOST('pompiers_socid', 'int') ? GETPOST('pompiers_socid', 'int') : $allLinks['Pompiers']->id[0];
	$policeId[0]        = GETPOST('police_socid', 'int') ? GETPOST('police_socid', 'int') : $allLinks['Police']->id[0] ;
	$touteUrgenceId[0]  = GETPOST('touteurgence_socid', 'int') ? GETPOST('touteurgence_socid', 'int') : $allLinks['AllEmergencies']->id[0];
	$defenseurId[0]     = GETPOST('defenseur_socid', 'int') ? GETPOST('defenseur_socid', 'int') : $allLinks['RightsDefender']->id[0] ;
	$antipoisonId[0]    = GETPOST('antipoison_socid', 'int') ? GETPOST('antipoison_socid', 'int') : $allLinks['PoisonControlCenter']->id[0];
	$responsibleId[0]   = GETPOST('responsible_socid', 'int') ? GETPOST('responsible_socid', 'int') : $allLinks['Responsible']->id[0];

	$resources->setDigiriskResources($db, $user->id,  'SAMU',  'societe', $samuId, $conf->entity);
	$resources->setDigiriskResources($db, $user->id,  'Pompiers',  'societe', $pompiersId, $conf->entity);
	$resources->setDigiriskResources($db, $user->id,  'Police',  'societe', $policeId, $conf->entity);
	$resources->setDigiriskResources($db, $user->id,  'AllEmergencies',  'societe', $touteUrgenceId, $conf->entity);
	$resources->setDigiriskResources($db, $user->id,  'RightsDefender',  'societe', $defenseurId, $conf->entity);
	$resources->setDigiriskResources($db, $user->id,  'PoisonControlCenter',  'societe', $antipoisonId, $conf->entity);
	$resources->setDigiriskResources($db, $user->id,  'Responsible',  'societe', $responsibleId, $conf->entity);

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_LOCATION_OF_DETAILED_INSTRUCTION", GETPOST("emplacementCD", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISKDOLIBARR_SOCIETY_DESCRIPTION", GETPOST("description", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISKDOLIBARR_GENERAL_MEANS", GETPOST("moyensgeneraux", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISKDOLIBARR_GENERAL_RULES", GETPOST("consignesgenerales", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISKDOLIBARR_FIRST_AID", GETPOST("firstaid", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISKDOLIBARR_RULES_LOCATION", GETPOST("emplacementRI", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISKDOLIBARR_DUER_LOCATION", GETPOST("emplacementDU", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_LOCATION", GETPOST("emplacementCC", 'none'), 'chaine', 0, '', $conf->entity);

	if ($action != 'updateedit' && ! $error) {
        setEventMessages($langs->trans('SetupSaved'), '');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

$form = new Form($db);

$helpUrl = 'FR:Module_Digirisk#L.27onglet_S.C3.A9curit.C3.A9';
$title    = $langs->trans("CompanyFoundation") . ' - ' . $langs->trans("Security");

saturne_header(0,'', $title, $helpUrl);

$counter = 0;

$securityResources = array("SAMU","Pompiers","Police","AllEmergencies","RightsDefender","PoisonControlCenter", "Responsible", "LabourDoctorSociety", "LabourDoctorContact", "LabourInspectorSociety", "LabourInspectorContact" );
$securityConsts    = array("DIGIRISKDOLIBARR_LOCATION_OF_DETAILED_INSTRUCTION", "DIGIRISKDOLIBARR_SOCIETY_DESCRIPTION", "DIGIRISKDOLIBARR_GENERAL_MEANS", "DIGIRISKDOLIBARR_GENERAL_RULES", "DIGIRISKDOLIBARR_FIRST_AID", "DIGIRISKDOLIBARR_RULES_LOCATION", "DIGIRISKDOLIBARR_DUER_LOCATION", "DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_LOCATION");
$socialResources   = array("TitularsCSE", "AlternatesCSE", "TitularsDP", "AlternatesDP");

$maxnumber = count($securityResources) + count($securityConsts);

foreach ($securityConsts as $securityConst) {
	if (dol_strlen($conf->global->$securityConst) > 0) {
		$counter += 1;
	}
}
foreach ($securityResources as $securityResource) {
	if ( ! empty($allLinks[$securityResource] && $allLinks[$securityResource]->id[0] > 0)) {
		$counter += 1;
	}
}


print load_fiche_titre($title, '', 'title_setup');

$head = company_admin_prepare_head();

print dol_get_fiche_head($head, 'security', '', -1, '');

print '<span class="opacitymedium">' . $langs->trans("DigiriskMenu") . "</span><br>\n";
print "<br>";

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="form_index">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">'; ?>

<h2 class="">
	<?php echo $langs->trans('SecurityConfiguration') ?>
</h2>
<?php include_once '../core/tpl/digiriskdolibarr_configuration_gauge_view.tpl.php'; ?>
<hr>

<?php print '<table class="noborder centpercent editmode">';

if (isModEnabled('societe')) {
	/*
	*** Labour Doctor -- Médecin du travail ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("LabourDoctor") . '</th><th><i class="fas fa-briefcase-medical"></i></th></tr>' . "\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">' . $langs->trans("ActionOnCompany") . '</td><td>';

	$labourdDoctorSociety = $allLinks['LabourDoctorSociety'];

	// * Third party concerned - Tiers concerné *

	if ($labourdDoctorSociety->ref == 'LabourDoctorSociety') {
		$events   = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourdoctor_contactid', 'params' => array('add-customer-contact' => 'disabled'));
		$societe->fetch($labourdDoctorSociety->id[0]);

		print $form->select_company($labourdDoctorSociety->id[0], 'labourdoctor_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
	} else {
		$events   = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourdoctor_contactid', 'params' => array('add-customer-contact' => 'disabled'));

		//For external user force the company to user company
		if ( ! empty($user->socid)) {
			print $form->select_company($user->socid, 'labourdoctor_socid', '', 0, 1, 0, $events, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'labourdoctor_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
		}
	}
	if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	// * Related contacts - Contacts associés *
	print '<tr class="oddeven"><td class="nowrap">' . $langs->trans("ActionOnContact") . '</td><td>';

	$labourdDoctorContact 		= $allLinks['LabourDoctorContact'];
	$labourDoctorPreselectedIds = $labourdDoctorContact->id;

	if ($labourdDoctorContact->id) {
		print $form->selectcontacts(empty($labourdDoctorSociety->id[0]) ? -1 : $labourdDoctorSociety->id[0], $labourdDoctorContact->id, 'labourdoctor_contactid[]', 0, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourdoctor_contactid');
	} else {
		$labourDoctorPreselectedIds                                                                                             = GETPOST('labourdoctor_contactid', 'array');
		if (GETPOST('labourdoctor_contactid', 'array')) $labourDoctorPreselectedIds[GETPOST('labourdoctor_contactid', 'array')] = GETPOST('labourdoctor_contactid', 'array');
		print $form->selectcontacts(empty(GETPOST('labourdoctor_socid', 'int')) ? -1 : GETPOST('labourdoctor_socid', 'int'), $labourDoctorPreselectedIds, 'labourdoctor_contactid[]', 0, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourdoctor_contactid');
	}
	print '</td></tr>';

	/*
	 *** Labour Inspector -- Inspecteur du travail ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("LabourInspector") . '</th><th><i class="fas fa-search"></i></th></tr>' . "\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">' . $langs->trans("ActionOnCompany") . '</td><td>';

	$labourdInspectorSociete = $allLinks['LabourInspectorSociety'];

	// * Third party concerned - Tiers concerné *

	if ($labourdInspectorSociete->ref == 'LabourInspectorSociety') {
		$events   = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourinspector_contactid', 'params' => array('add-customer-contact' => 'disabled'));
		$societe->fetch($labourdInspectorSociete->id[0]);

		print $form->select_company($labourdInspectorSociete->id[0], 'labourinspector_socid', '', 0, 1, 0, $events, 0, 'minwidth300');
	} else {
		$events   = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourinspector_contactid', 'params' => array('add-customer-contact' => 'disabled'));
		//For external user force the company to user company
		if ( ! empty($user->socid)) {
			print $form->select_company($user->socid, 'labourinspector_socid', '', 0, 1, 0, $events, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'labourinspector_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
		}
	}
	if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	// * Related contacts - Contacts associés *

	print '<tr class="oddeven"><td class="nowrap">' . $langs->trans("ActionOnContact") . '</td><td>';

	$labourInspectorContacts       = $allLinks['LabourInspectorContact'];
	$labourInspectorPreselectedIds = $labourInspectorContacts->id;

	if ($labourInspectorContacts->id) {
		print $form->selectcontacts(empty($labourdInspectorSociete->id[0]) ? -1 : $labourdInspectorSociete->id[0], $labourInspectorContacts->id, 'labourinspector_contactid[]', 0, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourinspector_contactid');
	} else {
		$labourInspectorPreselectedIds                                                                                                   = GETPOST('labourinspector_contactid', 'array');
		if (GETPOST('labourinspector_contactid', 'array')) $labourInspectorPreselectedIds[GETPOST('labourinspector_contactid', 'array')] = GETPOST('labourinspector_contactid', 'array');
		print $form->selectcontacts(empty(GETPOST('labourinspector_socid', 'int')) ? -1 : GETPOST('labourinspector_socid', 'int'), $labourInspectorPreselectedIds, 'labourinspector_contactid[]', 0, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourinspector_contactid');
	}
	print '</td></tr>';

	/*
	*** Emergencies -- SAMU ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("SAMU") . '</th><th><i class="fas fa-hospital-alt"></i></th></tr>' . "\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">' . $langs->trans("ActionOnCompany") . '</td><td>';

	$samuResources = $allLinks['SAMU'];

	// * Third party concerned - Tiers concerné *

	if ($samuResources->ref == 'SAMU') {
		$societe->fetch($samuResources->id[0]);

		print $form->select_company($samuResources->id[0], 'samu_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
	} else {
		//For external user force the company to user company
		if ( ! empty($user->socid)) {
			print $form->select_company($user->socid, 'samu_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'samu_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
		}
	}
	if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	/*
	*** Fire Brigade -- Pompiers ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("FireBrigade") . '</th><th><i class="fas fa-ambulance"></i></th></tr>' . "\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">' . $langs->trans("ActionOnCompany") . '</td><td>';

	$pompiersResources = $allLinks['Pompiers'];

	// * Third party concerned - Tiers concerné *

	if ($pompiersResources->ref == 'Pompiers') {
		$societe->fetch($pompiersResources->id[0]);
		print $form->select_company($pompiersResources->id[0], 'pompiers_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
	} else {
		//For external user force the company to user company
		if ( ! empty($user->socid)) {
			print $form->select_company($user->socid, 'pompiers_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'pompiers_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
		}
	}
	if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	/*
	*** Police -- Police ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("Police") . '</th><th><i class="fas fa-car"></i></th></tr>' . "\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">' . $langs->trans("ActionOnCompany") . '</td><td>';

	$policeResources = $allLinks['Police'];

	// * Third party concerned - Tiers concerné *

	if ($policeResources->ref == 'Police') {
		$societe->fetch($policeResources->id[0]);
		print $form->select_company($policeResources->id[0], 'police_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
	} else {
		//For external user force the company to user company
		if ( ! empty($user->socid)) {
			print $form->select_company($user->socid, 'police_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'police_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
		}
	}
	if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	/*
	*** For any emergency -- Pour toute urgence ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("AllEmergencies") . '</th><th><i class="fas fa-phone"></i></th></tr>' . "\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">' . $langs->trans("ActionOnCompany") . '</td><td>';

	$touteUrgenceResources = $allLinks['AllEmergencies'];

	// * Third party concerned - Tiers concerné *

	if ($touteUrgenceResources->ref == 'AllEmergencies') {
		$societe->fetch($touteUrgenceResources->id[0]);
		print $form->select_company($touteUrgenceResources->id[0], 'touteurgence_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
	} else {
		//For external user force the company to user company
		if ( ! empty($user->socid)) {
			print $form->select_company($user->socid, 'touteurgence_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'touteurgence_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
		}
	}
	if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	/*
	*** Rights defender -- Défenseur des droits ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("RightsDefender") . '</th><th><i class="fas fa-gavel"></i></th></tr>' . "\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">' . $langs->trans("ActionOnCompany") . '</td><td>';

	$defenseurResources = $allLinks['RightsDefender'];

	// * Third party concerned - Tiers concerné *

	if ($defenseurResources->ref == 'RightsDefender') {
		$societe->fetch($defenseurResources->id[0]);
		print $form->select_company($defenseurResources->id[0], 'defenseur_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
	} else {
		//For external user force the company to user company
		if ( ! empty($user->socid)) {
			print $form->select_company($user->socid, 'defenseur_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'defenseur_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
		}
	}
	if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';

	/*
	*** Poison control center -- Centre antipoison ***
	*/

	print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("PoisonControlCenter") . '</th><th><i class="fas fa-skull-crossbones"></i></th></tr>' . "\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">' . $langs->trans("ActionOnCompany") . '</td><td>';
	$poisonControlCenterResources = $allLinks['PoisonControlCenter'];

	// * Third party concerned - Tiers concerné *

	if ($poisonControlCenterResources->ref == 'PoisonControlCenter') {
		$societe->fetch($poisonControlCenterResources->id[0]);
		print $form->select_company($poisonControlCenterResources->id[0], 'antipoison_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
	} else {
		//For external user force the company to user company
		if ( ! empty($user->socid)) {
			print $form->select_company($user->socid, 'antipoison_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'antipoison_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');
		}
	}
	if ( ! GETPOSTISSET('backtopage')) print ' <a href="' . DOL_URL_ROOT . '/societe/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddThirdParty") . '"></span></a>';
	print '</td></tr>';
}

/*
*** Safety instructions -- Consignes de sécurité ***
*/

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("SafetyInstructions") . '</th><th></th></tr>' . "\n";

// * Responsible to notify - Responsable à prévenir *

$responsibleResources = $allLinks['Responsible'];

// * Third party concerned - Tiers concerné *

if ($responsibleResources->ref == 'Responsible' && $responsibleResources->id[0] > 0) {
	$usertmp->fetch($responsibleResources->id[0]);

	$userlist = $form->select_dolusers(GETPOST('responsible_socid'), '', 0, null, 0, '', '', 0, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300', 0, 1);
	print '<tr>';
	print '<td class="" style="width:10%">' . $form->editfieldkey('ResponsibleToNotify', 'Responsible_id', '', $object, 0) . '</td>';
	print '<td>';
	print img_picto('', 'user', 'class="pictofixedwidth"') . $form->selectarray('responsible_socid', $userlist, $usertmp->id, $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
	print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
	print '</td></tr>';

	// * Phone number - Numéro de téléphone *

	print '<tr class="oddeven"><td><label for="name">' . $langs->trans("PhonePro") . '</label></td><td>';

    print img_picto('', 'object_phoning', 'class="pictofixedwidth"');
	if ($usertmp->office_phone > 0) {
		print $usertmp->office_phone;
	} else { ?>
		<a href="<?php echo DOL_URL_ROOT . '/' ?>user/card.php?id=<?php echo $usertmp->id ?>" target="_blank">
			<i class="fas fa-plus"></i><?php print ' ' . $langs->trans('AddPhoneNumber'); ?>
		</a>
	<?php }

	print '</td></tr>';
} else { //id = 0
	//For external user force the company to user company
	if ( ! empty($user->socid)) {
		print $form->select_dolusers($user->socid, 'responsible_socid', 1, 1, 0, 0, 0, 0, 'minwidth300');
	} else {
		$userlist = $form->select_dolusers(GETPOST('responsible_socid'), '', 0, null, 0, '', '', 0, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300', 0, 1);
		print '<tr>';
		print '<td class="" style="width:10%">' . $form->editfieldkey('ResponsibleToNotify', 'Responsible_id', '', $object, 0) . '</td>';
		print '<td>';
		print $form->selectarray('responsible_socid', $userlist, GETPOST('responsible_socid'), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
		print ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '" target="_blank"><span class="fa fa-plus-circle valignmiddle paddingleft" title="' . $langs->trans("AddUser") . '"></span></a>';
		print '</td></tr>';
	}
}

print '</td></tr>';

// * Location of detailed instructions - Emplacement de la consigne détaillée *

print '<tr class="oddeven"><td><label for="emplacementCD">' . $langs->trans("LocationOfDetailedInstructions") . '</label></td><td>';
$doleditor = new DolEditor('emplacementCD', $conf->global->DIGIRISKDOLIBARR_LOCATION_OF_DETAILED_INSTRUCTION ? $conf->global->DIGIRISKDOLIBARR_LOCATION_OF_DETAILED_INSTRUCTION : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

/*
*** Society additional details -- Informations complémentaires de la société ***
*/

print '<tr class="liste_titre"><th class="titlefield">' . $langs->trans("SocietyAdditionalDetails") . '</th><th></th></tr>' . "\n";

// * Description - Emplacement de la consigne détaillée *

print '<tr class="oddeven"><td><label for="description">' . $langs->trans("Description") . '</label></td><td>';
$doleditor = new DolEditor('description', $conf->global->DIGIRISKDOLIBARR_SOCIETY_DESCRIPTION ? $conf->global->DIGIRISKDOLIBARR_SOCIETY_DESCRIPTION : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

// * General means at disposal - Moyens généraux mis à disposition *

print '<tr class="oddeven"><td><label for="moyensgeneraux">' . $langs->trans("GeneralMeansAtDisposal") . '</label></td><td>';
$doleditor = new DolEditor('moyensgeneraux', $conf->global->DIGIRISKDOLIBARR_GENERAL_MEANS ? $conf->global->DIGIRISKDOLIBARR_GENERAL_MEANS : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

// * General instructions - Consignes générales *

print '<tr class="oddeven"><td><label for="consignesgenerales">' . $langs->trans("GeneralInstructions") . '</label></td><td>';
$doleditor = new DolEditor('consignesgenerales', $conf->global->DIGIRISKDOLIBARR_GENERAL_RULES ? $conf->global->DIGIRISKDOLIBARR_GENERAL_RULES : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

// * General instructions - Consignes générales *

print '<tr class="oddeven"><td><label for="firstaid">' . $langs->trans("FirstAid") . '</label></td><td>';
$doleditor = new DolEditor('firstaid', $conf->global->DIGIRISKDOLIBARR_FIRST_AID ? $conf->global->DIGIRISKDOLIBARR_FIRST_AID : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

// * Rules of procedure - Règlement intérieur *

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("RulesOfProcedure") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";

// * Rules of procedure location - Emplacement du règlement intérieur *

print '<tr class="oddeven"><td><label for="emplacementRI">' . $langs->trans("Location") . '</label></td><td>';
$doleditor = new DolEditor('emplacementRI', $conf->global->DIGIRISKDOLIBARR_RULES_LOCATION ? $conf->global->DIGIRISKDOLIBARR_RULES_LOCATION : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

// * Risks evaluation - Document Unique *

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("RiskAssessmentDocument") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";

// * Risks evaluation location - Emplacement du Document Unique *

print '<tr class="oddeven"><td><label for="emplacementDU">' . $langs->trans("Location") . '</label></td><td>';
$doleditor = new DolEditor('emplacementDU', $conf->global->DIGIRISKDOLIBARR_DUER_LOCATION ? $conf->global->DIGIRISKDOLIBARR_DUER_LOCATION : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';

// * Collective Agreement - Convention collective *

print '<tr class="liste_titre"><th class="titlefield wordbreak">' . $langs->trans("CollectiveAgreement") . '</th><th>' . $langs->trans("") . '</th></tr>' . "\n";

// * Collective Agreement location - Emplacement de la Convention collective *

print '<tr class="oddeven"><td><label for="emplacementCC">' . $langs->trans("Location") . '</label></td><td>';
$doleditor = new DolEditor('emplacementCC', $conf->global->DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_LOCATION ? $conf->global->DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_LOCATION : '', '', 200, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();
print '</td></tr>';
print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</div>';
print '</form>';

llxFooter();
$db->close();
