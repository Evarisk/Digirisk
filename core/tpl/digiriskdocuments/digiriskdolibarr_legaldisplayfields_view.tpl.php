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
	<!-- BEGIN PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
<?php

try {
	$document = json_decode($document->LegalDisplayFillJSON(), false, 512, JSON_UNESCAPED_UNICODE)->LegalDisplay;
} catch (Exception $e) {
	$document->error = $e->getMessage();
	dol_syslog($e->getMessage(), LOG_INFO);
	return -1;
}

// Médecin du travail
print '<tr>';
print '<td class="titlefield"><i class="fas fa-hospital-alt"></i> ' . $form->textwithpicto($langs->trans("LabourDoctor"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';
if (isset($document->occupational_health_service->id) && $document->occupational_health_service->id > 0) {
	$contact->fetch($document->occupational_health_service->id);
	print $contact->getNomUrl(1);
}
print '</td></tr>';

// Inspecteur du travail
print '<tr>';
print '<td class="titlefield"><i class="fas fa-search"></i> ' . $form->textwithpicto($langs->trans("LabourInspector"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';
if (isset($document->detective_work->id) && $document->detective_work->id > 0) {
	$contact->fetch($document->detective_work->id);
	print $contact->getNomUrl(1);
}
print '</td></tr>';

// SAMU
print '<tr>';
print '<td class="titlefield"><i class="fas fa-hospital-alt"></i> ' . $form->textwithpicto($langs->trans("SAMU"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';
print '<i class="fas fa-phone"></i> ' . $document->emergency_service->samu;
print '</td></tr>';

// Pompiers
print '<tr>';
print '<td class="titlefield"><i class="fas fa-ambulance"></i> ' . $form->textwithpicto($langs->trans("Pompiers"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';
print '<i class="fas fa-phone"></i> ' . $document->emergency_service->pompier;
print '</td></tr>';

// Police
print '<tr>';
print '<td class="titlefield"><i class="fas fa-car"></i> ' . $form->textwithpicto($langs->trans("Police"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';
print '<i class="fas fa-phone"></i> ' . $document->emergency_service->police;
print '</td></tr>';

// Urgences
print '<tr>';
print '<td class="titlefield"><i class="fas fa-phone"></i> ' . $form->textwithpicto($langs->trans("AllEmergencies"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';
print '<i class="fas fa-phone"></i> ' . $document->emergency_service->emergency;
print '</td></tr>';

// Défenseur du droit du travail
print '<tr>';
print '<td class="titlefield"><i class="fas fa-gavel"></i> ' . $form->textwithpicto($langs->trans("RightsDefender"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';
print '<i class="fas fa-phone"></i> ' . $document->emergency_service->right_defender;
print '</td></tr>';

// Centre Antipoison
print '<tr>';
print '<td class="titlefield"><i class="fas fa-skull-crossbones"></i> ' . $form->textwithpicto($langs->trans("PoisonControlCenter"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';
print '<i class="fas fa-phone"></i> ' . $document->emergency_service->poison_control_center;
print '</td></tr>';

// Responsable de prévention
print '<tr>';
print '<td class="titlefield"><i class="fas fa-user"></i> ' . $form->textwithpicto($langs->trans("ResponsibleToNotify"), $langs->trans('HowToSetDataLegalDisplay')) . '</td>';
print '<td>';
if (isset($document->safety_rule->id)) {
    $usertmp->fetch($document->safety_rule->id);
    print $usertmp->getNomUrl(1);
}
print '</td></tr>';

?>
<!-- END PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
