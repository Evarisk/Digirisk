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
	<!-- BEGIN PHP TEMPLATE digiriskdolibarr_firepermitfields_view.tpl -->
<?php
if ($action == '') {

	$firepermit = json_decode($object->json, false, 512, JSON_UNESCAPED_UNICODE)->FirePermit;

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

	print $firepermit->unique_identifier_int;
	print '</td></tr>';

	print '</td></tr>';

//Titulars
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("IsEnd").'</td>';
	print '<td>';

	print $firepermit->is_end;
	print '</td></tr>';

	print '</td></tr>';

//Alternates
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("PreventionID").'</td>';
	print '<td>';

	print $firepermit->prevention_id;
	print '</td></tr>';

	print '</td></tr>';

// DP

//Date
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("DateStart").'</td>';
	print '<td>';

	print $firepermit->date_start;
	print '</td></tr>';

	print '</td></tr>';

//Titulars
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("DateEnd").'</td>';
	print '<td>';

	print $firepermit->date_end;
	print '</td></tr>';

	print '</td></tr>';

//Alternates
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("DateEndIsDefined").'</td>';
	print '<td>';

	print $firepermit->date_end__is_define;
	print '</td></tr>';

	print '</td></tr>';

//Alternates
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("DateClosure").'</td>';
	print '<td>';

	print $firepermit->date_closure;
	print '</td></tr>';

	print '</td></tr>';

//Alternates
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("Former").'</td>';
	print '<td>';

	print $firepermit->former;
	print '</td></tr>';

	print '</td></tr>';


//Alternates
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("MasterBuilder").'</td>';
	print '<td>';

	print $firepermit->maitre_oeuvre;
	print '</td></tr>';

	print '</td></tr>';

//Alternates
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("ExternalIntervener").'</td>';
	print '<td>';

	print $firepermit->intervenant_exterieur;
	print '</td></tr>';

	print '</td></tr>';

//Alternates
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("Intervener").'</td>';
	print '<td>';

	print $firepermit->intervenants;
	print '</td></tr>';

	print '</td></tr>';

//Alternates
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("ExternalSociety").'</td>';
	print '<td>';

	print $firepermit->society_outside;
	print '</td></tr>';

	print '</td></tr>';

//Alternates
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("Taxonomy").'</td>';
	print '<td>';

	print $firepermit->taxonomy;
	print '</td></tr>';

	print '</td></tr>';

}
elseif ($action == 'create') {

	//Changer pour les bons champs
	print '<tr class="oddeven"><td><label for="unique_identifier_int">'.$langs->trans("UniqueIdentifier").'</label></td><td>';
	print '<input name="unique_identifier_int" id="unique_identifier_int" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_GENERAL_RULES ? $conf->global->DIGIRISK_GENERAL_RULES : '').'</td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="is_end">'.$langs->trans("IsEnd").'</label></td><td>';
	print '<input name="is_end" id="is_end" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="prevention_id">'.$langs->trans("PreventionID").'</label></td><td>';
	print '<input name="prevention_id" id="prevention_id" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="date_start">'.$langs->trans("DateStart").'</label></td><td>';
	print '<input name="date_start" id="date_start" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="date_end">'.$langs->trans("DateEnd").'</label></td><td>';
	print '<input name="date_end" id="date_end" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="date_end__is_define">'.$langs->trans("DateEndIsDefined").'</label></td><td>';
	print '<input name="date_end__is_define" id="date_end__is_define" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="date_closure">'.$langs->trans("DateClosure").'</label></td><td>';
	print '<input name="date_closure" id="date_closure" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="former">'.$langs->trans("Former").'</label></td><td>';
	print '<input name="former" id="former" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="maitre_oeuvre">'.$langs->trans("MasterBuilder").'</label></td><td>';
	print '<input name="maitre_oeuvre" id="maitre_oeuvre" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="intervenant_exterieur">'.$langs->trans("ExternalIntervener").'</label></td><td>';
	print '<input name="intervenant_exterieur" id="intervenant_exterieur" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="intervenants">'.$langs->trans("Intervener").'</label></td><td>';
	print '<input name="intervenants" id="intervenants" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="society_outside">'.$langs->trans("ExternalSociety").'</label></td><td>';
	print '<input name="society_outside" id="society_outside" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

	print '<tr class="oddeven"><td><label for="taxonomy">'.$langs->trans("Taxonomy").'</label></td><td>';
	print '<input name="taxonomy" id="taxonomy" class="minwidth300" rows="'.ROWS_3.'"></td></tr>'."\n";

}
elseif ($action == 'add') {

}
?>
<!-- END PHP TEMPLATE digiriskdolibarr_firepermitfields_view.tpl -->
