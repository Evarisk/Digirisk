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

	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'DU');
	$tags->add_type($project, 'project');
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

	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'DU');
	$tags->add_type($project, 'project');
}

if ($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT > 0) {
	$project->fetch($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT);
	//Backward compatibility
	if ($project->title == $langs->trans('PreventionPlan')) {
		$project->title = $langs->trans('PreventionPlanInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
		$project->update($user);
	}

	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'PP');
	$tags->add_type($project, 'project');
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

	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'PP');
	$tags->add_type($project, 'project');
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

	$tags->fetch('', 'FP');
	$tags->add_type($project, 'project');
}

if ( $conf->global->DIGIRISKDOLIBARR_ACCIDENT_PROJECT == 0 || $project->statut == 2 ) {

	$project->ref         = $projectRef->getNextValue($third_party, $project);
	$project->title       = $langs->trans('AccidentInitial') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
	$project->description = $langs->trans('AccidentDescription');
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
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ACCIDENT_PROJECT', $project_id, 'integer', 1, '',$conf->entity);

	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$tags = new Categorie($db);

	$tags->fetch('', 'ACC');
	$tags->add_type($project, 'project');
}

if ( $conf->global->DIGIRISKDOLIBARR_USERAPI_SET ==  0 ) {
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

	$usertmp = new User($db);
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

if ( $conf->global->DIGIRISKDOLIBARR_READERGROUP_SET ==  0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup->entity = $conf->entity;
	$usergroup->name = $conf->global->MAIN_INFO_SOCIETE_NOM . ' - ' . $langs->trans('DigiriskReaderGroup');
	$usergroup->note = $langs->trans('DigiriskReaderGroupDescription');

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
		$usergroup->addrights('', 'produit', 'lire');
		$usergroup->addrights('', 'societe', 'lire');
		$usergroup->addrights('', 'ecm', 'read');
		$usergroup->addrights('', 'ticket', 'read');
		$usergroup->addrights('', 'agenda', 'myactions');
	}
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_READERGROUP_SET', $usergroup_id, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_USERGROUP_SET ==  0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
	$usergroup->entity = $conf->entity;
	$usergroup->name = $conf->global->MAIN_INFO_SOCIETE_NOM . ' - ' . $langs->trans('DigiriskUserGroup');
	$usergroup->note = $langs->trans('DigiriskUserGroupDescription');

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

if ( $conf->global->DIGIRISKDOLIBARR_USERGROUP_UPDATED ==  0 ) {
	$usergroup_id = $conf->global->DIGIRISKDOLIBARR_USERGROUP_SET;
	if ($usergroup_id > 0) {
		$usergroup->fetch($usergroup_id);
		//Rights digiriskdolibarr
		$usergroup->addrights(4363021); //DigiriskDolibarr lire
		$usergroup->addrights(4363023); //RiskAssessmentDocument Create
		$usergroup->addrights(4363026); //LegalDisplay Create
		$usergroup->addrights(4363029); //InformationsSharing Create
		$usergroup->addrights(43630212); //FirePermit Create
		$usergroup->addrights(43630215); //Prevention Plan Create
		$usergroup->addrights(43630218); //DigiriskElement Create
		$usergroup->addrights(43630221); //Risk Create
		$usergroup->addrights(43630224); //ListingRisksAction Create
		$usergroup->addrights(43630227); //ListingRisksPhoto Create
		$usergroup->addrights(43630230); //RiskSign Create
		$usergroup->addrights(43630233); //Evaluator Create
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

if ( $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_SET ==  0 ) {
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	$usergroup = new UserGroup($db);
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
		$usergroup->addrights('', 'societe');
		$usergroup->addrights('', 'ecm');
		$usergroup->addrights('', 'ticket');
		$usergroup->addrights('', 'agenda');
		$usergroup->addrights('', 'projet');
	}

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_ADMINUSERGROUP_SET', $usergroup_id, 'integer', 0, '', $conf->entity);
}

if ( $conf->global->DIGIRISKDOLIBARR_ADMINUSERGROUP_UPDATED ==  0 ) {
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

if ($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH_UPDATED ==  0) {
	require_once __DIR__ . '/../../class/digiriskelement/groupment.class.php';

	$digiriskelement = new Groupment($db);
	$digiriskelement->fetch($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH);

	$dirforimage   = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/defaultImgGP0/';
	$original_file = 'trash-alt-solid.png';
	$dir = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/groupment/';
	$src_file = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/groupment/GP0/';
	$src_file_thumbs = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/groupment/GP0/thumbs/';

	if (!is_dir($dir)) {
		dol_mkdir($dir);
	}

	if (!is_dir($src_file)) {
		dol_mkdir($src_file);
	}

	if (!is_dir($src_file_thumbs)) {
		dol_mkdir($src_file_thumbs);
	}

	dol_copy($dirforimage.$original_file, $src_file.$original_file, 0, 0);
	dol_copy($dirforimage.'/thumbs/trash-alt-solid_mini.png', $src_file.'/thumbs/trash-alt-solid_mini.png', 0, 0);
	dol_copy($dirforimage.'/thumbs/trash-alt-solid_small.png', $src_file.'/thumbs/trash-alt-solid_small.png', 0, 0);

	$digiriskelement->photo = $original_file;
	$result = $digiriskelement->update($user);

	if ($result > 0) {
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH_UPDATED', 1, 'integer', 0, '', $conf->entity);
	}
}
