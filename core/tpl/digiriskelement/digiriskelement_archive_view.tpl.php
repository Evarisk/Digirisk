<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiriskelement/digiriskelement_archive_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Archive screen of a digirisk element: the risk list restricted to the archived risks,
 *          then the archived sub elements.
 *
 * Requires the calling context to have set up the risk list variables the same way
 * digiriskelement_risk.php does, plus:
 * - DigiriskElement  $object
 * - string           $riskType
 * - bool             $permissionToArchiveElement
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
    print 'Error, template page cannot be called as URL';
    exit;
}

$archivedChildren = $object->getArchivedChildren();
$elementPictos    = $object->getPicto();
$riskTypes        = ['risk', 'riskenvironmental'];
?>

<div class="digirisk-archive">
    <div class="wpeo-notice notice-info">
        <div class="notice-content">
            <div class="notice-subtitle"><?php echo $langs->trans('ArchiveNoticeDescription'); ?></div>
        </div>
    </div>

    <div class="archive-risk-type-switch">
        <?php foreach ($riskTypes as $availableRiskType) : ?>
            <a href="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&risk_type=' . $availableRiskType; ?>">
                <div class="wpeo-button <?php echo $availableRiskType == $riskType ? 'button-blue' : 'button-grey'; ?>">
                    <i class="fas <?php echo $availableRiskType == 'risk' ? 'fa-exclamation-triangle' : 'fa-leaf'; ?> button-icon"></i>
                    <span><?php echo digirisk_trans_risk_type('', $availableRiskType, 's'); ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php
    // Same list as the risk tab, restricted to the archived risks of the element
    $archivedRiskList = 1;
    require __DIR__ . '/../riskanalysis/risk/digiriskdolibarr_risklist_view.tpl.php';
    ?>

    <?php echo load_fiche_titre($langs->trans('ArchivedElements') . ' <span class="badge">' . count($archivedChildren) . '</span>', '', 'fontawesome_fa-network-wired_fas_#d35968'); ?>

    <div class="div-table-responsive">
        <table class="noborder centpercent">
            <tr class="liste_titre">
                <td><?php echo $langs->trans('Ref'); ?></td>
                <td><?php echo $langs->trans('Label'); ?></td>
                <td class="center"><?php echo $langs->trans('ArchivedContent'); ?></td>
                <td class="center"><?php echo $langs->trans('ArchiveDate'); ?></td>
                <td class="center"><?php echo $langs->trans('Status'); ?></td>
                <td class="right"><?php echo $langs->trans('Action'); ?></td>
            </tr>
            <?php if (empty($archivedChildren)) : ?>
                <tr class="oddeven"><td colspan="6"><span class="opacitymedium"><?php echo $langs->trans('NoArchivedElement'); ?></span></td></tr>
            <?php else : ?>
                <?php foreach ($archivedChildren as $archivedChild) : ?>
                    <?php
                    $childDescendants = $archivedChild->getDescendants();
                    $childRisks       = $archivedChild->getArchivedRisks();
                    ?>
                    <tr class="oddeven">
                        <td>
                            <?php echo $elementPictos[$archivedChild->element_type] ?? ''; ?>
                            <a href="<?php echo dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_card.php', 1) . '?id=' . $archivedChild->id; ?>"><?php echo dol_escape_htmltag($archivedChild->ref); ?></a>
                        </td>
                        <td><?php echo dol_escape_htmltag($archivedChild->label); ?></td>
                        <td class="center">
                            <?php echo $langs->trans('NbOfSubElements') . ' : ' . count($childDescendants); ?>
                            <br>
                            <?php echo $langs->trans('NbOfArchivedRisks') . ' : ' . count($childRisks); ?>
                        </td>
                        <td class="center"><?php echo dol_print_date($archivedChild->tms, 'dayhour'); ?></td>
                        <td class="center"><?php echo $archivedChild->getLibStatut(5); ?></td>
                        <td class="right">
                            <?php if ($permissionToArchiveElement) : ?>
                                <a href="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=unarchive_element&element_id=' . $archivedChild->id . '&token=' . newToken(); ?>">
                                    <div class="wpeo-button button-square-40 button-blue wpeo-tooltip-event" data-direction="left" aria-label="<?php echo dol_escape_htmltag($langs->trans('Unarchive')); ?>">
                                        <i class="fas fa-box-open button-icon"></i>
                                    </div>
                                </a>
                            <?php else : ?>
                                <div class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" data-direction="left" aria-label="<?php echo dol_escape_htmltag($langs->trans('NotEnoughPermissions')); ?>">
                                    <i class="fas fa-box-open button-icon"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
