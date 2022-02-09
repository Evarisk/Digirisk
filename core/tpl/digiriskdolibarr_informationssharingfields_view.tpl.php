<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
	$informationssharing = json_decode($informationssharing->InformationsSharingFillJSON($informationssharing), false, 512, JSON_UNESCAPED_UNICODE)->InformationsSharing;
} catch (Exception $e) {
	$informationssharing->error = $e->getMessage();
	dol_syslog($e->getMessage(), LOG_INFO);
	return -1;
}

// CSE

//Date
print '<tr>';
print '<td class="titlefield">' . $form->textwithpicto($langs->trans("ElectionDateCSE"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';

print $informationssharing->membres_du_comite_entreprise_date;
print '</td></tr>';

print '</td></tr>';

//Titulars
print '<tr>';
print '<td class="titlefield">' . $form->textwithpicto($langs->trans("Titulars"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';

print $informationssharing->membres_du_comite_entreprise_titulaires;
print '</td></tr>';

print '</td></tr>';

//Alternates
print '<tr>';
print '<td class="titlefield">' . $form->textwithpicto($langs->trans("Alternates"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';

print $informationssharing->membres_du_comite_entreprise_suppleants;
print '</td></tr>';

print '</td></tr>';

// DP

//Date
print '<tr>';
print '<td class="titlefield">' . $form->textwithpicto($langs->trans("ElectionDateDP"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';

print $informationssharing->delegues_du_personnels_date;
print '</td></tr>';

print '</td></tr>';

//Titulars
print '<tr>';
print '<td class="titlefield">' . $form->textwithpicto($langs->trans("Titulars"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';

print $informationssharing->delegues_du_personnels_titulaires;
print '</td></tr>';

print '</td></tr>';

//Alternates
print '<tr>';
print '<td class="titlefield">' . $form->textwithpicto($langs->trans("Alternates"), $langs->trans('HowToSetDataInformationsSharing')) . '</td>';
print '<td>';

print $informationssharing->delegues_du_personnels_suppleants;
print '</td></tr>';

print '</td></tr>';

?>
<!-- END PHP TEMPLATE digiriskdolibarr_informationssharingfields_view.tpl -->
