<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * @param	DigiriskElement	$object		DigiriskElement
 * @return 	array					Array of tabs
 */
function digiriskelementPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("digiriskdolibarr@digiriskdolibarr");

	$h = 0;
	$head = array();
	if ($object->id > 0) {
		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_risk.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Risks");
		$head[$h][2] = 'elementRisk';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_signalisation.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Signalisation");
		$head[$h][2] = 'elementSignalisation';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_card.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Card") . ' ' . $object->ref;
		$head[$h][2] = 'elementCard';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_listingrisksphoto.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("ListingRisksPhoto");
		$head[$h][2] = 'elementListingrisksphoto';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_listingrisksaction.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("ListingRisksAction");
		$head[$h][2] = 'elementListingrisksaction';
		$h++;

		if (isset($object->fields['note_public']) || isset($object->fields['note_private']))
		{
			$nbNote = 0;
			if (!empty($object->note_private)) $nbNote++;
			if (!empty($object->note_public)) $nbNote++;
			$head[$h][0] = dol_buildpath('/digiriskdolibarr/digiriskelement_note.php', 1).'?id='.$object->id;
			$head[$h][1] = $langs->trans('Notes');
			if ($nbNote > 0) $head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
			$head[$h][2] = 'note';
			$h++;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$upload_dir = $conf->digiriskdolibarr->dir_output."/digiriskelement/".dol_sanitizeFileName($object->ref);
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_document.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Documents');
		if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		$head[$h][2] = 'elementDocument';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_agenda.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Events");
		$head[$h][2] = 'elementAgenda';
		$h++;

		complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiriskelement@digiriskdolibarr');

		complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiriskelement@digiriskdolibarr', 'remove');

	} else {

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_card.php", 1);
		$head[$h][1] = $langs->trans("Informations");
		$head[$h][2] = 'elementCard';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_legaldisplay.php", 1);
		$head[$h][1] = $langs->trans("LegalDisplay");
		$head[$h][2] = 'elementLegaldisplay';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/informationssharing_card.php?action=create", 1);
		$head[$h][1] = $langs->trans("InformationsSharing");
		$head[$h][2] = 'elementInformationssharing';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_listingrisksphoto.php", 1);
		$head[$h][1] = $langs->trans("ListingRisksPhoto");
		$head[$h][2] = 'elementListingrisksphoto';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_listingrisksaction.php", 1);
		$head[$h][1] = $langs->trans("ListingRisksAction");
		$head[$h][2] = 'elementListingrisksaction';
		$h++;

//		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_evaluator.php", 1);
//		$head[$h][1] = $langs->trans("Evaluator");
//		$head[$h][2] = 'evaluator';
//		$h++;

//		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_duer.php", 1);
//		$head[$h][1] = $langs->trans("ListingRisksAction");
//		$head[$h][2] = 'duer';
//		$h++;

		if (isset($object->fields['note_public']) || isset($object->fields['note_private']))
		{
			$nbNote = 0;
			if (!empty($object->note_private)) $nbNote++;
			if (!empty($object->note_public)) $nbNote++;
			$head[$h][0] = dol_buildpath('/digiriskdolibarr/digiriskelement_note.php', 1);
			$head[$h][1] = $langs->trans('Notes');
			if ($nbNote > 0) $head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
			$head[$h][2] = 'note';
			$h++;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$upload_dir = $conf->digiriskdolibarr->dir_output."/digiriskelement/".dol_sanitizeFileName($object->ref);
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_document.php", 1);
		$head[$h][1] = $langs->trans('Documents');
		if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		$head[$h][2] = 'elementDocument';
		$h++;

//		$head[$h][0] = dol_buildpath("/digiriskdolibarr/digiriskelement_agenda.php", 1);
//		$head[$h][1] = $langs->trans("Events");
//		$head[$h][2] = 'elementAgenda';
//		$h++;

		complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiriskelement@digiriskdolibarr');

		complete_head_from_modules($conf, $langs, $object, $head, $h, 'digiriskelement@digiriskdolibarr', 'remove');

	}


	return $head;
}
