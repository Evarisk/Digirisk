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

/**
 * \file    lib/digiriskdolibarr.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for Digiriskdolibarr
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function digiriskdolibarrAdminPrepareHead()
{
	global $langs, $conf, $user;

	$langs->load("digiriskdolibarr@digiriskdolibarr");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/config/riskassessmentdocument.php", 1);
	$head[$h][1] = '<i class="fas fa-exclamation-triangle"></i>  ' . $langs->trans("RiskAssessmentDocument");
	$head[$h][2] = 'riskassessmentdocument';
	$h++;

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/config/digiriskelement.php", 1);
	$head[$h][1] = '<i class="fas fa-network-wired"></i>  ' . $langs->trans("Arborescence");
	$head[$h][2] = 'digiriskelement';
	$h++;

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/config/preventionplan.php", 1);
	$head[$h][1] = '<i class="fas fa-info"></i>  ' . $langs->trans("PreventionPlan");
	$head[$h][2] = 'preventionplan';
	$h++;

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/config/firepermit.php", 1);
	$head[$h][1] = '<i class="fas fa-fire-alt"></i>  ' . $langs->trans("FirePermit");
	$head[$h][2] = 'firepermit';
	$h++;

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/config/accident.php", 1);
	$head[$h][1] = '<i class="fas fa-user-injured"></i>  ' . $langs->trans("Accident");
	$head[$h][2] = 'accident';
	$h++;

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/config/digiriskdocuments.php", 1);
	$head[$h][1] = '<i class="fas fa-file-alt"></i> ' . $langs->trans("YourDocuments");
	$head[$h][2] = 'digiriskdocuments';
	$h++;

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/config/digirisksignature.php", 1);
	$head[$h][1] = '<i class="fas fa-file-signature"></i> ' . $langs->trans("DigiriskSignature");
	$head[$h][2] = 'digirisksignature';
	$h++;

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/setup.php", 1);
	$head[$h][1] = '<i class="fas fa-cog"></i>  ' . $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/config/about.php", 1);
	$head[$h][1] = '<i class="fab fa-readme"></i> ' . $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

//	if ($user->admin) {
//		$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/ticket/ticket.php", 1);
//		$head[$h][1] = $langs->trans("Tickets");
//		$head[$h][2] = 'ticket';
//		$h++;
//	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'digiriskdolibarr');

	return $head;
}
//
///**
// * Prepare admin pages subheader documents
// *
// * @return array
// */
//function digiriskdolibarrAdminDigiriskDocumentsPrepareHead()
//{
//	global $langs, $conf;
//
//	$langs->load("digiriskdolibarr@digiriskdolibarr");
//
//	$h = 0;
//	$head = array();
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskdocuments/legaldisplay.php", 1);
//	$head[$h][1] = $langs->trans("LegalDisplay");
//	$head[$h][2] = 'legaldisplay';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskdocuments/informationssharing.php", 1);
//	$head[$h][1] = $langs->trans("InformationsSharing");
//	$head[$h][2] = 'informationssharing';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskdocuments/listingrisksaction.php", 1);
//	$head[$h][1] = $langs->trans("ListingRisksAction");
//	$head[$h][2] = 'listingrisksaction';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskdocuments/listingrisksphoto.php", 1);
//	$head[$h][1] = $langs->trans("ListingRisksPhoto");
//	$head[$h][2] = 'listingrisksphoto';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskdocuments/riskassessmentdocument.php", 1);
//	$head[$h][1] = $langs->trans("RiskAssessmentDocument");
//	$head[$h][2] = 'riskassessmentdocument';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskdocuments/groupmentdocument.php", 1);
//	$head[$h][1] = $langs->trans("GroupmentDocument");
//	$head[$h][2] = 'groupmentdocument';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskdocuments/workunitdocument.php", 1);
//	$head[$h][1] = $langs->trans("WorkUnitDocument");
//	$head[$h][2] = 'workunitdocument';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskdocuments/preventionplandocument.php", 1);
//	$head[$h][1] = $langs->trans("PreventionPlanDocument");
//	$head[$h][2] = 'preventionplandocument';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskdocuments/firepermitdocument.php", 1);
//	$head[$h][1] = $langs->trans("FirePermitDocument");
//	$head[$h][2] = 'firepermitdocument';
//	$h++;
//
//	complete_head_from_modules($conf, $langs, null, $head, $h, 'digiriskdolibarr');
//
//	return $head;
//}
//
///**
// * Prepare admin pages subheader elements
// *
// * @return array
// */
//function digiriskdolibarrAdminDigiriskElementPrepareHead()
//{
//	global $langs, $conf;
//
//	$langs->load("digiriskdolibarr@digiriskdolibarr");
//
//	$h = 0;
//	$head = array();
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskelement/groupment.php", 1);
//	$head[$h][1] = $langs->trans("Groupment");
//	$head[$h][2] = 'groupment';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskelement/workunit.php", 1);
//	$head[$h][1] = $langs->trans("WorkUnit");
//	$head[$h][2] = 'workunit';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskelement/evaluator.php", 1);
//	$head[$h][1] = $langs->trans("Evaluator");
//	$head[$h][2] = 'evaluator';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskelement/preventionplan.php", 1);
//	$head[$h][1] = $langs->trans("PreventionPlan");
//	$head[$h][2] = 'preventionplan';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskelement/firepermit.php", 1);
//	$head[$h][1] = $langs->trans("FirePermit");
//	$head[$h][2] = 'firepermit';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskelement/accident.php", 1);
//	$head[$h][1] = $langs->trans("Accident");
//	$head[$h][2] = 'accident';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/digiriskelement/deletedelements.php", 1);
//	$head[$h][1] = $langs->trans("HiddenElements");
//	$head[$h][2] = 'deletedelements';
//	$h++;
//
//	complete_head_from_modules($conf, $langs, null, $head, $h, 'digiriskdolibarr');
//
//	return $head;
//}
//
///**
// * Prepare admin pages subheader risk analysis
// *
// * @return array
// */
//function digiriskdolibarrAdminRiskAnalysisPrepareHead()
//{
//	global $langs, $conf;
//
//	$langs->load("digiriskdolibarr@digiriskdolibarr");
//
//	$h = 0;
//	$head = array();
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/riskanalysis/risk.php", 1);
//	$head[$h][1] = $langs->trans("Risk");
//	$head[$h][2] = 'risk';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/riskanalysis/riskassessment.php", 1);
//	$head[$h][1] = $langs->trans("RiskAssessment");
//	$head[$h][2] = 'riskassessment';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/riskanalysis/risksign.php", 1);
//	$head[$h][1] = $langs->trans("RiskSign");
//	$head[$h][2] = 'risksign';
//	$h++;
//
//	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/riskanalysis/task.php", 1);
//	$head[$h][1] = $langs->trans("Task");
//	$head[$h][2] = 'task';
//	$h++;
//
//	complete_head_from_modules($conf, $langs, null, $head, $h, 'digiriskdolibarr');
//
//	return $head;
//}
