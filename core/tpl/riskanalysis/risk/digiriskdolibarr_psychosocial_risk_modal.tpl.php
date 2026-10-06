<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/tpl/riskanalysis/risk/digiriskdolibarr_psychosocial_risk_modal.tpl.php
 * \ingroup    digiriskdolibarr
 * \brief      Template for psychosocial risks modal
 */

$predefinedPsychosocialRisks = [
    $langs->trans('PsychosocialRisksFactors') => [
        [
            'title' => 'rps_v2',
            'category' => $langs->trans('PsychosocialRisks'),
            'description' => $langs->trans('EmotionalRequirements'),
            'cotation' => 51,
            'sub-category' => 0
        ],
        [
            'title' => 'rps_v2',
            'category' => $langs->trans('PsychosocialRisks'),
            'description' => $langs->trans('WorkIntensityAndTime'),
            'cotation' => 0,
            'sub-category' => 1
        ],
        [
            'title' => 'rps_v2',
            'category' => $langs->trans('PsychosocialRisks'),
            'description' => $langs->trans('Autonomy'),
            'cotation' => 0,
            'sub-category' => 2
        ],
        [
            'title' => 'rps_v2',
            'category' => $langs->trans('PsychosocialRisks'),
            'description' => $langs->trans('SocialRelationsAtWork'),
            'cotation' => 0,
            'sub-category' => 3
        ],
        [
            'title' => 'rps_v2',
            'category' => $langs->trans('PsychosocialRisks'),
            'description' => $langs->trans('MeaningOfWork'),
            'cotation' => 0,
            'sub-category' => 4
        ],
        [
            'title' => 'rps_v2',
            'category' => $langs->trans('PsychosocialRisks'),
            'description' => $langs->trans('WorkSituationInsecurity'),
            'cotation' => 0,
            'sub-category' => 5
        ],
    ],
    $langs->trans('CompanyPreventionInformations') => [
        [
            'title' => 'rps_v2',
            'category' => $langs->trans('PsychosocialRisks'),
            'description' => $langs->trans('PreventionContextInCompany'),
            'cotation' => 48,
            'sub-category' => 6
        ],
        [
            'title' => 'rps_v2',
            'category' => $langs->trans('PsychosocialRisks'),
            'description' => $langs->trans('RPSImpactOnCompanyAndEmployees'),
            'cotation' => 0,
            'sub-category' => 7
        ]
    ]
];

// Échelle proposée dans la modal : trois paliers suffisent pour coter un facteur psychosocial.
// La valeur envoyée est la cotation Digirisk représentative du palier et le libellé reprend les
// clés déjà utilisées par la grille de cotation du module.
$psychosocialCotationScale = [
    1 => ['value' => 0, 'label' => 'Weak'],
    2 => ['value' => 48, 'label' => 'Moderate'],
    3 => ['value' => 51, 'label' => 'High']
];

$psychosocialRiskCount = 0;
foreach ($predefinedPsychosocialRisks as $risks) {
    $psychosocialRiskCount += count($risks);
}

?>

<!-- Modal des risques psychosociaux -->
<div class="psychosocial-risk-add-modal" value="<?php echo $object->id ?>">
    <div class="wpeo-modal modal-risk-0 modal-risk" id="psychosocial_risk_add" value="new">
        <div class="modal-container modal-container-psychosocial wpeo-modal-event">
            <!-- Modal-Header -->
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-brain"></i> <?php print $langs->trans('AddPsychosocialRiskTitle'); ?></h2>
                <div class="modal-close"><i class="fas fa-times"></i></div>
            </div>
            <!-- Modal-Content -->
            <div class="modal-content" id="#modalContent">
                <div class="psychosocial-risk-content">
                    <div class="psychosocial-risk-wrapper">
                        <table id="psychosocial_risk_table" class="psychosocial-risk-table">
                            <thead>
                            <tr>
                                <th>
                                    <label class="psychosocial-select-all">
                                        <input type="checkbox" id="select_all_psychosocial_risks" class="select-all-risks" checked>
                                        <span><?php print $langs->trans('SelectAll'); ?></span>
                                    </label>
                                </th>
                                <th><?php print $langs->trans('DangerCategory'); ?></th>
                                <th><?php print $langs->trans('RiskCotation'); ?></th>
                                <th><?php print $langs->trans('RiskDescription'); ?></th>
                                <th><?php print $langs->trans('RiskAssessmentDate'); ?></th>
                                <th><?php print $langs->trans('PreventionActions'); ?></th>
                            </tr>
                            </thead>
                            <tbody id="psychosocial_risks_list">
                            <?php
                            $riskIndex = 0;
                            foreach ($predefinedPsychosocialRisks as $sectionTitle => $risks) :
                                ?>
                                <!-- Header de section -->
                                <tr class="psychosocial-section-header">
                                    <td colspan="6">
                                        <i class="fas fa-layer-group"></i>
                                        <?php echo $sectionTitle; ?>
                                    </td>
                                </tr>

                                <?php foreach ($risks as $risk) : ?>
                                    <tr class="oddeven psychosocial-risk-row" id="psychosocial_risk_<?php echo $riskIndex; ?>" data-category="17">
                                        <td class="psychosocial-cell-select">
                                            <input type="checkbox"
                                                   class="select-psychosocial-risk"
                                                   name="selected_risks[<?php echo $riskIndex; ?>][selected]"
                                                   value="1"
                                                   id="risk_checkbox_<?php echo $riskIndex; ?>"
                                                   checked>
                                        </td>
                                        <td class="psychosocial-cell-category">
                                            <div class="risk-category-container">
                                                <img src="<?php echo DOL_URL_ROOT; ?>/custom/digiriskdolibarr/img/categorieDangers/rps_v2.png"
                                                     class="risk-category-pic"
                                                     alt="<?php echo dol_escape_htmltag($risk['category']); ?>">
                                                <input hidden class="sub-category"
                                                       type="text"
                                                       value="<?php echo dol_escape_htmltag($risk['sub-category']); ?>">
                                                <input type="hidden" name="selected_risks[<?php echo $riskIndex; ?>][title]" value="<?php echo dol_escape_htmltag($risk['title']); ?>">
                                                <input type="hidden" name="selected_risks[<?php echo $riskIndex; ?>][category]" value="<?php echo dol_escape_htmltag($risk['category']); ?>">
                                            </div>
                                        </td>
                                        <td class="psychosocial-cell-cotation">
                                            <div class="cotation-container">
                                                <div class="cotation-standard">
                                                    <div class="cotation-listing">
                                                        <?php
                                                        $cotation = $risk['cotation'];
                                                        $scale = $cotation <= 47 ? 1 : ($cotation <= 50 ? 2 : ($cotation <= 80 ? 3 : 4));
                                                        foreach ($psychosocialCotationScale as $scaleKey => $data) :
                                                            ?>
                                                            <div class="risk-evaluation-cotation cotation<?php echo ($scaleKey == $scale ? ' selected-cotation' : ''); ?>"
                                                                 data-evaluation-method="standard"
                                                                 data-evaluation-id="<?php echo $data['value']; ?>"
                                                                 data-scale="<?php echo $scaleKey; ?>"
                                                                 data-id="0"
                                                                 data-variable-id="<?php echo $data['value']; ?>">
                                                                <?php print $langs->trans($data['label']); ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="psychosocial-cell-description">
                                            <textarea class="flat risk-description"
                                                      name="selected_risks[<?php echo $riskIndex; ?>][description]"
                                                      rows="2"><?php echo dol_escape_htmltag($risk['description']); ?></textarea>
                                        </td>
                                        <td class="psychosocial-cell-date">
                                            <input type="datetime-local" name="riskassessment-date" class="riskassessment-date" value="<?php echo dol_print_date(dol_now('tzuser'), '%Y-%m-%dT%H:%M:%S'); ?>">
                                        </td>
                                        <td class="psychosocial-cell-actions">
                                            <textarea class="flat task-name"
                                                      name="selected_risks[<?php echo $riskIndex; ?>][prevention_actions]"
                                                      rows="2"
                                                      placeholder="<?php echo dol_escape_htmltag($langs->trans('PreventionActions')); ?>"></textarea>
                                        </td>
                                    </tr>
                                    <?php $riskIndex++; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Modal-Footer -->
            <div class="modal-footer center">
                <div class="wpeo-button button-grey modal-close">
                    <span><i class="fas fa-times"></i> <?php print $langs->trans('Cancel'); ?></span>
                </div>
                <div id="submit_selected_psychosocial_risks" class="wpeo-button button-primary" data-loading-label="<?php echo dol_escape_htmltag($langs->trans('AddingInProgress')); ?>">
                    <span class="psychosocial-submit-label">
                        <i class="fas fa-plus"></i>
                        <?php print $langs->trans('AddSelectedPsychosocialRisks'); ?>
                        <span class="psychosocial-selected-count"><?php echo $psychosocialRiskCount; ?></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
