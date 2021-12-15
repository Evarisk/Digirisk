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
 * \file    lib/digiriskdolibarr_digiriskstandard.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for DigiriskStandard
 */

/**
 * Prepare array of tabs for DigiriskStandard
 *
 * @param	DigiriskStandard	$object		DigiriskStandard
 * @return 	array					Array of tabs
 */
function digiriskstandardPrepareHead($object)
{
	global $langs, $conf, $user;

	$langs->load("digiriskdolibarr@digiriskdolibarr");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php", 1). '?id=' . $object->id;
	$head[$h][1] = '<i class="fas fa-info-circle"></i> ' . $langs->trans("Informations");
	$head[$h][2] = 'standardCard';
	$h++;

	if ($user->rights->digiriskdolibarr->legaldisplay->read) {
		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskstandard/digiriskstandard_legaldisplay.php", 1). '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-file"></i> ' . $langs->trans("LegalDisplay");
		$head[$h][2] = 'standardLegalDisplay';
		$h++;
	}

	if ($user->rights->digiriskdolibarr->informationssharing->read) {
		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskstandard/digiriskstandard_informationssharing.php", 1). '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-comment-dots"></i> ' . $langs->trans("InformationsSharing");
		$head[$h][2] = 'standardInformationsSharing';
		$h++;
	}

	if ($user->rights->digiriskdolibarr->listingrisksaction->read) {
		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskelement/digiriskelement_listingrisksaction.php", 1). '?id=' . $object->id . '&type=standard';
		$head[$h][1] = '<i class="fas fa-exclamation"></i> ' . $langs->trans("ListingRisksAction");
		$head[$h][2] = 'elementListingRisksAction';
		$h++;
	}

	if ($user->rights->digiriskdolibarr->listingrisksphoto->read) {
		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskelement/digiriskelement_listingrisksphoto.php", 1). '?id=' . $object->id . '&type=standard';
		$head[$h][1] = '<i class="fas fa-images"></i> ' . $langs->trans("ListingRisksPhoto");
		$head[$h][2] = 'elementListingRisksPhoto';
		$h++;
	}

	if ($user->rights->digiriskdolibarr->riskassessmentdocument->read) {
		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskstandard/digiriskstandard_riskassessmentdocument.php", 1). '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-file-alt"></i> ' . $langs->trans("RiskAssessmentDocument");
		$head[$h][2] = 'standardRiskAssessmentDocument';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskstandard/digiriskstandard_agenda.php", 1). '?id=' . $object->id;
	$head[$h][1] = '<i class="fas fa-calendar"></i> ' . $langs->trans("Events");
	$head[$h][2] = 'standardAgenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiriskstandard@digiriskdolibarr');

	return $head;
}
