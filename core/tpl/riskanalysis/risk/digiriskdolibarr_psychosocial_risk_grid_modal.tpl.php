<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/tpl/riskanalysis/risk/digiriskdolibarr_psychosocial_risk_grid_modal.tpl.php
 * \ingroup    digiriskdolibarr
 * \brief      Template for the INRS psychosocial risk assessment grid modal (RPS-DU tool, ED 6403)
 */

$psychosocialGridCriteria = Risk::getPsychosocialRiskGrid();

// The four answers of the printed grid, in its own reading order
$psychosocialGridAnswers = ['PsychosocialGridAnswerNever', 'PsychosocialGridAnswerSometimes', 'PsychosocialGridAnswerOften', 'PsychosocialGridAnswerAlways'];

// The four colours of the grid land on the four levels of the Digirisk cotation scale
$psychosocialGridScale = [
    1 => ['cotation' => 0,   'label' => 'Weak'],
    2 => ['cotation' => 48,  'label' => 'Moderate'],
    3 => ['cotation' => 51,  'label' => 'High'],
    4 => ['cotation' => 100, 'label' => 'Extreme']
];

$psychosocialGridFamily = '';

?>

<!-- Modal de la grille d'évaluation des RPS (outil RPS-DU de l'INRS) -->
<div class="psychosocial-risk-grid-modal" value="<?php echo $object->id; ?>" data-category="<?php echo Risk::PSYCHOSOCIAL_CATEGORY_POSITION; ?>">
    <div class="wpeo-modal modal-risk-0 modal-risk" id="psychosocial_risk_grid_add" value="new">
        <div class="modal-container modal-container-psychosocial-grid wpeo-modal-event">
            <!-- Modal-Header -->
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-clipboard-list"></i> <?php print $langs->trans('AddPsychosocialRiskGridTitle'); ?></h2>
                <div class="modal-close"><i class="fas fa-times"></i></div>
            </div>
            <!-- Modal-Content -->
            <div class="modal-content" id="#modalContent">
                <div class="psychosocial-grid-error notice hidden">
                    <div class="wpeo-notice notice-warning">
                        <div class="notice-content">
                            <div class="notice-title"><?php echo $langs->trans('PsychosocialGridCreationError'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="psychosocial-grid-toolbar">
                    <span class="psychosocial-grid-source"><i class="fas fa-circle-info"></i> <?php print $langs->trans('PsychosocialGridSource'); ?></span>
                    <label class="psychosocial-grid-date-label">
                        <?php print $langs->trans('RiskAssessmentDate'); ?>
                        <input type="datetime-local" class="psychosocial-grid-date" value="<?php echo dol_print_date(dol_now('tzuser'), '%Y-%m-%dT%H:%M:%S'); ?>">
                    </label>
                </div>
                <div class="psychosocial-grid-content">
                    <table class="psychosocial-grid-table">
                        <thead>
                        <tr>
                            <th><?php print $langs->trans('PsychosocialGridCriterion'); ?></th>
                            <th><?php print $langs->trans('RiskCotation'); ?></th>
                            <th><?php print $langs->trans('RiskDescription'); ?></th>
                            <th><?php print $langs->trans('PreventionActions'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($psychosocialGridCriteria as $criterion) : ?>
                            <?php if ($criterion['family'] != $psychosocialGridFamily) : ?>
                                <?php $psychosocialGridFamily = $criterion['family']; ?>
                                <tr class="psychosocial-grid-family">
                                    <td colspan="4"><i class="fas fa-layer-group"></i> <?php echo dol_escape_htmltag($psychosocialGridFamily); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="psychosocial-grid-row" data-sub-category="<?php echo (int) $criterion['subCategory']; ?>">
                                <td class="psychosocial-grid-cell-criterion">
                                    <span class="psychosocial-grid-criterion-name"><?php echo (int) $criterion['position'] . '. ' . dol_escape_htmltag($criterion['name']); ?></span>
                                    <span class="psychosocial-grid-criterion-question"><?php echo dol_escape_htmltag($criterion['question']); ?></span>
                                </td>
                                <td class="psychosocial-grid-cell-answers">
                                    <div class="psychosocial-grid-answers">
                                        <?php
                                        // A criterion describing a resource rather than a constraint is read the other
                                        // way round: on this one, never doing it is the answer that colours red
                                        $criterionScales = !empty($criterion['reversed']) ? [4, 3, 2, 1] : [1, 2, 3, 4];
                                        foreach ($psychosocialGridAnswers as $answerIndex => $answerLabel) :
                                            $scale = $criterionScales[$answerIndex];
                                            ?>
                                            <div class="psychosocial-grid-answer wpeo-tooltip-event" data-scale="<?php echo $scale; ?>" data-cotation="<?php echo $psychosocialGridScale[$scale]['cotation']; ?>" aria-label="<?php echo dol_escape_htmltag($langs->trans('RiskCotation') . ' : ' . $langs->trans($psychosocialGridScale[$scale]['label'])); ?>">
                                                <?php print $langs->trans($answerLabel); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="psychosocial-grid-cell-description">
                                    <textarea class="psychosocial-grid-description" rows="2"><?php echo dol_escape_htmltag($criterion['name']); ?></textarea>
                                </td>
                                <td class="psychosocial-grid-cell-actions">
                                    <textarea class="psychosocial-grid-task" rows="2" placeholder="<?php echo dol_escape_htmltag($langs->trans('PreventionActions')); ?>"></textarea>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Modal-Footer -->
            <div class="modal-footer center">
                <span class="psychosocial-grid-hint"><?php print $langs->trans('PsychosocialGridHint'); ?></span>
                <div class="wpeo-button button-grey modal-close">
                    <span><i class="fas fa-times"></i> <?php print $langs->trans('Cancel'); ?></span>
                </div>
                <div class="psychosocial-grid-submit wpeo-button button-primary button-grey" disabled="disabled" data-loading-label="<?php echo dol_escape_htmltag($langs->trans('AddingInProgress')); ?>">
                    <span class="psychosocial-grid-submit-label">
                        <i class="fas fa-plus"></i>
                        <?php print $langs->trans('AddAssessedPsychosocialGridCriteria'); ?>
                        <span class="psychosocial-grid-assessed-count">0</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
