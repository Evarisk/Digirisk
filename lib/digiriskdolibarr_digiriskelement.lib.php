<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiriskdolibarr_digiriskelement.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for DigiriskElement
 */

/**
 * Prepare array of tabs for DigiriskElement
 *
 * @param	DigiriskElement $object DigiriskElement
 * @return 	array					Array of tabs
 */
function digiriskelement_prepare_head($object)
{
	global $langs, $conf, $user;

	saturne_load_langs();

	$h    = 0;
	$head = array();
	if ($object->id > 0) {
		if ($user->rights->digiriskdolibarr->risk->read) {
			$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php", 1) . '?id=' . $object->id;
			$head[$h][1] = '<i class="fas fa-exclamation-triangle"></i> ' . $langs->trans("Risks");
			$head[$h][2] = 'elementRisk';
			$h++;
		}

		if ($user->rights->digiriskdolibarr->evaluator->read) {
			$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskelement/digiriskelement_evaluator.php", 1) . '?id=' . $object->id;
			$head[$h][1] = '<i class="fas fa-user-check"></i> ' . $langs->trans("Evaluators");
			$head[$h][2] = 'elementEvaluator';
			$h++;
		}

		if ($user->rights->digiriskdolibarr->risksign->read) {
			$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskelement/digiriskelement_risksign.php", 1) . '?id=' . $object->id;
			$head[$h][1] = '<i class="fas fa-map-signs"></i> ' . $langs->trans("RiskSigns");
			$head[$h][2] = 'elementRiskSign';
			$h++;
		}

		if ($user->rights->digiriskdolibarr->accident->read) {
			$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/accident/accident_list.php", 1) . '?fromid=' . $object->id;
			$head[$h][1] = '<i class="fas fa-user-injured"></i> ' . $langs->trans("Accidents");
			$head[$h][2] = 'elementAccidents';
			$h++;
		}

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-info-circle"></i> ' . $langs->trans("Card");
		$head[$h][2] = 'elementCard';
		$h++;

		if ($object->element_type == 'groupment') {
			if ($user->rights->digiriskdolibarr->listingrisksaction->read) {
				$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskelement/digiriskelement_listingrisksaction.php", 1) . '?id=' . $object->id;
				$head[$h][1] = '<i class="fas fa-exclamation"></i> ' . $langs->trans("ListingRisksAction");
				$head[$h][2] = 'elementListingRisksAction';
				$h++;
			}

			if ($user->rights->digiriskdolibarr->listingrisksphoto->read) {
				$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskelement/digiriskelement_listingrisksphoto.php", 1) . '?id=' . $object->id;
				$head[$h][1] = '<i class="fas fa-images"></i> ' . $langs->trans("ListingRisksPhoto");
				$head[$h][2] = 'elementListingRisksPhoto';
				$h++;
			}
		}

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskelement/digiriskelement_agenda.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-calendar"></i> ' . $langs->trans("Events");
		$head[$h][2] = 'elementAgenda';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/digiriskelement/digiriskelement_register.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fa fa-ticket"></i> ' . $langs->trans("Registers");
		$head[$h][2] = 'elementRegister';
		$h++;

		complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiriskelement@digiriskdolibarr');
	}
	return $head;
}
