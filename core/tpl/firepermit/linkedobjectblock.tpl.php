<?php
global $user;

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
