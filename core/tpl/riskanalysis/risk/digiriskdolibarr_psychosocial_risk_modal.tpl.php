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
                                <th>Action</th>
                                <th>Catégorie</th>
                                <th style="width: 300px; text-align: center;">Cotation</th>
                                <th>Description du Risque</th>
                                <th>Actions de Prévention</th>
                            </tr>
                            </thead>
                            <tbody id="psychosocial_risks_list">
                            <?php foreach ($predefinedPsychosocialRisks as $index => $risk): ?>
                                <tr class="oddeven psychosocial-risk-row" id="psychosocial_risk_<?php echo $index; ?>" data-category="17">
                                    <td>
                                        <input type="checkbox"
                                               class="select-psychosocial-risk"
                                               name="selected_risks[<?php echo $index; ?>][selected]"
                                               value="1"
                                               id="risk_checkbox_<?php echo $index; ?>">
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
                                              name="selected_risks[<?php echo $index; ?>][description]"
                                              rows="3"><?php echo dol_escape_htmltag($risk['description']); ?></textarea>
                                    </td>
                                                                    <td>
                                    <textarea class="flat minwidth150 task-name"
                                              name="selected_risks[<?php echo $index; ?>][prevention_actions]"
                                              rows="3"
                                              placeholder="<?php echo $langs->trans('PreventionActions'); ?>">Lire le rapport sur les RPS dans la pièce jointe de cette tâche</textarea>
                                </td>
                                </tr>

                                <!-- Données cachées pour chaque risque -->
                                <input type="hidden" name="selected_risks[<?php echo $index; ?>][title]" value="<?php echo dol_escape_htmltag($risk['title']); ?>">
                                <input type="hidden" name="selected_risks[<?php echo $index; ?>][category]" value="<?php echo dol_escape_htmltag($risk['category']); ?>">

                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Modal-Footer -->
            <div class="modal-footer">
                <div id="submit_selected_psychosocial_risks" class="wpeo-button button-primary" disabled style="opacity: 0.6;">
                    <span><i class="fas fa-plus"></i> <?php echo $langs->trans('AddSelectedPsychosocialRisks'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
