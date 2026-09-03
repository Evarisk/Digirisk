<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/tpl/riskanalysis/risk/digiriskdolibarr_psychosocial_risk_ed6403_modal.tpl.php
 * \ingroup    digiriskdolibarr
 * \brief      Template for psychosocial risks modal - INRS RPS-DU method (ED 6403)
 */

require_once __DIR__ . '/../../../../class/riskanalysis/risk.class.php';

$ed6403SubCategories = Risk::getDangerSubCategories('ed6403')[17] ?? [];

// Answer columns of the INRS grid, always displayed in this order
$ed6403Answers = ['RPSAnswerNever', 'RPSAnswerSometimes', 'RPSAnswerOften', 'RPSAnswerAlways'];

// Digirisk cotation matching each color of the INRS grid : green, yellow, orange, red
$ed6403Cotations = [0, 48, 51, 80];

/**
 * Translate a key coming from the danger sub categories JSON, fallback on the raw french label
 *
 * @param  string $key     Translation key
 * @param  string $default Raw label of the JSON file
 * @return string
 */
$ed6403Trans = function (string $key, string $default) use ($langs) {
    $translated = $langs->trans($key);
    return ($translated == $key || dol_strlen($translated) == 0) ? $default : $translated;
};

?>

<!-- Modal des risques psychosociaux - grille RPS-DU (ED 6403) -->
<div class="psychosocial-risk-ed6403-add-modal" value="<?php echo $object->id ?>">
    <div class="wpeo-modal modal-risk-0 modal-risk" id="psychosocial_risk_ed6403_add" value="new">
        <div class="modal-container wpeo-modal-event">
            <!-- Modal-Header -->
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-clipboard-list"></i> <?php print $langs->trans('AddPsychosocialRiskED6403Title'); ?></h2>
                <div class="modal-close"><i class="fas fa-times"></i></div>
            </div>
            <!-- Modal-Content -->
            <div class="modal-content">
                <div class="psychosocial-risk-ed6403-content">
                    <div class="psychosocial-risk-ed6403-wrapper">
                        <div class="psychosocial-risk-ed6403-help"><?php print $langs->trans('AddPsychosocialRiskED6403Help'); ?></div>
                        <table id="psychosocial_risk_ed6403_table" class="psychosocial-risk-ed6403-table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select_all_psychosocial_risks_ed6403" class="select-all-risks" checked>
                                        <label for="select_all_psychosocial_risks_ed6403"><?php print $langs->trans('SelectAllCriteria'); ?></label>
                                    </th>
                                    <th><?php print $langs->trans('DangerCategories'); ?></th>
                                    <th><?php print $langs->trans('RPSCriterion'); ?></th>
                                    <th><?php print $langs->trans('RPSCotation'); ?></th>
                                    <th><?php print $langs->trans('RiskDescription'); ?></th>
                                    <th><?php print $langs->trans('RiskAssessmentDate'); ?></th>
                                    <th><?php print $langs->trans('PreventionActions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="psychosocial_risks_ed6403_list">
                                <?php
                                $criterionIndex  = 0;
                                $criterionNumber = 0;
                                $currentFamily   = '';
                                foreach ($ed6403SubCategories as $subCategory) :
                                    $criterionNumber++;
                                    if ($currentFamily != $subCategory['family']) :
                                        $currentFamily = $subCategory['family']; ?>
                                        <!-- Header de famille de facteurs -->
                                        <tr class="psychosocial-ed6403-family-header">
                                            <td colspan="7">
                                                <i class="fas fa-layer-group"></i>
                                                <?php print $ed6403Trans($subCategory['familyLabel'], $subCategory['family']); ?>
                                            </td>
                                        </tr>
                                    <?php endif;

                                    $criterionName = $ed6403Trans($subCategory['label'], $subCategory['name']);
                                    $reversed      = !empty($subCategory['reversed']); ?>

                                    <tr class="oddeven psychosocial-risk-ed6403-row" id="psychosocial_risk_ed6403_<?php echo $criterionIndex; ?>">
                                        <td class="psychosocial-risk-ed6403-select">
                                            <input type="checkbox" class="select-psychosocial-risk-ed6403" name="selected_ed6403_risks[<?php echo $criterionIndex; ?>][selected]" value="1" id="risk_ed6403_checkbox_<?php echo $criterionIndex; ?>" checked>
                                        </td>
                                        <td>
                                            <div class="risk-category-container">
                                                <img src="<?php echo DOL_URL_ROOT; ?>/custom/digiriskdolibarr/img/categorieDangers/rps_v2.png" class="risk-category-pic" alt="<?php echo dol_escape_htmltag($langs->trans('PsychosocialRisks')); ?>">
                                                <input hidden class="sub-category" type="text" value="<?php echo dol_escape_htmltag($subCategory['position']); ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="rps-criterion-title"><?php echo $criterionNumber . ' - ' . dol_escape_htmltag($criterionName); ?></div>
                                            <div class="rps-criterion-question"><?php echo dol_escape_htmltag($ed6403Trans($subCategory['label'] . 'Question', $subCategory['question'])); ?></div>
                                        </td>
                                        <td>
                                            <div class="cotation-container">
                                                <div class="cotation-standard">
                                                    <div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
                                                        <?php foreach ($ed6403Answers as $answerIndex => $answerLabel) :
                                                            // On a reversed criterion the answers stay in the same order but the colors, and so the cotations, are mirrored
                                                            $scale    = $reversed ? (4 - $answerIndex) : ($answerIndex + 1);
                                                            $cotation = $ed6403Cotations[$scale - 1]; ?>
                                                            <div class="risk-evaluation-cotation cotation rps-cotation-level-<?php echo $scale; ?><?php echo ($scale == 1 ? ' selected-cotation' : ''); ?>"
                                                                 data-evaluation-method="standard"
                                                                 data-evaluation-id="<?php echo $cotation; ?>"
                                                                 data-scale="<?php echo $scale; ?>"
                                                                 data-id="0"
                                                                 data-variable-id="<?php echo $cotation; ?>">
                                                                <?php print $langs->trans($answerLabel); ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <textarea class="flat rps-risk-description" name="selected_ed6403_risks[<?php echo $criterionIndex; ?>][description]" rows="2"><?php echo dol_escape_htmltag($criterionName); ?></textarea>
                                        </td>
                                        <td>
                                            <input type="datetime-local" name="riskassessment-date" class="riskassessment-date" value="<?php echo dol_print_date(dol_now('tzuser'), '%Y-%m-%dT%H:%M:%S'); ?>">
                                        </td>
                                        <td>
                                            <textarea class="flat task-name" name="selected_ed6403_risks[<?php echo $criterionIndex; ?>][prevention_actions]" rows="2" placeholder="<?php echo dol_escape_htmltag($langs->trans('PreventionActions')); ?>"></textarea>
                                        </td>
                                    </tr>
                                    <?php $criterionIndex++;
                                endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Modal-Footer -->
            <div class="modal-footer">
                <div id="submit_selected_psychosocial_risks_ed6403" class="wpeo-button button-primary">
                    <span><i class="fas fa-plus"></i> <?php print $langs->trans('AddSelectedPsychosocialRisksED6403'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
