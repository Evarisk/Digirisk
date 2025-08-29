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

?>

<!-- Modal des risques psychosociaux -->
<div class="psychosocial-risk-add-modal" value="<?php echo $object->id ?>">
    <div class="wpeo-modal modal-risk-0 modal-risk" id="psychosocial_risk_add" value="new">
        <div class="modal-container wpeo-modal-event" style="max-width: 80%; max-height: 80%;">
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
                                    <input type="checkbox" id="select_all_psychosocial_risks" class="select-all-risks" checked>
                                    <label for="select_all_psychosocial_risks" style="margin-left: 5px; font-weight: normal;">Tout sélectionner</label>
                                </th>
                                <th>Catégorie</th>
                                <th>Cotation</th>
                                <th>Description du Risque</th>
                                <th>Date de l'évaluation</th>
                                <th>Actions de Prévention</th>
                            </tr>
                            </thead>
                            <tbody id="psychosocial_risks_list">
                            <?php
                            $riskIndex = 0;
                            foreach ($predefinedPsychosocialRisks as $sectionTitle => $risks):
                            ?>
                                <!-- Header de section -->
                                <tr class="psychosocial-section-header">
                                    <td colspan="5" style="background-color: #f8f9fa; font-weight: bold; padding: 15px; border-top: 2px solid #dee2e6; text-align: left;">
                                        <i class="fas fa-layer-group" style="margin-right: 8px;"></i>
                                        <?php echo $sectionTitle; ?>
                                    </td>
                                </tr>

                                <?php foreach ($risks as $risk): ?>
                                    <tr class="oddeven psychosocial-risk-row" id="psychosocial_risk_<?php echo $riskIndex; ?>" data-category="17">
                                        <td style="justify-items: center;">
                                            <input type="checkbox"
                                                   class="select-psychosocial-risk"
                                                   name="selected_risks[<?php echo $riskIndex; ?>][selected]"
                                                   value="1"
                                                   id="risk_checkbox_<?php echo $riskIndex; ?>"
                                                   checked>
                                        </td>
                                        <td>
                                            <div class="risk-category-container">
                                                <img src="<?php echo DOL_URL_ROOT; ?>/custom/digiriskdolibarr/img/categorieDangers/rps_v2.png"
                                                     class="risk-category-pic"
                                                     alt="<?php echo dol_escape_htmltag($risk['category']); ?>">
                                                <input hidden class="sub-category"
                                                       type="text"
                                                       value="<?php echo dol_escape_htmltag($risk['sub-category']); ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cotation-container">
                                                <div class="cotation-standard" style="display: block">
                                                    <div class="cotation-listing wpeo-gridlayout grid-4 grid-gap-0">
                                                        <?php
                                                        $cotation = $risk['cotation'];
                                                        $scale = $cotation <= 47 ? 1 : ($cotation <= 50 ? 2 : ($cotation <= 80 ? 3 : 4));
                                                        $labelMap = [
                                                            1 => ['value' => 0, 'label' => 'Faible', 'color' => '#00b300'],
                                                            2 => ['value' => 48, 'label' => 'Modéré', 'color' => '#ff9900'],
                                                            3 => ['value' => 51, 'label' => 'Élevé', 'color' => '#ff3300'],
                                                        ];
                                                        foreach ($labelMap as $scaleKey => $data):
                                                        ?>
                                                        <div class="risk-evaluation-cotation cotation<?php echo ($scaleKey == $scale ? ' selected-cotation' : ''); ?>"
                                                             data-evaluation-method="standard"
                                                             data-evaluation-id="<?php echo $data['value']; ?>"
                                                             data-scale="<?php echo $scaleKey; ?>"
                                                             data-id="0"
                                                             data-variable-id="<?php echo ($data['value']); ?>"
                                                             style="cursor: pointer; width: 60px; background-color: <?php echo $data['color']; ?>;">
                                                            <?php echo $data['label']; ?>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <textarea class="flat minwidth200 risk-description"
                                                      name="selected_risks[<?php echo $riskIndex; ?>][description]"
                                                      rows="1"
                                            style="width: 100% !important"><?php echo dol_escape_htmltag($risk['description']); ?></textarea>
                                        </td>
                                        <td>
                                            <?php print '<input type="datetime-local" name="riskassessment-date" class="riskassessment-date" value="' . dol_print_date(dol_now('tzuser'), '%Y-%m-%dT%H:%M:%S') . '">'; ?>
                                        </td>
                                        <td>
                                            <textarea class="flat task-name"
                                                      name="selected_risks[<?php echo $riskIndex; ?>][prevention_actions]"
                                                      rows="1"
                                                      style="width: 100%; max-width: 180px;"
                                                      placeholder="<?php echo $langs->trans('PreventionActions'); ?>">Lire le rapport sur les RPS dans la pièce jointe de cette tâche</textarea>
                                        </td>
                                    </tr>

                                    <!-- Données cachées pour chaque risque -->
                                    <input type="hidden" name="selected_risks[<?php echo $riskIndex; ?>][title]" value="<?php echo dol_escape_htmltag($risk['title']); ?>">
                                    <input type="hidden" name="selected_risks[<?php echo $riskIndex; ?>][category]" value="<?php echo dol_escape_htmltag($risk['category']); ?>">

                                    <?php $riskIndex++; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Modal-Footer -->
            <div class="modal-footer">
                <div id="submit_selected_psychosocial_risks" class="wpeo-button button-primary">
                    <span><i class="fas fa-plus"></i> <?php echo $langs->trans('AddSelectedPsychosocialRisks'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
