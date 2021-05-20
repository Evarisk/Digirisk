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
<!-- BEGIN PHP TEMPLATE digiriskdolibarr_preventionplanfields_view.tpl -->
<?php
if ($action == '') {

	$preventionplan = json_decode($object->json, false, 512, JSON_UNESCAPED_UNICODE)->PreventionPlan;

//Creation User

	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("CreatedBy").'</td>';
	print '<td>';

	if ($object->fk_user_creat > 0)
	{
		$usercreat = new User($db);
		$result = $usercreat->fetch($object->fk_user_creat);
		if ($result < 0) dol_print_error('', $usercreat->error);
		elseif ($result > 0) print $usercreat->getNomUrl(-1);
	}

//Creation Date
	print '</td></tr>';

	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("CreatedOn").'</td>';
	print '<td>';

	print dol_print_date($object->date_creation);

	print '</td></tr>';

//Date
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("UniqueIdentifier").'</td>';
	print '<td>';

	print $preventionplan->unique_identifier_int;
	print '</td></tr>';

	print '</td></tr>';

//Titulars
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("IsEnd").'</td>';
	print '<td>';

	print $preventionplan->is_end;
	print '</td></tr>';

	print '</td></tr>';

//Alternates
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("PreventionID").'</td>';
	print '<td>';

	print $preventionplan->prevention_id;
	print '</td></tr>';

	print '</td></tr>';

// DP

//Date
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("DateStart").'</td>';
	print '<td>';

	print $preventionplan->date_start;
	print '</td></tr>';

	print '</td></tr>';

//Titulars
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("DateEnd").'</td>';
	print '<td>';

	print $preventionplan->date_end;
	print '</td></tr>';

	print '</td></tr>';



}
?>
<!-- END PHP TEMPLATE digiriskdolibarr_preventionplanfields_view.tpl -->
