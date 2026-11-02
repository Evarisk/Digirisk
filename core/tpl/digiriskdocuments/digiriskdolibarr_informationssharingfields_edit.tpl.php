<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/digiriskdocuments/digiriskdolibarr_informationssharingfields_edit.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Edit rows of the company social data shown on the information sharing page
 */

/**
 * The following vars must be defined:
 * Global   : $conf, $langs
 * Variable : $form, $formName, $allLinks (DigiriskResources::fetchDigiriskResources())
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

require __DIR__ . '/digiriskdolibarr_labourfields_edit.tpl.php';

foreach (['HarassmentOfficer', 'HarassmentOfficerCSE'] as $harassmentOfficer) {
	print '<tr>';
	print '<td class="titlefield"><i class="fas fa-user"></i> ' . $langs->trans($harassmentOfficer) . '</td>';
	print '<td>' . img_picto('', 'user', 'class="pictofixedwidth"') . $form->select_dolusers($allLinks[$harassmentOfficer]->id[0] ?? 0, $harassmentOfficer, 1, null, 0, '', '', $conf->entity, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300 maxwidth500 widthcentpercentminusx') . '</td>';
	print '</tr>';
}

// Staff representatives: the ESC (CSE) then the staff delegates (DP)
foreach (['CSE', 'DP'] as $staffBody) {
	$electionDate = getDolGlobalString('DIGIRISKDOLIBARR_' . $staffBody . '_ELECTION_DATE');

	print '<tr>';
	print '<td class="titlefield"><i class="fas fa-calendar-alt"></i> ' . $langs->trans('ElectionDate' . $staffBody) . '</td>';
	print '<td>' . $form->selectDate(strtotime($electionDate) ? $electionDate : -1, 'ElectionDate' . $staffBody, 0, 0, 1, $formName, 1, 1) . '</td>';
	print '</tr>';

	foreach (['Titulars', 'Alternates'] as $memberType) {
		$resourceRef = $memberType . $staffBody;

		print '<tr>';
		print '<td class="titlefield"><i class="fas fa-user"></i> ' . $langs->trans($memberType) . '</td>';
		print '<td>' . img_picto('', 'user', 'class="pictofixedwidth"') . $form->select_dolusers($allLinks[$resourceRef]->id ?? [], $resourceRef, 0, null, 0, '', '', $conf->entity, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300 maxwidth500 widthcentpercentminusx', 0, 0, true) . '</td>';
		print '</tr>';
	}
}
