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
