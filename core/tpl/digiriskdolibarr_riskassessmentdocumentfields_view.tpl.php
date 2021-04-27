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
 * $riskassessmentdocument (invoice, order, ...)
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

$riskassessmentdocument = json_decode($riskassessmentdocument->riskAssessmentDocumentFillJSON($riskassessmentdocument), false, 512, JSON_UNESCAPED_UNICODE)->RiskAssessmentDocument;

// Médecin du travail

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("AuditStartDate"), $langs->trans('HowToSetDataRiskAssessmentDocument')).'</td><td colspan="2">';
print $form->selectDate($riskassessmentdocument->dateAudit, '', '', '', '', "add", 1, 1);
print '</td></tr>';

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("AuditEndDate"), $langs->trans('HowToSetDataRiskAssessmentDocument')).'</td><td colspan="2">';
print $form->selectDate($riskassessmentdocument->dateAudit, '', '', '', '', "add", 1, 1);
print '</td></tr>';

// Inspecteur du travail
print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("Recipient"), $langs->trans('HowToSetDataRiskAssessmentDocument')).'</td><td colspan="2">';
print $form->select_dolusers();
print '</td></tr>';

// SAMU

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("Method"), $langs->trans('HowToSetDataRiskAssessmentDocument')).'</td>';
print '<td>';
print $langs->trans($riskassessmentdocument->methodologie.'Card');
print '</td></tr>';

// Pompiers

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("Sources"), $langs->trans('HowToSetDataRiskAssessmentDocument')).'</td>';
print '<td>';
print $langs->trans($riskassessmentdocument->sources.'Card');
print '</td></tr>';

// Police

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("ImportantNote"), $langs->trans('HowToSetDataRiskAssessmentDocument')).'</td>';
print '<td>';
print $langs->trans($riskassessmentdocument->remarqueImportante);
print '</td></tr>';

// Urgences

print '<tr>';
print '<td class="titlefield">'.$form->textwithpicto($langs->trans("SitePlans"), $langs->trans('HowToSetDataRiskAssessmentDocument')).'</td>';
print '<td>';
print $langs->trans($riskassessmentdocument->dispoDesPlans);
print '</td></tr>';

?>
<!-- END PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
