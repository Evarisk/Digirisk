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
 * $object (invoice, order, ...)
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
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.GETPOST('id').'" >';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="'.GETPOST('id').'">';

print '<table class="noborder centpercent editmode">';

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Day").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Monday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="monday" id="monday" class="minwidth100" value="'.($object->monday ?: GETPOST("monday", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Tuesday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="tuesday" id="tuesday" class="minwidth100" value="'.($object->tuesday ?: GETPOST("tuesday", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Wednesday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="wednesday" id="wednesday" class="minwidth100" value="'.($object->wednesday ?: GETPOST("wednesday", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Thursday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="thursday" id="thursday" class="minwidth100" value="'.($object->thursday ?: GETPOST("thursday", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Friday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="friday" id="friday" class="minwidth100" value="'.($object->friday ?: GETPOST("friday", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Saturday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="saturday" id="saturday" class="minwidth100" value="'.($object->saturday ?: GETPOST("saturday", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("Sunday"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="sunday" id="sunday" class="minwidth100" value="'.($object->sunday ?: GETPOST("sunday", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '</table>';

if ($firepermit) {
	$status = $firepermit->status;
} elseif ($preventionplan) {
	$status = $preventionplan->status;
}

if ($status < 2 && $permissiontoadd ){
	print '<br><div class="center">';
	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '</div>';
}
print '<br>';

print '</form>';

?>
<!-- END PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
