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
 * \file    lib/digiriskdolibarr_firepermit.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for DigiriskElement
 */

/**
 * Prepare array of tabs for DigiriskElement
 *
 * @param	DigiriskElement $object DigiriskElement
 * @return 	array					Array of tabs
 */
function firepermitPrepareHead($object)
{
	global $db, $langs, $conf, $user;

	$langs->load("digiriskdolibarr@digiriskdolibarr");

	$h = 0;
	$head = array();

	if ($user->rights->digiriskdolibarr->firepermit->read) {
		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/firepermit/firepermit_card.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-address-card"></i> ' . $langs->trans("Card");
		$head[$h][2] = 'firepermitCard';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/firepermit/firepermit_agenda.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-calendar"></i> ' . $langs->trans("Events");
		$head[$h][2] = 'firepermitAgenda';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/firepermit/firepermit_schedule.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-calendar-times"></i> ' . $langs->trans("Schedule");
		$head[$h][2] = 'firepermitSchedule';
		$h++;

		$head[$h][0] = dol_buildpath("/digiriskdolibarr/view/firepermit/firepermit_attendants.php", 1) . '?id=' . $object->id;
		$head[$h][1] = '<i class="fas fa-file-signature"></i> ' . $langs->trans("Attendants");
		$head[$h][2] = 'firepermitAttendants';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'firepermitdocument@digiriskdolibarr');

	return $head;
}
