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
 * \file    core/tpl/digiriskdocuments/digiriskdolibarr_labourfields_edit.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Edit rows of the occupational physician and the labour inspector, shared by the legal display and information sharing pages
 */

/**
 * The following vars must be defined:
 * Global   : $conf, $langs
 * Variable : $form, $allLinks (DigiriskResources::fetchDigiriskResources())
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

$labourServices = [
	'LabourDoctor'    => 'fa-hospital-alt',
	'LabourInspector' => 'fa-search',
];

foreach ($labourServices as $labourService => $labourServiceIcon) {
	$societyRef = $labourService . 'Society';
	$contactRef = $labourService . 'Contact';
	$socId      = $allLinks[$societyRef]->id[0] ?? 0;
	// Choosing another third party reloads the list of its contacts
	$events = [['method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => $contactRef, 'params' => ['add-customer-contact' => 'disabled']]];

	print '<tr>';
	print '<td class="titlefield tdtop"><i class="fas ' . $labourServiceIcon . '"></i> ' . $langs->trans($labourService) . '</td>';
	print '<td>';
	print '<div>' . img_picto('', 'company', 'class="pictofixedwidth"') . $form->select_company($socId, $societyRef, '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300 maxwidth500 widthcentpercentminusx') . '</div>';
	print '<div class="margintoponlyshort">' . img_picto('', 'contact', 'class="pictofixedwidth"') . $form->selectcontacts($socId > 0 ? $socId : -1, $allLinks[$contactRef]->id ?? [], $contactRef . '[]', 0, '', '', 0, 'minwidth300 maxwidth500 widthcentpercentminusx', false, 0, [], false, 'multiple', $contactRef) . '</div>';
	print '</td></tr>';
}
