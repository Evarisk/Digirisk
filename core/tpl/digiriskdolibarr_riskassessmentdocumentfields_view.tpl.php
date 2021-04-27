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

// Date d'audit
if ( $action == "edit" && $permissiontoadd ) {

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="edit">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<tr>';
	print '<td class="titlefield"><label for="AuditStartDate">' . $form->textwithpicto($langs->trans("AuditStartDate"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</label></td><td colspan="2">';
	print $form->selectDate($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE, 'AuditStartDate', '', '', '', "edit", 1, 1);
	print '</td></tr>';

	print '<tr>';
	print '<td class="titlefield"><label for="AuditStartDate">' . $form->textwithpicto($langs->trans("AuditEndDate"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</label></td><td colspan="2">';
	print $form->selectDate($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE, 'AuditEndDate', '', '', '', "edit", 1, 1);
	print '</td></tr>';

// Destinataire

	print '<tr>';
	print '<td class="titlefield"><label for="Recipient">' . $form->textwithpicto($langs->trans("Recipient"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</label></td><td colspan="2">';
	print $form->select_dolusers($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT, 'Recipient', 0, null, 0, '', '', 0, 0, 0, '', 0, '', '', 0, 0);
	print '</td></tr>';

// Méthodologie

	print '<tr>';
	print '<td class="titlefield"><label for="Method">' . $form->textwithpicto($langs->trans("Method"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</label></td>';
	print '<td>';
	print '<textarea name="Method" id="Method" class="minwidth300" rows="'.ROWS_3.'">'.$conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD.'</textarea>';
	print '</td></tr>';

// Sources

	print '<tr>';
	print '<td class="titlefield"><label for="Sources">' . $form->textwithpicto($langs->trans("Sources"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</label></td>';
	print '<td>';
	print '<textarea name="Sources" id="Sources" class="minwidth300" rows="'.ROWS_3.'">'.$conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES.'</textarea>';
	print '</td></tr>';

// Remarque Importante

	print '<tr>';
	print '<td class="titlefield"><label for="ImportantNote">' . $form->textwithpicto($langs->trans("ImportantNote"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</label></td>';
	print '<td>';
	print '<textarea name="ImportantNote" id="ImportantNote" class="minwidth300" rows="'.ROWS_3.'">'.$conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTE.'</textarea>';
	print '</td></tr>';

// Disponibilité des plans

	print '<tr>';
	print '<td class="titlefield"><label for="SitePlans">' . $form->textwithpicto($langs->trans("SitePlans"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</label></td>';
	print '<td>';
	print '<textarea name="SitePlans" id="SitePlans" class="minwidth300" rows="'.ROWS_3.'">'.$conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SITE_PLANS.'</textarea>';
	print '</td></tr>';

} else {
	print '<tr>';
	print '<td class="titlefield">' . $form->textwithpicto($langs->trans("AuditStartDate"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</td><td colspan="2">';
	print dol_print_date(strtotime($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE), '%d/%m/%Y');
	print '</td></tr>';

	print '<tr>';
	print '<td class="titlefield">' . $form->textwithpicto($langs->trans("AuditEndDate"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</td><td colspan="2">';
	print dol_print_date(strtotime($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE), '%d/%m/%Y');
	print '</td></tr>';

// Destinataire

	print '<tr>';
	print '<td class="titlefield">' . $form->textwithpicto($langs->trans("Recipient"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</td><td colspan="2">';
	$user->fetch($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT);
	print $user->lastname . ' ' . $user->firstname;
	print '</td></tr>';

// Méthodologie

	print '<tr>';
	print '<td class="titlefield">' . $form->textwithpicto($langs->trans("Method"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</td>';
	print '<td>';
	print $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD;
	print '</td></tr>';

// Sources

	print '<tr>';
	print '<td class="titlefield">' . $form->textwithpicto($langs->trans("Sources"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</td>';
	print '<td>';
	print $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES;
	print '</td></tr>';

// Remarque Importante

	print '<tr>';
	print '<td class="titlefield">' . $form->textwithpicto($langs->trans("ImportantNote"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</td>';
	print '<td>';
	print $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTE;
	print '</td></tr>';

// Disponibilité des plans

	print '<tr>';
	print '<td class="titlefield">' . $form->textwithpicto($langs->trans("SitePlans"), $langs->trans('HowToSetDataRiskAssessmentDocument')) . '</td>';
	print '<td>';
	print $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SITE_PLANS;
	print '</td></tr>';
}
?>

<!-- END PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
