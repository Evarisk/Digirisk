<?php
/* Copyright (C) 2017  Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * Need to have following variables defined:
 * $legaldisplay (invoice, order, ...)
 * $action
 * $conf
 * $langs
 *
 * $keyforbreak may be defined to key to switch on second column
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}
if (!is_object($form)) $form = new Form($db);

?>
	<!-- BEGIN PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
<?php

$legaldisplay = json_decode($legaldisplay->LegaldisplayFillJSON($legaldisplay), false, 512, JSON_UNESCAPED_UNICODE)->LegalDisplay;


//Creation User

print '<tr>';
print '<td class="titlefield">'.$langs->trans("CreatedBy").'</td>';
print '<td>';

if ($legaldisplay->fk_user_creat > 0)
{
	$usercreat = new User($db);
	$result = $usercreat->fetch($legaldisplay->fk_user_creat);
	if ($result < 0) dol_print_error('', $usercreat->error);
	elseif ($result > 0) print $usercreat->getNomUrl(-1);
}

//Creation Date
print '</td></tr>';

print '<tr>';
print '<td class="titlefield">'.$langs->trans("CreatedOn").'</td>';
print '<td>';

print dol_print_date($legaldisplay->date_creation);

print '</td></tr>';

// Médecin du travail

print '<tr>';
	print '<td class="titlefield">'.$form->textwithpicto($langs->trans("LabourDoctor"), $langs->trans('HowToSetDataLegalDisplay')).'</td>';
	print '<td>';
		print $legaldisplay->occupational_health_service->name;
		print '</td></tr>';

// Inspecteur du travail
print '<tr>';
	print '<td class="titlefield">'.$form->textwithpicto($langs->trans("LabourInspector"), $langs->trans('HowToSetDataLegalDisplay')).'</td>';
	print '<td>';
		print $legaldisplay->detective_work->name;
		print '</td></tr>';

// SAMU

print '<tr>';
	print '<td class="titlefield">'.$form->textwithpicto($langs->trans("SAMU"), $langs->trans('HowToSetDataLegalDisplay')).'</td>';
	print '<td>';
		print $legaldisplay->emergency_service->samu;
		print '</td></tr>';

// Pompiers

print '<tr>';
	print '<td class="titlefield">'.$form->textwithpicto($langs->trans("Pompiers"), $langs->trans('HowToSetDataLegalDisplay')).'</td>';

print '<td>';
		print $legaldisplay->emergency_service->pompier;
		print '</td></tr>';

// Police

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("Police"), $langs->trans('HowToSetDataLegalDisplay')).'</td>';
	print '<td>';
		print $legaldisplay->emergency_service->police;
		print '</td></tr>';

// Urgences

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("AllEmergencies"), $langs->trans('HowToSetDataLegalDisplay')).'</td>';
	print '<td>';
		print $legaldisplay->emergency_service->emergency;
		print '</td></tr>';

// Défenseur du droit du travail

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("RightsDefender"), $langs->trans('HowToSetDataLegalDisplay')).'</td>';
	print '<td>';
		print $legaldisplay->emergency_service->right_defender;
		print '</td></tr>';

// Centre Antipoison

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("PoisonControlCenter"), $langs->trans('HowToSetDataLegalDisplay')).'</td>';
	print '<td>';
		print $legaldisplay->emergency_service->poison_control_center;
		print '</td></tr>';

// Responsable de prévention

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("ResponsibleToNotify"), $langs->trans('HowToSetDataLegalDisplay')).'</td>';
	print '<td>';
		print $legaldisplay->safety_rule->responsible_for_preventing;
		print '</td></tr>';


?>
<!-- END PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
