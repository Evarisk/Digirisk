<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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

/**
 * \file    core/tpl/digiriskelement/digiriskelement_jobsheet_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Job sheet tab of a GP/UT: choice of the machine, checklist of the ED 126 headings, generation and versions
 *
 * Rendered by view/digiriskelement/digiriskelement_jobsheet.php (inherits its scope). Expects:
 * - DigiriskElement $object
 * - Product[]       $products            Machines linked to the GP/UT
 * - int             $productId           Machine of the sheet, 0 for the work station alone
 * - array           $data                JobSheet::load()
 * - array           $checklist           JobSheet::getChecklist()
 * - string          $dirFiles, $fileDir, $urlSource   Documents block of Saturne
 * - bool            $permissiontoadd, $permissiontodelete
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
    print 'Error, template page cannot be called as URL';
    exit;
}

$statusBadges = [
    'ok'      => ['label' => $langs->trans('JobSheetStatusOk'), 'status' => 'status4'],
    'warning' => ['label' => $langs->trans('JobSheetStatusWarning'), 'status' => 'status1'],
    'missing' => ['label' => $langs->trans('JobSheetStatusMissing'), 'status' => 'status8'],
    'hand'    => ['label' => $langs->trans('JobSheetStatusHand'), 'status' => 'status0'],
];
$selfUrl = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
?>

<div class="fichecenter">
    <div class="underbanner clearboth"></div>
    <br>
    <div class="opacitymedium">
        <?php print $langs->trans('JobSheetIntro'); ?>
        <a href="https://www.inrs.fr/dam/inrs/CataloguePapier/ED/TI-ED-126.pdf" target="_blank" rel="noopener noreferrer"><?php print $langs->trans('JobSheetED126Link'); ?> <i class="fas fa-external-link-alt"></i></a>
    </div>
    <br>

    <?php print load_fiche_titre($langs->trans('JobSheetChooseMachine'), '', 'fa-cog'); ?>
    <table class="noborder centpercent">
        <tr class="liste_titre">
            <td><?php print $langs->trans('JobSheetMachine'); ?></td>
            <td><?php print $langs->trans('Label'); ?></td>
            <td class="right"></td>
        </tr>
        <?php foreach ($products as $linkedProduct) : ?>
            <tr class="oddeven">
                <td><?php print $linkedProduct->getNomUrl(1); ?></td>
                <td><?php print dol_escape_htmltag($linkedProduct->label); ?></td>
                <td class="right">
                    <?php if ($linkedProduct->id == $productId) : ?>
                        <?php print dolGetBadge($langs->trans('JobSheetSelected'), '', 'status4'); ?>
                    <?php else : ?>
                        <a class="butActionSmall" href="<?php print $selfUrl . '&fk_product=' . $linkedProduct->id; ?>"><?php print $langs->trans('JobSheetSelect'); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr class="oddeven">
            <td colspan="2">
                <i class="fas fa-user-cog pictofixedwidth"></i><?php print $langs->trans('JobSheetWithoutMachine'); ?>
                <?php if (empty($products)) : ?>
                    <span class="opacitymedium">— <?php print $langs->trans('JobSheetNoLinkedMachine'); ?>
                        <a href="<?php print dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_product.php', 1) . '?id=' . $object->id; ?>"><?php print $langs->trans('JobSheetLinkMachine'); ?></a>
                    </span>
                <?php endif; ?>
            </td>
            <td class="right">
                <?php if ($productId == 0) : ?>
                    <?php print dolGetBadge($langs->trans('JobSheetSelected'), '', 'status4'); ?>
                <?php else : ?>
                    <a class="butActionSmall" href="<?php print $selfUrl . '&fk_product=0'; ?>"><?php print $langs->trans('JobSheetSelect'); ?></a>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <br>

    <?php print load_fiche_titre($langs->trans('JobSheetChecklistTitle'), '', 'fa-tasks'); ?>
    <table class="noborder centpercent">
        <tr class="liste_titre">
            <td class="center width50"><?php print $langs->trans('JobSheetNum'); ?></td>
            <td><?php print $langs->trans('JobSheetHeading'); ?></td>
            <td><?php print $langs->trans('JobSheetTakenFrom'); ?></td>
            <td class="center"><?php print $langs->trans('Status'); ?></td>
            <td><?php print $langs->trans('JobSheetDetail'); ?></td>
        </tr>
        <?php foreach ($checklist as $item) : ?>
            <tr class="oddeven">
                <td class="center"><?php print dol_escape_htmltag($item['num']); ?></td>
                <td><strong><?php print dol_escape_htmltag($item['label']); ?></strong></td>
                <td class="opacitymedium"><?php print dol_escape_htmltag($item['source']); ?></td>
                <td class="center nowraponall"><?php print dolGetBadge($statusBadges[$item['status']]['label'], '', $statusBadges[$item['status']]['status']); ?></td>
                <td>
                    <?php print dol_escape_htmltag($item['detail']); ?>
                    <?php foreach ($item['links'] as $linkLabel => $linkUrl) : ?>
                        <br><a href="<?php print $linkUrl; ?>"><i class="fas fa-pencil-alt pictofixedwidth"></i><?php print dol_escape_htmltag($linkLabel); ?></a>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <br>

    <?php print saturne_show_documents('digiriskdolibarr:JobSheetDocument', $dirFiles, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, '', 1, 0, 0, 0, '', $langs->trans('JobSheetVersions'), '', $langs->defaultlang, '', $object); ?>
</div>
