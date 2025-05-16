<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

$linkedObjectBlock = dol_sort_array($linkedObjectBlock, 'datec', 'desc', 0, 0, 1);
$ilink = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;

    $trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass .= ' liste_sub_total';
	} ?>
	<tr class="<?php echo $trclass; ?>" >
		<td class="linkedcol-element tdoverflowmax100"><?php echo $langs->trans("FirePermit"); ?>
		<td class="linkedcol-name tdoverflowmax150"><?php echo $objectlink->getNomUrl(1); ?></td>
		<td class="linkedcol-label"><?php echo $objectlink->label; ?></td>
		<td class="linkedcol-date center"><?php echo dol_print_date($objectlink->date_start, 'day'); ?></td>
		<td class="linkedcol-date center"><?php echo dol_print_date($objectlink->date_end, 'day'); ?></td>
		<td class="linkedcol-statut right"><?php echo $objectlink->getLibStatut(3); ?></td>
</tr>
	<?php
}
