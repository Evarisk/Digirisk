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

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$societe->id.'" >';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent editmode">';

print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Day").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("DigiriskDay0"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="day0" id="day0" class="minwidth100" value="'.($object->day0 ?$object->day0 : GETPOST("day0", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("DigiriskDay1"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="day1" id="day1" class="minwidth100" value="'.($object->day1 ?$object->day1  : GETPOST("day1", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("DigiriskDay2"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="day2" id="day2" class="minwidth100" value="'.($object->day2 ?$object->day2 : GETPOST("day2", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("DigiriskDay3"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="day3" id="day3" class="minwidth100" value="'.($object->day3 ?$object->day3 : GETPOST("day3", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("DigiriskDay4"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="day4" id="day4" class="minwidth100" value="'.($object->day4 ?$object->day4 : GETPOST("day4", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("DigiriskDay5"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="day5" id="day5" class="minwidth100" value="'.($object->day5 ?$object->day5 : GETPOST("day5", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("DigiriskDay6"), $langs->trans("OpeningHoursFormatDesc"));
print '</td><td>';
print '<input name="day6" id="day6" class="minwidth100" value="'.($object->day6 ?$object->day6 : GETPOST("day6", 'alpha')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
print '</div>';
print '<br>';

print '</form>';

?>
<!-- END PHP TEMPLATE digiriskdolibarr_legaldisplayfields_view.tpl.php -->
