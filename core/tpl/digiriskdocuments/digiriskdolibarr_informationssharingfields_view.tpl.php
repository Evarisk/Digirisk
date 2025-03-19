<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * or see https://www.gnu.org/
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}
if ( ! is_object($form)) $form = new Form($db);

?>
	<!-- BEGIN PHP TEMPLATE digiriskdolibarr_informationssharingfields_view.tpl -->
<?php

try {
	$document = json_decode($document->InformationsSharingFillJSON($document), false, 512, JSON_UNESCAPED_UNICODE)->InformationsSharing;
} catch (Exception $e) {
	$document->error = $e->getMessage();
	dol_syslog($e->getMessage(), LOG_INFO);
	return -1;
}

// Médecin du travail
print '<tr>';
print '<td class="titlefield"><i class="fas fa-hospital-alt"></i> ' . $form->textwithpicto($langs->trans("LabourDoctor"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';

// Check if occupational_health_service object exists and has an id property greater than 0
if (isset($document->occupational_health_service->id) && $document->occupational_health_service->id > 0) {
	$contact->fetch($document->occupational_health_service->id);
	print $contact->getNomUrl(1) . ' ';
	print '<i class="fas fa-phone"></i> '  . $document->occupational_health_service->phone;
}
print '</td></tr>';

// Inspecteur du travail
print '<tr>';
print '<td class="titlefield minwidth300"><i class="fas fa-search"></i> ' . $form->textwithpicto($langs->trans("LabourInspector"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';
// Check if detective_work and its id are defined and valid
if (isset($document->detective_work->id) && $document->detective_work->id > 0) {
	$contact->fetch($document->detective_work->id);
	print $contact->getNomUrl(1) . ' ';
	print '<i class="fas fa-phone"></i> ' . $document->detective_work->phone;
}
print '</td></tr>';

// Harassment officer more 250 employees
print '<tr>';
print '<td class="titlefield"><i class="fas fa-user"></i> ' . $form->textwithpicto($langs->trans("HarassmentOfficer"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';
if (isset($document->harassment_officer->id) && $document->harassment_officer->id > 0) {
	$user->fetch($document->harassment_officer->id);
	print $user->getNomUrl(1) . ' ';
	print '<i class="fas fa-phone"></i> ' . $document->harassment_officer->phone;
}
print '</td></tr>';

// Harassment officer CSE
print '<tr>';
print '<td class="titlefield"><i class="fas fa-user"></i> ' . $form->textwithpicto($langs->trans("HarassmentOfficerCSE"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';
if (isset($document->harassment_officer_cse->id) && $document->harassment_officer_cse->id > 0) {
	$user->fetch($document->harassment_officer_cse->id);
	print $user->getNomUrl(1) . ' ';
	print '<i class="fas fa-phone"></i> ' . $document->harassment_officer_cse->phone;
}
print '</td></tr>';

// CSE

// Date
print '<tr>';
print '<td class="titlefield"><i class="fas fa-calendar-alt"></i> ' . $form->textwithpicto($langs->trans("ElectionDateCSE"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';
print '<i class="fas fa-calendar-alt"></i> ' . dol_print_date($document->membres_du_comite_entreprise_date, 'day');
print '</td></tr>';

// Titulars
print '<tr>';
print '<td class="titlefield"><i class="fas fa-user"></i> ' . $form->textwithpicto($langs->trans("Titulars"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';
print $document->membres_du_comite_entreprise_titulairesFullName;
print '</td></tr>';

// Alternates
print '<tr>';
print '<td class="titlefield"><i class="fas fa-user"></i> ' . $form->textwithpicto($langs->trans("Alternates"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';
print $document->membres_du_comite_entreprise_suppleantsFullName;
print '</td></tr>';

// DP
//Date
print '<tr>';
print '<td class="titlefield"><i class="fas fa-calendar-alt"></i> ' . $form->textwithpicto($langs->trans("ElectionDateDP"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';
print '<i class="fas fa-calendar-alt"></i> ' . dol_print_date($document->delegues_du_personnels_date, 'day');
print '</td></tr>';

//Titulars
print '<tr>';
print '<td class="titlefield"><i class="fas fa-user"></i> ' . $form->textwithpicto($langs->trans("Titulars"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';
print $document->delegues_du_personnels_titulairesFullName;
print '</td></tr>';

//Alternates
print '<tr>';
print '<td class="titlefield"><i class="fas fa-user"></i> ' . $form->textwithpicto($langs->trans("Alternates"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';
print $document->delegues_du_personnels_suppleantsFullName;
print '</td></tr>';

?>
<!-- END PHP TEMPLATE digiriskdolibarr_informationssharingfields_view.tpl -->
