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
 * \brief   Archive screen of a digirisk element: archived risks then archived sub elements.
 *
 * Requires the following variables to be defined by the calling context:
 * - DoliDB           $db
 * - Conf             $conf
 * - Translate        $langs
 * - DigiriskElement  $object
 * - Risk             $risk
 * - RiskAssessment   $evaluation
 * - bool             $permissionToArchive
 * - bool             $permissionToArchiveRisk
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
    print 'Error, template page cannot be called as URL';
    exit;
}

$archivedRisks    = $object->getArchivedRisks();
$archivedChildren = $object->getArchivedChildren();
$elementPictos    = $object->getPicto();
?>

<div class="digirisk-archive">
    <div class="wpeo-notice notice-info">
        <div class="notice-content">
            <div class="notice-subtitle"><?php echo $langs->trans('ArchiveNoticeDescription'); ?></div>
        </div>
    </div>

    <?php echo load_fiche_titre($langs->trans('ArchivedRisks') . ' <span class="badge">' . count($archivedRisks) . '</span>', '', 'fontawesome_fa-exclamation-triangle_fas_#d35968'); ?>

    <div class="div-table-responsive">
        <table class="noborder centpercent">
            <tr class="liste_titre">
                <td><?php echo $langs->trans('Ref'); ?></td>
                <td><?php echo $langs->trans('DangerCategory'); ?></td>
                <td><?php echo $langs->trans('Description'); ?></td>
                <td class="center"><?php echo $langs->trans('LastRiskAssessment'); ?></td>
                <td class="center"><?php echo $langs->trans('ArchiveDate'); ?></td>
                <td class="center"><?php echo $langs->trans('Status'); ?></td>
                <td class="right"><?php echo $langs->trans('Action'); ?></td>
            </tr>
            <?php if (empty($archivedRisks)) : ?>
                <tr class="oddeven"><td colspan="7"><span class="opacitymedium"><?php echo $langs->trans('NoArchivedRisk'); ?></span></td></tr>
            <?php else : ?>
                <?php foreach ($archivedRisks as $archivedRisk) : ?>
                    <?php
                    // The last validated assessment carries the cotation shown in every risk listing
                    $riskAssessments = $evaluation->fetchFromParent($archivedRisk->id, 1);
                    $lastEvaluation  = is_array($riskAssessments) && !empty($riskAssessments) ? end($riskAssessments) : null;
                    ?>
                    <tr class="oddeven">
                        <td><span class="ref"><?php echo dol_escape_htmltag($archivedRisk->ref); ?></span></td>
                        <td>
                            <img class="danger-category-pic" width="40" height="40" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->getDangerCategory($archivedRisk, $archivedRisk->type) . '.png'; ?>" alt="">
                            <span><?php echo dol_escape_htmltag($risk->getDangerCategoryName($archivedRisk, $archivedRisk->type)); ?></span>
                        </td>
                        <td><?php echo dol_escape_htmltag(dol_trunc($archivedRisk->description, 120)); ?></td>
                        <td class="center">
                            <?php if (is_object($lastEvaluation)) : ?>
                                <span class="risk-evaluation-cotation" data-scale="<?php echo $lastEvaluation->getEvaluationScale(); ?>"><?php echo $lastEvaluation->cotation; ?></span>
                            <?php else : ?>
                                <span class="opacitymedium">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td class="center"><?php echo dol_print_date($archivedRisk->tms, "dayhour"); ?></td>
                        <td class="center"><?php echo $archivedRisk->getLibStatut(5); ?></td>
                        <td class="right">
                            <?php if ($permissionToArchiveRisk) : ?>
                                <a class="wpeo-button button-square-40 button-blue wpeo-tooltip-event" data-direction="left" aria-label="<?php echo dol_escape_htmltag($langs->trans('Unarchive')); ?>" href="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=unarchive_risk&risk_id=' . $archivedRisk->id . '&token=' . newToken(); ?>">
                                    <i class="fas fa-box-open button-icon"></i>
                                </a>
                            <?php else : ?>
                                <span class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" data-direction="left" aria-label="<?php echo dol_escape_htmltag($langs->trans('NotEnoughPermissions')); ?>"><i class="fas fa-box-open button-icon"></i></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

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
                        <td class="center"><?php echo dol_print_date($archivedChild->tms, "dayhour"); ?></td>
                        <td class="center"><?php echo $archivedChild->getLibStatut(5); ?></td>
                        <td class="right">
                            <?php if ($permissionToArchive) : ?>
                                <a class="wpeo-button button-square-40 button-blue wpeo-tooltip-event" data-direction="left" aria-label="<?php echo dol_escape_htmltag($langs->trans('Unarchive')); ?>" href="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=unarchive_element&element_id=' . $archivedChild->id . '&token=' . newToken(); ?>">
                                    <i class="fas fa-box-open button-icon"></i>
                                </a>
                            <?php else : ?>
                                <span class="wpeo-button button-square-40 button-grey wpeo-tooltip-event" data-direction="left" aria-label="<?php echo dol_escape_htmltag($langs->trans('NotEnoughPermissions')); ?>"><i class="fas fa-box-open button-icon"></i></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
