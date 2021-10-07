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
	print '<td class="titlefield"><label for="AuditStartDate">' . $langs->trans("AuditStartDate") . '</label></td><td colspan="2">';
	print $form->selectDate($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE, 'AuditStartDate', '', '', '', "edit", 1, 1);
	print '</td></tr>';

	print '<tr>';
	print '<td class="titlefield"><label for="AuditStartDate">' . $langs->trans("AuditEndDate") . '</label></td><td colspan="2">';
	print $form->selectDate($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE, 'AuditEndDate', '', '', '', "edit", 1, 1);
	print '</td></tr>';

// Destinataire

	print '<tr>';
	print '<td class="titlefield"><label for="Recipient">' .$langs->trans("Recipient") . '</label></td><td colspan="2">';
	print $form->select_dolusers($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT, 'Recipient', 0, null, 0, '', '', 0, 0, 0, '', 0, '', '', 0, 0);
	print '</td></tr>';

// Méthodologie

	print '<tr>';
	print '<td class="titlefield"><label for="Method">' . $langs->trans("Method") . '</label></td>';
	print '<td>';
	$doleditor = new DolEditor('Method', $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD ? $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD : '', '', 90, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

// Sources

	print '<tr>';
	print '<td class="titlefield"><label for="Sources">' . $langs->trans("Sources") . '</label></td>';
	print '<td>';
	$doleditor = new DolEditor('Sources', $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES ? $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES : '', '', 90, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

// Remarque Importante

	print '<tr>';
	print '<td class="titlefield"><label for="ImportantNote">' . $langs->trans("ImportantNote") . '</label></td>';
	print '<td>';
	$doleditor = new DolEditor('ImportantNote', $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES ? $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES : '', '', 90, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';
	print '</table>';
	print '<div class="tabsAction" >' . "\n";
	// Modify
	print '<input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print '</form>';
	print '</div>';

// Disponibilité des plans
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">' . "\n";
	print '<tr>';
	print '<td class="titlefield"><label for="SitePlans">' . $langs->trans("SitePlans") . '</label></td>';
	print '<td>';
	// To attach new file
	if ((!empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS))) {
		print '<!-- Start form to attach new file in digiriskdolibarr_riskassessmentdocumentfields_view.tpl.php -->' . "\n";
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
		$formfile = new FormFile($db);
		$formfile->form_attach_new_file($_SERVER["PHP_SELF"], 'none', 0, 0, $permtoupload, 48, $object, '', 0, '', 0, 'DataMigrationImport', '', '', 0);
	}
	// End "Add new file" area
	print '</td></tr>';
} else {
	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("AuditStartDate") . '</td><td colspan="2">';
	print dol_print_date(strtotime($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE), '%d/%m/%Y');
	print '</td></tr>';

	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("AuditEndDate") . '</td><td colspan="2">';
	print dol_print_date(strtotime($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE), '%d/%m/%Y');
	print '</td></tr>';

// Destinataire

	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("Recipient") . '</td><td colspan="2">';
	$user->fetch($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT);
	print $user->lastname . ' ' . $user->firstname;
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
	$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/riskassessmentdocument/', "files", 0, '', '(\.odt|\.zip)', 'date', 'asc', 1);
	if (count($filearray)) : ?>
		<?php $file = array_shift($filearray); ?>
		<span class="">
			<?php print '<img class="" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode('/riskassessmentdocument/thumbs/'. preg_replace('/\./', '_small.',$file['name'])).'" >'; ?>
		</span>
	<?php else: ?>
		<?php $nophoto = DOL_URL_ROOT.'/public/theme/common/nophoto.png'; ?>
		<span class="">
			<img class="" alt="No photo" src="<?php echo $nophoto ?>">
		</span>
	<?php endif; ?>
	<?php print '</td></tr>';
	print '</table>';
	print '</div>';

	// Buttons for actions
	print '<div class="tabsAction" >' . "\n";
	// Modify
	if ($permissiontoadd) {
		print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>' . "\n";
	} else {
		print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Modify') . '</a>' . "\n";
	}
}

?>

<!-- END PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
