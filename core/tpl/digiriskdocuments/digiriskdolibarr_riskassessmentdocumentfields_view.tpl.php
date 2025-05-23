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
if (empty($conf) || ! is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}
if ( ! is_object($form)) $form = new Form($db);

?>
	<!-- BEGIN PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
<?php

// Date d'audit
if ( $action == "edit" && $permissiontoadd ) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

	print '<tr>';
	print '<td class="titlefield"><label for="AuditStartDate">' . $langs->trans("AuditStartDate") . '</label></td><td colspan="2">';
	print $form->selectDate($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE, 'AuditStartDate', '', '', '', "edit", 1, 1);
	print '</td></tr>';

	print '<tr>';
	print '<td class="titlefield"><label for="AuditStartDate">' . $langs->trans("AuditEndDate") . '</label></td><td colspan="2">';
	print $form->selectDate($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE, 'AuditEndDate', '', '', '', "edit", 1, 1);
	print '</td></tr>';

	// Destinataire
	$userRecipient = json_decode($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT);
	print '<tr>';
	print '<td class="titlefield"><label for="Recipient">' . $langs->trans("Recipient") . '</label></td><td colspan="2">';
	print $form->select_dolusers($userRecipient, 'Recipient', 0, null, 0, '', '', 0, 0, 0, '', 0, '', '', 0, 0, true);
	print '</td></tr>';

	// Méthodologie
	print '<tr>';
	print '<td class="titlefield"><label for="Method">' . $langs->trans("Method") . '</label></td>';
	print '<td>';
	$doleditor = new DolEditor('Method', $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD ? $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD : '', '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// Sources
	print '<tr>';
	print '<td class="titlefield"><label for="Sources">' . $langs->trans("Sources") . '</label></td>';
	print '<td>';
	$doleditor = new DolEditor('Sources', $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES ? $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES : '', '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// Remarque Importante
	print '<tr>';
	print '<td class="titlefield"><label for="ImportantNote">' . $langs->trans("ImportantNote") . '</label></td>';
	print '<td>';
	$doleditor = new DolEditor('ImportantNote', $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES ? $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES : '', '', 90, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// Disponibilité des plans
	print '<tr>';
	print '<td class="titlefield">' . $form->editfieldkey($langs->trans("SitePlans"), 'SitePlans', '', $object, 0) . '</td>';
	print '<td>';
    print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessmentdocument/siteplans', 'small', '', 0, 0, 0, 200, 200, 0, 0, 0, 'riskassessmentdocument/siteplans', null, '', 0, 0);
	print '<input class="flat" type="file" name="userfile[]" accept="image/*" id="SitePlans" />';
	print '</td></tr>';
} else {
    $userTmp = new User($db);

	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("AuditStartDate") . '</td><td colspan="2">';
	print dol_print_date($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE, '%d/%m/%Y');
	print '</td></tr>';

	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("AuditEndDate") . '</td><td colspan="2">';
	print dol_print_date($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE, '%d/%m/%Y');
	print '</td></tr>';

	// Destinataire
	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("Recipient") . '</td><td colspan="2">';
	$recipients = json_decode($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT);
	if (is_array($recipients) && !empty($recipients)) {
        foreach ($recipients as $recipientId) {
            $userTmp->fetch($recipientId);
            print $userTmp->getNomUrl(1) . '<br>';
        }
    }
	print '</td></tr>';

	// Méthodologie
	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("Method") . '</td>';
	print '<td>';
	print $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD;
	print '</td></tr>';

	// Sources
	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("Sources") . '</td>';
	print '<td>';
	print $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES;
	print '</td></tr>';

	// Remarque Importante
	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("ImportantNote") . '</td>';
	print '<td>';
	print $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES;
	print '</td></tr>';

	// Disponibilité des plans
	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("SitePlans") . '</td>';
	print '<td>';
    print saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessmentdocument/siteplans', 'small', '', 0, 0, 0, 100, 100, 0, 0, 0, 'riskassessmentdocument/siteplans', null, '', 0, 0);
    print '</td></tr>';
}

?>

<!-- END PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
