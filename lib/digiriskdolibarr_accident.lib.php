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
 * \file    lib/digiriskdolibarr_accident.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for Accident
 */

/**
 * Prepare array of tabs for Accident
 *
 * @param	Accident $object Accident
 * @return 	array					Array of tabs
 */
function accidentPrepareHead($object)
{
	global $db, $langs, $conf, $user;

	$langs->load("digiriskdolibarr@digiriskdolibarr");

	$h = 0;
	$head = array();

	if ($user->rights->digiriskdolibarr->accident->read) {
		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/accident/accident_card.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-address-card"></i> ' . $langs->trans("Card");
		$head[$h][2] = 'accidentCard';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/accident/accident_metadata.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-info-circle"></i> ' . $langs->trans("AccidentMetaData");
		$head[$h][2] = 'accidentMetadata';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/accident/accident_metadata_lesion.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-info-circle"></i> ' . $langs->trans("AccidentMetaDataLesion");
		$head[$h][2] = 'accidentMetadataLesion';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/accident/accident_attendants.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-file-signature"></i> ' . $langs->trans("Signature");
		$head[$h][2] = 'accidentAttendants';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/accident/accident_document.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-file-alt"></i> ' . $langs->trans("Documents");
		$head[$h][2] = 'accidentDocument';
		$h++;

//		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/accident/accident_document_metadata.php", 1) . '?id=' . $object->id;
//		$head[$h][1] = '<i class="fas fa-file-alt"></i> ' . $langs->trans("DocumentsMetaData");
//		$head[$h][2] = 'accidentDocumentMetaData';
//		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/accident/accident_agenda.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-calendar"></i> ' . $langs->trans("Events");
		$head[$h][2] = 'accidentAgenda';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'accident@digiriskdolibarr');

	return $head;
}
