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

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

//Check projet
if ($conf->global->DIGIRISKDOLIBARR_DU_PROJECT > 0) {
	$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
	//Backward compatibility
	if ($project->title == $langs->trans('RiskAssessmentDocument')) {
		$project->title       = $langs->trans('RiskAssessmentDocumentInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
		$project->description = $langs->trans('RiskAssessmentDocumentDescription');
		$project->update($user);
	}
}

if ( $conf->global->DIGIRISKDOLIBARR_DU_PROJECT == 0 || $project->statut == 2 ) {

	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('RiskAssessmentDocumentInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$project->description = $langs->trans('RiskAssessmentDocumentDescription');
	$project->date_c      = dol_now();
	$currentYear          = dol_print_date(dol_now(),'%Y');
	$fiscalMonthStart     = $conf->global->SOCIETE_FISCAL_MONTH_START;
	$startdate            = dol_mktime('0','0','0',$fiscalMonthStart ? $fiscalMonthStart : '1', '1', $currentYear);
	$project->date_start  = $startdate;

	$project->usage_task = 1;

	$startdateAddYear      = dol_time_plus_duree($startdate, 1,'y');
	$startdateAddYearMonth = dol_time_plus_duree($startdateAddYear, -1,'d');
	$enddate               = dol_print_date($startdateAddYearMonth, 'dayrfc');
	$project->date_end     = $enddate;
	$project->statut      = 1;
	$project_id = $project->create($user);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DU_PROJECT', $project_id, 'integer', 1, '',$conf->entity);
}

if ($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT > 0) {
	$project->fetch($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT);
	//Backward compatibility
	if ($project->title == $langs->trans('PreventionPlan')) {
		$project->title = $langs->trans('PreventionPlanInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
		$project->update($user);
	}
}

if ( $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT == 0 || $project->statut == 2 ) {

	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('PreventionPlanInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$project->description = $langs->trans('PreventionPlanDescription');
	$project->date_c      = dol_now();
	$currentYear          = dol_print_date(dol_now(),'%Y');
	$fiscalMonthStart     = $conf->global->SOCIETE_FISCAL_MONTH_START;
	$startdate            = dol_mktime('0','0','0',$fiscalMonthStart ? $fiscalMonthStart : '1', '1', $currentYear);
	$project->date_start  = $startdate;

	$project->usage_task = 1;

	$startdateAddYear      = dol_time_plus_duree($startdate, 1,'y');
	$startdateAddYearMonth = dol_time_plus_duree($startdateAddYear, -1,'d');
	$enddate               = dol_print_date($startdateAddYearMonth, 'dayrfc');
	$project->date_end     = $enddate;
	$project->statut      = 1;
	$project_id = $project->create($user);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT', $project_id, 'integer', 1, '',$conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_PROJECT == 0 || $project->statut == 2 ) {

	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('FirePermitInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$project->description = $langs->trans('FirePermitDescription');
	$project->date_c      = dol_now();
	$currentYear          = dol_print_date(dol_now(),'%Y');
	$fiscalMonthStart     = $conf->global->SOCIETE_FISCAL_MONTH_START;
	$startdate            = dol_mktime('0','0','0',$fiscalMonthStart ? $fiscalMonthStart : '1', '1', $currentYear);
	$project->date_start  = $startdate;

	$project->usage_task = 1;

	$startdateAddYear      = dol_time_plus_duree($startdate, 1,'y');
	$startdateAddYearMonth = dol_time_plus_duree($startdateAddYear, -1,'d');
	$enddate               = dol_print_date($startdateAddYearMonth, 'dayrfc');
	$project->date_end     = $enddate;
	$project->statut      = 1;
	$project_id = $project->create($user);
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_FIREPERMIT_PROJECT', $project_id, 'integer', 1, '',$conf->entity);

	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'QHSE');

	$tags->label = 'FP';
	$tags->type = 'project';
	$tags->fk_parent = $tags->id;
	$tags->create($user);

	$tags->add_type($project, 'project');
}

if ( $conf->global->DIGIRISKDOLIBARR_USERAPI_SET ==  0 ) {
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

	$usertmp = new User($db);
	$usertmp->lastname  = 'API';
	$usertmp->firstname = 'REST';
	$usertmp->login     = 'USERAPI';
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

if ( $conf->global->DIGIRISKDOLIBARR_USERGROUP_SET ==  0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup->entity = $conf->entity;
	$usergroup->name = $langs->trans('DigiriskUserGroup');
	$usergroup->note = $langs->trans('DigiriskUserGroupDescription');

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
	}

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_USERGROUP_SET ', $usergroup_id, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_SET ==  0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup->entity = $conf->entity;
	$usergroup->name   = $langs->trans('DigiriskAdminUserGroup');
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
	}

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ADMINUSERGROUP_SET ', $usergroup_id, 'integer', 0, '', $conf->entity);
}
