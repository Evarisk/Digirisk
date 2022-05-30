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

require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

//Check projet
if ($conf->global->DIGIRISKDOLIBARR_DU_PROJECT > 0) {
	$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
	//Backward compatibility
	if ($project->title == $langs->trans('RiskAssessmentDocument')) {
		$project->title       = $langs->trans('RiskAssessmentDocumentInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
		$project->description = $langs->trans('RiskAssessmentDocumentDescription');
		$project->update($user);
	}

	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'DU');
	$tags->add_type($project, 'project');
}

if ( $conf->global->DIGIRISKDOLIBARR_DU_PROJECT == 0 || $project->statut == 2 ) {
	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('RiskAssessmentDocumentInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$project->description = $langs->trans('RiskAssessmentDocumentDescription');
	$project->date_c      = dol_now();
	$currentYear          = dol_print_date(dol_now(), '%Y');
	$fiscalMonthStart     = $conf->global->SOCIETE_FISCAL_MONTH_START;
	$startdate            = dol_mktime('0', '0', '0', $fiscalMonthStart ? $fiscalMonthStart : '1', '1', $currentYear);
	$project->date_start  = $startdate;

	$project->usage_task = 1;

	$startdateAddYear      = dol_time_plus_duree($startdate, 1, 'y');
	$startdateAddYearMonth = dol_time_plus_duree($startdateAddYear, -1, 'd');
	$enddate               = dol_print_date($startdateAddYearMonth, 'dayrfc');
	$project->date_end     = $enddate;
	$project->statut       = 1;
	$project_id            = $project->create($user);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DU_PROJECT', $project_id, 'integer', 0, '', $conf->entity);

	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'DU');
	$tags->add_type($project, 'project');

	$url = '/projet/tasks.php?id=' . $project_id;

	$sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
	$sql .= " url='".$db->escape($url)."'";
	$sql .= " WHERE leftmenu='digiriskactionplan'";
	$sql .= " AND entity=" . $conf->entity;

	$resql = $db->query($sql);
	if (!$resql) {
		$error = "Error ".$db->lasterror();
		return -1;
	}
	header("Location: " . $_SERVER['PHP_SELF']);
}

if ($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT > 0) {
	$project->fetch($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT);
	//Backward compatibility
	if ($project->title == $langs->trans('PreventionPlan')) {
		$project->title = $langs->trans('PreventionPlanInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
		$project->update($user);
	}

	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'PP');
	$tags->add_type($project, 'project');
}

if ( $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT == 0 || $project->statut == 2 ) {
	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('PreventionPlanInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$project->description = $langs->transnoentities('PreventionPlanDescription');
	$project->date_c      = dol_now();
	$currentYear          = dol_print_date(dol_now(), '%Y');
	$fiscalMonthStart     = $conf->global->SOCIETE_FISCAL_MONTH_START;
	$startdate            = dol_mktime('0', '0', '0', $fiscalMonthStart ? $fiscalMonthStart : '1', '1', $currentYear);
	$project->date_start  = $startdate;

	$project->usage_task = 1;

	$startdateAddYear      = dol_time_plus_duree($startdate, 1, 'y');
	$startdateAddYearMonth = dol_time_plus_duree($startdateAddYear, -1, 'd');
	$enddate               = dol_print_date($startdateAddYearMonth, 'dayrfc');
	$project->date_end     = $enddate;
	$project->statut       = 1;
	$project_id            = $project->create($user);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT', $project_id, 'integer', 0, '', $conf->entity);

	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'PP');
	$tags->add_type($project, 'project');
}

if ( $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_PROJECT == 0 || $project->statut == 2 ) {
	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('FirePermitInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$project->description = $langs->trans('FirePermitDescription');
	$project->date_c      = dol_now();
	$currentYear          = dol_print_date(dol_now(), '%Y');
	$fiscalMonthStart     = $conf->global->SOCIETE_FISCAL_MONTH_START;
	$startdate            = dol_mktime('0', '0', '0', $fiscalMonthStart ? $fiscalMonthStart : '1', '1', $currentYear);
	$project->date_start  = $startdate;

	$project->usage_task = 1;

	$startdateAddYear      = dol_time_plus_duree($startdate, 1, 'y');
	$startdateAddYearMonth = dol_time_plus_duree($startdateAddYear, -1, 'd');
	$enddate               = dol_print_date($startdateAddYearMonth, 'dayrfc');
	$project->date_end     = $enddate;
	$project->statut       = 1;
	$project_id            = $project->create($user);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_PROJECT', $project_id, 'integer', 0, '', $conf->entity);

	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'FP');
	$tags->add_type($project, 'project');
}

if ( $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT == 0 || $project->statut == 2 ) {
	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('AccidentInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$project->description = $langs->trans('AccidentDescription');
	$project->date_c      = dol_now();
	$currentYear          = dol_print_date(dol_now(), '%Y');
	$fiscalMonthStart     = $conf->global->SOCIETE_FISCAL_MONTH_START;
	$startdate            = dol_mktime('0', '0', '0', $fiscalMonthStart ? $fiscalMonthStart : '1', '1', $currentYear);
	$project->date_start  = $startdate;

	$project->usage_task = 1;

	$startdateAddYear      = dol_time_plus_duree($startdate, 1, 'y');
	$startdateAddYearMonth = dol_time_plus_duree($startdateAddYear, -1, 'd');
	$enddate               = dol_print_date($startdateAddYearMonth, 'dayrfc');
	$project->date_end     = $enddate;
	$project->statut       = 1;
	$project_id            = $project->create($user);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ACCIDENT_PROJECT', $project_id, 'integer', 0, '', $conf->entity);

	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'ACC');
	$tags->add_type($project, 'project');
}

if ( $conf->global->DIGIRISKDOLIBARR_TICKET_PROJECT == 0 || $project->statut == 2 ) {
	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('TicketInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$project->description = $langs->trans('TicketDescription');
	$project->date_c      = dol_now();
	$currentYear          = dol_print_date(dol_now(), '%Y');
	$fiscalMonthStart     = $conf->global->SOCIETE_FISCAL_MONTH_START;
	$startdate            = dol_mktime('0', '0', '0', $fiscalMonthStart ? $fiscalMonthStart : '1', '1', $currentYear);
	$project->date_start  = $startdate;

	$project->usage_task = 1;

	$startdateAddYear      = dol_time_plus_duree($startdate, 1, 'y');
	$startdateAddYearMonth = dol_time_plus_duree($startdateAddYear, -1, 'd');
	$enddate               = dol_print_date($startdateAddYearMonth, 'dayrfc');
	$project->date_end     = $enddate;
	$project->statut       = 1;
	$project_id            = $project->create($user);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_PROJECT', $project_id, 'integer', 0, '', $conf->entity);

	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'TS');
	$tags->add_type($project, 'project');
}

if ( $conf->global->DIGIRISKDOLIBARR_USERAPI_SET == 0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

	$usertmp            = new User($db);
	$usertmp->lastname  = 'API';
	$usertmp->firstname = 'REST';
	$usertmp->login     = 'USERAPI';
	$usertmp->email     = '';
	$usertmp->entity    = $conf->entity;
	$usertmp->setPassword($user);
	$usertmp->api_key = getRandomPassword(true);

	$user_id = $usertmp->create($user);
	if ($user_id > 0) {
		$apiUser = new User($db);
		$apiUser->fetch($user_id);
		//Rights digiriskdolibarr api
		$apiUser->addrights(43630236);
		$apiUser->addrights(43630237);
	}

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_USERAPI_SET', $user_id, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_READERGROUP_SET == 0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup         = new UserGroup($db);
	$usergroup->entity = $conf->entity;
	$usergroup->name   = $conf->global->MAIN_INFO_SOCIETE_NOM . ' - ' . $langs->trans('DigiriskReaderGroup');
	$usergroup->note   = $langs->trans('DigiriskReaderGroupDescription');

	$usergroup_id = $usergroup->create($user);
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		//Rights digiriskdolibarr
		$usergroup->addrights(4363020); //DigiriskDolibarr read
		$usergroup->addrights(4363021); //DigiriskDolibarr lire
		$usergroup->addrights(4363022); //RiskAssessmentDocument Read
		$usergroup->addrights(4363025); //LegalDisplay Read
		$usergroup->addrights(4363028); //InformationsSharing Read
		$usergroup->addrights(43630211); //FirePermit Read
		$usergroup->addrights(43630214); //Prevention Plan Read
		$usergroup->addrights(43630217); //DigiriskElement Read
		$usergroup->addrights(43630220); //Risk Read
		$usergroup->addrights(43630223); //ListingRisksAction Read
		$usergroup->addrights(43630226); //ListingRisksPhoto Read
		$usergroup->addrights(43630229); //RiskSign Read
		$usergroup->addrights(43630232); //Evaluator Read
		$usergroup->addrights(43630238); //Accident Read
		$usergroup->addrights('', 'produit', 'lire');
		$usergroup->addrights('', 'societe', 'lire');
		$usergroup->addrights('', 'ecm', 'read');
		$usergroup->addrights('', 'ticket', 'read');
		$usergroup->addrights('', 'agenda', 'myactions');
	}
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_READERGROUP_SET', $usergroup_id, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_READERGROUP_UPDATED == 0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_READERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		$usergroup->name = $conf->global->MAIN_INFO_SOCIETE_NOM . ' - ' . $langs->trans('DigiriskReaderGroup');
		$usergroup->note = $langs->trans('DigiriskReaderGroupDescription');
		$usergroup->update($user);
	}
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_READERGROUP_UPDATED', 1, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_READERGROUP_UPDATED == 1 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_READERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		//Rights digiriskdolibarr
		$usergroup->addrights(43630238); //Accident Read
	}
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_READERGROUP_UPDATED', 2, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_USERGROUP_SET == 0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup         = new UserGroup($db);
	$usergroup->entity = $conf->entity;
	$usergroup->name   = $conf->global->MAIN_INFO_SOCIETE_NOM . ' - ' . $langs->trans('DigiriskUserGroup');
	$usergroup->note   = $langs->trans('DigiriskUserGroupDescription');

	$usergroup_id = $usergroup->create($user);
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		//Rights digiriskdolibarr
		$usergroup->addrights(4363020); //DigiriskDolibarr read
		$usergroup->addrights(4363021); //DigiriskDolibarr lire
		$usergroup->addrights(4363022); //RiskAssessmentDocument Read
		$usergroup->addrights(4363023); //RiskAssessmentDocument Create
		$usergroup->addrights(4363025); //LegalDisplay Read
		$usergroup->addrights(4363026); //LegalDisplay Create
		$usergroup->addrights(4363028); //InformationsSharing Read
		$usergroup->addrights(4363029); //InformationsSharing Create
		$usergroup->addrights(43630211); //FirePermit Read
		$usergroup->addrights(43630212); //FirePermit Create
		$usergroup->addrights(43630214); //Prevention Plan Read
		$usergroup->addrights(43630215); //Prevention Plan Create
		$usergroup->addrights(43630217); //DigiriskElement Read
		$usergroup->addrights(43630218); //DigiriskElement Create
		$usergroup->addrights(43630220); //Risk Read
		$usergroup->addrights(43630221); //Risk Create
		$usergroup->addrights(43630223); //ListingRisksAction Read
		$usergroup->addrights(43630224); //ListingRisksAction Create
		$usergroup->addrights(43630226); //ListingRisksPhoto Read
		$usergroup->addrights(43630227); //ListingRisksPhoto Create
		$usergroup->addrights(43630229); //RiskSign Read
		$usergroup->addrights(43630230); //RiskSign Create
		$usergroup->addrights(43630232); //Evaluator Read
		$usergroup->addrights(43630233); //Evaluator Create
		$usergroup->addrights(43630238); //Accident Read
		$usergroup->addrights(43630239); //Accident Create
		$usergroup->addrights('', 'produit', 'lire');
		$usergroup->addrights('', 'produit', 'creer');
		$usergroup->addrights('', 'societe', 'lire');
		$usergroup->addrights('', 'societe', 'creer');
		$usergroup->addrights('', 'ecm', 'read');
		$usergroup->addrights('', 'ecm', 'upload');
		$usergroup->addrights('', 'ticket', 'read');
		$usergroup->addrights('', 'ticket', 'write');
		$usergroup->addrights('', 'agenda', 'myactions');
	}

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_USERGROUP_SET', $usergroup_id, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_USERGROUP_UPDATED == 0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_USERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		//Rights digiriskdolibarr
		$usergroup->addrights(4363020); //DigiriskDolibarr read
		$usergroup->addrights(4363021); //DigiriskDolibarr lire
		$usergroup->addrights(4363022); //RiskAssessmentDocument Read
		$usergroup->addrights(4363023); //RiskAssessmentDocument Create
		$usergroup->addrights(4363025); //LegalDisplay Read
		$usergroup->addrights(4363026); //LegalDisplay Create
		$usergroup->addrights(4363028); //InformationsSharing Read
		$usergroup->addrights(4363029); //InformationsSharing Create
		$usergroup->addrights(43630211); //FirePermit Read
		$usergroup->addrights(43630212); //FirePermit Create
		$usergroup->addrights(43630214); //Prevention Plan Read
		$usergroup->addrights(43630215); //Prevention Plan Create
		$usergroup->addrights(43630217); //DigiriskElement Read
		$usergroup->addrights(43630218); //DigiriskElement Create
		$usergroup->addrights(43630220); //Risk Read
		$usergroup->addrights(43630221); //Risk Create
		$usergroup->addrights(43630223); //ListingRisksAction Read
		$usergroup->addrights(43630224); //ListingRisksAction Create
		$usergroup->addrights(43630226); //ListingRisksPhoto Read
		$usergroup->addrights(43630227); //ListingRisksPhoto Create
		$usergroup->addrights(43630229); //RiskSign Read
		$usergroup->addrights(43630230); //RiskSign Create
		$usergroup->addrights(43630232); //Evaluator Read
		$usergroup->addrights(43630233); //Evaluator Create
		$usergroup->addrights(43630238); //Accident Read
		$usergroup->addrights(43630239); //Accident Create
		$usergroup->addrights('', 'produit', 'lire');
		$usergroup->addrights('', 'produit', 'creer');
		$usergroup->addrights('', 'societe', 'lire');
		$usergroup->addrights('', 'societe', 'creer');
		$usergroup->addrights('', 'ecm', 'read');
		$usergroup->addrights('', 'ecm', 'upload');
		$usergroup->addrights('', 'ticket', 'read');
		$usergroup->addrights('', 'ticket', 'write');
		$usergroup->addrights('', 'agenda', 'myactions');
	}

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_USERGROUP_UPDATED', 1, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_USERGROUP_UPDATED == 1 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_USERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		$usergroup->name = $conf->global->MAIN_INFO_SOCIETE_NOM . ' - ' . $langs->trans('DigiriskUserGroup');
		$usergroup->note = $langs->trans('DigiriskUserGroupDescription');
		$usergroup->update($user);
	}
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_USERGROUP_UPDATED', 2, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_USERGROUP_UPDATED == 2 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_USERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		$usergroup->name = $conf->global->MAIN_INFO_SOCIETE_NOM . ' - ' . $langs->trans('DigiriskUserGroup');
		$usergroup->note = $langs->trans('DigiriskUserGroupDescription');
		$usergroup->update($user);

		$usergroup->addrights(43630238); //Accident Read
		$usergroup->addrights(43630239); //Accident Create
	}
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_USERGROUP_UPDATED', 3, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_SET == 0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup         = new UserGroup($db);
	$usergroup->entity = $conf->entity;
	$usergroup->name   = $conf->global->MAIN_INFO_SOCIETE_NOM . ' - ' . $langs->trans('DigiriskAdminUserGroup');
	$usergroup->note   = $langs->trans('DigiriskAdminUserGroupDescription');

	$usergroup_id = $usergroup->create($user);
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		//Rights digiriskdolibarr
		$usergroup->addrights(4363020); //DigiriskDolibarr read
		$usergroup->addrights(4363021); //DigiriskDolibarr lire
		$usergroup->addrights('', 'digiriskdolibarr', 'riskassessmentdocument'); //RiskAssessmentDocument
		$usergroup->addrights('', 'digiriskdolibarr', 'legaldisplay');           //LegalDisplay
		$usergroup->addrights('', 'digiriskdolibarr', 'informationssharing');    //InformationsSharing
		$usergroup->addrights('', 'digiriskdolibarr', 'firepermit');             //FirePermit
		$usergroup->addrights('', 'digiriskdolibarr', 'preventionplan');         //Prevention Plan
		$usergroup->addrights('', 'digiriskdolibarr', 'digiriskelement');        //DigiriskElement
		$usergroup->addrights('', 'digiriskdolibarr', 'risk');                   //Risk
		$usergroup->addrights('', 'digiriskdolibarr', 'listingrisksaction');     //ListingRisksAction
		$usergroup->addrights('', 'digiriskdolibarr', 'listingrisksphoto');      //ListingRisksPhoto
		$usergroup->addrights('', 'digiriskdolibarr', 'risksign');               //RiskSign
		$usergroup->addrights('', 'digiriskdolibarr', 'evaluator');              //Evaluator
		$usergroup->addrights('', 'digiriskdolibarr', 'api');                    //API
		$usergroup->addrights('', 'digiriskdolibarr', 'adminpage');              //AdminPage
		$usergroup->addrights('', 'digiriskdolibarr', 'accident'); 			  //Accident
		$usergroup->addrights('', 'societe');
		$usergroup->addrights('', 'ecm');
		$usergroup->addrights('', 'ticket');
		$usergroup->addrights('', 'agenda');
		$usergroup->addrights('', 'projet');
	}

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ADMINUSERGROUP_SET', $usergroup_id, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_UPDATED == 0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		//Rights digiriskdolibarr
		$usergroup->addrights('', 'societe');
		$usergroup->addrights('', 'ecm');
		$usergroup->addrights('', 'ticket');
		$usergroup->addrights('', 'agenda');
		$usergroup->addrights('', 'projet');
	}

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ADMINUSERGROUP_UPDATED', 1, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_UPDATED == 1 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		$usergroup->name = $conf->global->MAIN_INFO_SOCIETE_NOM . ' - ' . $langs->trans('DigiriskAdminUserGroup');
		$usergroup->note = $langs->trans('DigiriskAdminUserGroupDescription');
		$usergroup->update($user);
	}
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ADMINUSERGROUP_UPDATED', 2, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_UPDATED == 2 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		//Rights digiriskdolibarr
		$usergroup->addrights('', 'digiriskdolibarr', 'accident'); //Accident
	}

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ADMINUSERGROUP_UPDATED', 3, 'integer', 0, '', $conf->entity);
}

if ($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH_UPDATED == 0) {
	require_once __DIR__ . '/../../class/digiriskelement/groupment.class.php';

	$digiriskelement = new Groupment($db);
	$digiriskelement->fetch($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);

	$dirforimage     = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/defaultImgGP0/';
	$original_file   = 'trash-alt-solid.png';
	$dir             = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/groupment/';
	$src_file        = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/groupment/GP0/';
	$src_file_thumbs = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/groupment/GP0/thumbs/';

	if ( ! is_dir($dir)) {
		dol_mkdir($dir);
	}

	if ( ! is_dir($src_file)) {
		dol_mkdir($src_file);
	}

	if ( ! is_dir($src_file_thumbs)) {
		dol_mkdir($src_file_thumbs);
	}

	dol_copy($dirforimage . $original_file, $src_file . $original_file, 0, 0);
	dol_copy($dirforimage . '/thumbs/trash-alt-solid_mini.png', $src_file . '/thumbs/trash-alt-solid_mini.png', 0, 0);
	dol_copy($dirforimage . '/thumbs/trash-alt-solid_small.png', $src_file . '/thumbs/trash-alt-solid_small.png', 0, 0);

	$digiriskelement->photo = $original_file;
	$result                 = $digiriskelement->update($user);

	if ($result > 0) {
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH_UPDATED', 1, 'integer', 0, '', $conf->entity);
	}
}

if ($conf->global->DIGIRISKDOLIBARR_CONF_BACKWARD_COMPATIBILITY == 0) {
	// CONST CONFIGURATION
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_GENERAL_MEANS', dolibarr_get_const($db,'DIGIRISK_GENERAL_MEANS'), 'chaine', 0, 'General means', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_GENERAL_RULES', dolibarr_get_const($db,'DIGIRISK_GENERAL_RULES'), 'chaine', 0, 'General rules', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_IDCC_DICTIONNARY', dolibarr_get_const($db,'DIGIRISK_IDCC_DICTIONNARY'), 'chaine', 0, 'IDCC of company', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SOCIETY_DESCRIPTION', dolibarr_get_const($db,'DIGIRISK_SOCIETY_DESCRIPTION'), 'chaine', 0, '', $conf->entity);

	dolibarr_del_const($db, 'DIGIRISK_GENERAL_MEANS', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_GENERAL_RULES', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_IDCC_DICTIONNARY', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_SOCIETY_DESCRIPTION', $conf->entity);

	// CONST RISK ASSESSMENT DOCUMENT
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE', dolibarr_get_const($db,'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE'), 'date', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE', dolibarr_get_const($db,'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE'), 'date', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT',  dolibarr_get_const($db,'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT'), 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD',  dolibarr_get_const($db,'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD'), 'chaine', 0, 'General means', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES',  dolibarr_get_const($db,'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES'), 'chaine', 0, 'General means', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES', dolibarr_get_const($db,'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES'), 'chaine', 0, '', $conf->entity);

	dolibarr_del_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SITE_PLANS', $conf->entity);

	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENTDOCUMENT_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON', 'mod_riskassessmentdocument_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/riskassessmentdocument/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/riskassessmentdocument/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_DEFAULT_MODEL', 'riskassessmentdocument_odt', 'chaine', 0, '', $conf->entity);

	// CONST LEGAL DISPLAY
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LOCATION_OF_DETAILED_INSTRUCTION', dolibarr_get_const($db,'DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION'), 'chaine', 0, 'Location of detailed instruction', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT', dolibarr_get_const($db,'DIGIRISK_DEROGATION_SCHEDULE_PERMANENT'), 'chaine', 0, 'Permanent exceptions to working hours', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL', dolibarr_get_const($db,'DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL'), 'chaine', 0, 'Occasional exceptions to working hours', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE', dolibarr_get_const($db,'DIGIRISK_COLLECTIVE_AGREEMENT_TITLE'), 'chaine', 0, 'Title of the collective agreement', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_LOCATION', dolibarr_get_const($db,'DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION'), 'chaine', 0, 'Location of the collective agreement', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DUER_LOCATION', dolibarr_get_const($db,'DIGIRISK_DUER_LOCATION'), 'chaine', 0, 'Location of risks evaluation', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RULES_LOCATION', dolibarr_get_const($db,'DIGIRISK_RULES_LOCATION'), 'chaine', 0, 'Location of rules of procedure', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE', dolibarr_get_const($db,'DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE'), 'chaine', 0, 'Information procedure of participation agreement', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIRST_AID', dolibarr_get_const($db,'DIGIRISK_FIRST_AID'), 'chaine', 0, '', $conf->entity);

	dolibarr_del_const($db, 'DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_DEROGATION_SCHEDULE_PERMANENT', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_COLLECTIVE_AGREEMENT_TITLE', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_DUER_LOCATION', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_RULES_LOCATION', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE', $conf->entity);
	dolibarr_del_const($db, 'DIGIRISK_FIRST_AID', $conf->entity);

	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_LEGALDISPLAY_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON', 'mod_legaldisplay_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/legaldisplay/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LEGALDISPLAY_CUSTOM_ADDON_ODT_PATH', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/legaldisplay/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LEGALDISPLAY_DEFAULT_MODEL', 'legaldisplay_odt', 'chaine', 0, '', $conf->entity);

	// CONST INFORMATIONS SHARING
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_INFORMATIONSSHARING_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON', 'mod_informationssharing_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/informationssharing/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_INFORMATIONSSHARING_CUSTOM_ADDON_ODT_PATH', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/informationssharing/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_INFORMATIONSSHARING_DEFAULT_MODEL', 'informationssharing_odt', 'chaine', 0, '', $conf->entity);

	// CONST LISTING RISKS ACTION
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSACTION_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON', 'mod_listingrisksaction_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/listingrisksaction/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LISTINGRISKSACTION_CUSTOM_ADDON_ODT_PATH', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/listingrisksaction/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LISTINGRISKSACTION_DEFAULT_MODEL', 'listingrisksaction_odt', 'chaine', 0, '', $conf->entity);

	// CONST LISTING RISKS PHOTO
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSPHOTO_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON', 'mod_listingrisksphoto_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/listingrisksphoto/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_CUSTOM_ADDON_ODT_PATH', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/listingrisksphoto/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_DEFAULT_MODEL', 'listingrisksphoto_odt', 'chaine', 0, '', $conf->entity);

	// CONST GROUPMENT DOCUMENT
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_GROUPMENTDOCUMENT_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON', 'mod_groupmentdocument_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/groupmentdocument/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/groupmentdocument/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_DEFAULT_MODEL', 'groupmentdocument_odt', 'chaine', 0, '', $conf->entity);

	// CONST WORKUNIT DOCUMENT
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_WORKUNITDOCUMENT_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON', 'mod_workunitdocument_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/workunitdocument/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_WORKUNITDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/workunitdocument/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_WORKUNITDOCUMENT_DEFAULT_MODEL', 'workunitdocument_odt', 'chaine', 0, '', $conf->entity);

	// CONST PREVENTION PLAN
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_EDIT', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON', 'mod_preventionplan_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT', dolibarr_get_const($db,'DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE', dolibarr_get_const($db,'DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE'), 'integer', 0, '', $conf->entity);

	// CONST PREVENTION PLAN DOCUMENT
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANDOCUMENT_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON', 'mod_preventionplandocument_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/preventionplandocument/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_SPECIMEN_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/preventionplandocument/specimen/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/preventionplandocument/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_DEFAULT_MODEL', 'preventionplandocument_odt', 'chaine', 0, '', $conf->entity);

	// CONST FIRE PERMIT
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_EDIT', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_ADDON', 'mod_firepermit_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_PROJECT',  dolibarr_get_const($db,'DIGIRISKDOLIBARR_FIREPERMIT_PROJECT'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE',  dolibarr_get_const($db,'DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE'), 'integer', 0, '', $conf->entity);

	// CONST FIRE PERMIT DOCUMENT
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_FIREPERMITDOCUMENT_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON', 'mod_firepermitdocument_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/firepermitdocument/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/firepermitdocument/', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_DEFAULT_MODEL', 'firepermitdocument_odt', 'chaine', 0, '', $conf->entity);

	//CONST DIGIRISKELEMENT
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DIGIRISKELEMENT_MEDIAS_BACKWARD_COMPATIBILITY', 1, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH', dolibarr_get_const($db,'DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH'), 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH_UPDATED', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT',  dolibarr_get_const($db,'DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT'), 'integer', 0, '', $conf->entity);

	// CONST GROUPMENT
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_GROUPMENT_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_GROUPMENT_ADDON', 'mod_groupment_standard', 'chaine', 0, '', $conf->entity);

	// CONST WORKUNIT
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_WORKUNIT_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_WORKUNIT_ADDON', 'mod_workunit_standard', 'chaine', 0, '', $conf->entity);

	// CONST EVALUATOR
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_EVALUATOR_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_EVALUATOR_ADDON',  'mod_evaluator_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_EVALUATOR_DURATION',  dolibarr_get_const($db,'DIGIRISKDOLIBARR_EVALUATOR_DURATION'), 'integer', 0, '', $conf->entity);

	// CONST RISK ANALYSIS

	// CONST RISK
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_RISK_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISK_ADDON', 'mod_risk_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISK_DESCRIPTION', dolibarr_get_const($db,'DIGIRISKDOLIBARR_RISK_DESCRIPTION'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT', dolibarr_get_const($db,'DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT'), 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_MOVE_RISKS', dolibarr_get_const($db,'DIGIRISKDOLIBARR_MOVE_RISKS'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISK_DESCRIPTION_PREFILL', dolibarr_get_const($db,'DIGIRISKDOLIBARR_RISK_DESCRIPTION_PREFILL'), 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_RISKS', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SHOW_RISKS'), 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS'), 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_SHARED_RISKS', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SHOW_SHARED_RISKS'), 'integer', 0, '', $conf->entity);

	// CONST RISK ASSESSMENT
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENT_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON', 'mod_riskassessment_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD', dolibarr_get_const($db,'DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD', dolibarr_get_const($db,'DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_ALL_RISKASESSMENTS', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SHOW_ALL_RISKASESSMENTS'), 'integer', 0, '', $conf->entity);

	// CONST RISK SIGN
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_RISKSIGN_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISKSIGN_ADDON', 'mod_risksign_standard', 'chaine', 0, '', $conf->entity);

	// CONST PROJET
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 2, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DU_PROJECT', dolibarr_get_const($db,'DIGIRISKDOLIBARR_DU_PROJECT'), 'integer', 0, '', $conf->entity);

	// CONST TASK
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TASK_MANAGEMENT', dolibarr_get_const($db,'DIGIRISKDOLIBARR_TASK_MANAGEMENT'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_TASK_START_DATE', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SHOW_TASK_START_DATE'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_TASK_END_DATE', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SHOW_TASK_END_DATE'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_TASK_PROGRESS', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SHOW_TASK_PROGRESS'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SHOW_ALL_TASKS', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SHOW_ALL_TASKS'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TASK_TIMESPENT_DURATION', dolibarr_get_const($db,'DIGIRISKDOLIBARR_TASK_TIMESPENT_DURATION'), 'integer', 0, '', $conf->entity);

	// CONST PREVENTION PLAN LINE
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANDET_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLANDET_ADDON', 'mod_preventionplandet_standard', 'chaine', 0, '', $conf->entity);

	// CONST FIRE PERMIT LINE
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_FIREPERMITDET_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMITDET_ADDON', 'mod_firepermitdet_standard', 'chaine', 0, '', $conf->entity);

	// CONST MODULE
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SUBPERMCATEGORY_FOR_DOCUMENTS', 1, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_THIRDPARTY_SET', 3, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_THIRDPARTY_UPDATED', 1, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_CONTACTS_SET', 3, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_USERAPI_SET', 0, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_READERGROUP_SET', 0, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_USERGROUP_SET', 0, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ADMINUSERGROUP_SET', 0, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_READERGROUP_UPDATED', 2, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_USERGROUP_UPDATED', 3, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ADMINUSERGROUP_UPDATED', 3, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION', dolibarr_get_const($db,'DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_USE_CAPTCHA', dolibarr_get_const($db,'DIGIRISKDOLIBARR_USE_CAPTCHA'), 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ACTIVE_STANDARD', dolibarr_get_const($db,'DIGIRISKDOLIBARR_ACTIVE_STANDARD'), 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_NEW_SIGNATURE_TABLE', 1, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TRIGGERS_UPDATED', 1, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ENCODE_BACKWARD_COMPATIBILITY', 1, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM', 854, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM', 480, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE', 1280, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE', 720, 'integer', 0, '', $conf->entity);

	dolibarr_del_const($db, 'DIGIRISKDOLIBARR_DOCUMENT_MODELS_SET', $conf->entity);

	// CONST SIGNATURE
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_SIGNATURE_SHOW_COMPANY_LOGO', dolibarr_get_const($db,'DIGIRISKDOLIBARR_SIGNATURE_SHOW_COMPANY_LOGO'), 'integer', 0, '', $conf->entity);

	//CONST TICKET & REGISTERS
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', dolibarr_get_const($db,'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED', dolibarr_get_const($db,'DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', dolibarr_get_const($db,'DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE'), 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO', dolibarr_get_const($db,'DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO'), 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO', dolibarr_get_const($db,'DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO'), 'integer', 0, '', $conf->entity);

	// CONST ACCIDENT
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_ACCIDENT_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_ACCIDENT_EDIT', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ACCIDENT_ADDON', 'mod_accident_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ACCIDENT_PROJECT', dolibarr_get_const($db,'DIGIRISKDOLIBARR_ACCIDENT_PROJECT'), 'integer', 0, '', $conf->entity);

	// CONST ACCIDENT LINE
	dolibarr_set_const($db, 'MAIN_AGENDA_ACTIONAUTO_ACCIDENT_WORKSTOP_CREATE', 1, 'integer', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ACCIDENT_WORKSTOP_ADDON', 'mod_accident_workstop_standard', 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ACCIDENT_LESION_ADDON', 'mod_accident_lesion_standard', 'chaine', 0, '', $conf->entity);

	// GENERAL CONSTS
	dolibarr_set_const($db, 'MAIN_USE_EXIF_ROTATION', 1, 'integer', 0, '', $conf->entity);
	//dolibarr_set_const($db, 'MAIN_EXTRAFIELDS_USE_SELECT2', 1, 'integer', 0, '', $conf->entity);

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_CONF_BACKWARD_COMPATIBILITY', 1, 'integer', 0, '', $conf->entity);
}

if ($conf->global->DIGIRISKDOLIBARR_ENCODE_BACKWARD_COMPATIBILITY == 0) {
	$project->fetch($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT);
	$project->description = $langs->transnoentities('PreventionPlanDescription');
	$project->update($user);

	require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

	$extrafields = new ExtraFields($db);

	$extrafields->fetch_name_optionals_label('ticket');

	if ($extrafields->attributes['ticket']['label']['digiriskdolibarr_ticket_firstname'] && $extrafields->attributes['ticket']['label']['digiriskdolibarr_ticket_phone']) {
		$extrafields->update('digiriskdolibarr_ticket_firstname', $langs->transnoentities("FirstName"), 'varchar', 255, 'ticket', 0, 0, 2100, '', 1, '', 1);
		$extrafields->update('digiriskdolibarr_ticket_phone', $langs->transnoentities("Phone"), 'phone', '', 'ticket', 0, 0, 2200, '', 1, '', 1);
	}

	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', $langs->trans('SST'));
	if ($tags->id > 0) {
		$tags->label = $langs->transnoentities('SST');
		$tags->update($user);
	}

	$tags->fetch('', $langs->trans('AnticipatedLeave'));
	if ($tags->id > 0) {
		$tags->label = $langs->transnoentities('AnticipatedLeave');
		$tags->update($user);
	}

	$tags->fetch('', $langs->trans('HumanProblem'));
	if ($tags->id > 0) {
		$tags->label = $langs->transnoentities('HumanProblem');
		$tags->update($user);
	}

	$tags->fetch('', $langs->trans('MaterialProblem'));
	if ($tags->id > 0) {
		$tags->label = $langs->transnoentities('MaterialProblem');
		$tags->update($user);
	}

	$tags->fetch('', $langs->trans('EnhancementSuggestion'));
	if ($tags->id > 0) {
		$tags->label = $langs->transnoentities('EnhancementSuggestion');
		$tags->update($user);
	}

	require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
	require_once __DIR__ . '/../../class/digiriskresources.class.php';

	$societe   = new Societe($db);
	$resources = new DigiriskResources($db);
	$rights_defenderID = $resources->digirisk_dolibarr_fetch_resource('RightsDefender');
	$societe->fetch($rights_defenderID);
	$societe->name = $langs->transnoentities('RightsDefender') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$societe->update(0, $user);

	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);

	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		$usergroup->note = $langs->transnoentities('DigiriskAdminUserGroupDescription');
		$usergroup->update($user);
	}

	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_USERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		$usergroup->note = $langs->transnoentities('DigiriskUserGroupDescription');
		$usergroup->update($user);
	}

	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_READERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		$usergroup->note = $langs->transnoentities('DigiriskReaderGroupDescription');
		$usergroup->update($user);
	}

	require_once __DIR__ . '/../../class/accident.class.php';

	$accident   = new Accident($db);

	$accidents = $accident->fetchAll();
	foreach ($accidents as $accident) {
		$accident->description = dol_html_entity_decode($accident->description, ENT_QUOTES|ENT_HTML5);
		$accident->update($user);
	}

	require_once __DIR__ . '/../../class/digirisksignature.class.php';

	$signatory = new DigiriskSignature($db);

	$signatories = $signatory->fetchAll();
	foreach ($signatories as $signatory) {
		if ($signatory->signature == $langs->trans('FileGenerated')) {
			$signatory->signature = $langs->transnoentities('FileGenerated');
			$signatory->update($user);
		}
	}

	require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

	$actioncomm = new Actioncomm($db);

	$actioncomms = $actioncomm->getActions();
	if ( ! empty($actioncomms)) {
		foreach ($actioncomms as $actioncomm) {
			$actioncomm->label = dol_html_entity_decode($actioncomm->label, ENT_QUOTES|ENT_HTML5);
			$actioncomm->note_private = dol_html_entity_decode($actioncomm->note_private, ENT_QUOTES|ENT_HTML5);
			$actioncomm->update($user);
		}
	}

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ENCODE_BACKWARD_COMPATIBILITY', 1, 'integer', 0, '', $conf->entity);
}
