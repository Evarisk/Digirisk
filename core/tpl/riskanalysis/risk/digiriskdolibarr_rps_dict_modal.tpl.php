<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/tpl/riskanalysis/risk/digiriskdolibarr_rps_dict_modal.tpl.php
 * \ingroup    digiriskdolibarr
 * \brief      Criteres de la grille RPS-DU, ouverts depuis le bloc de qualification.
 *             Consultables seulement : la grille deviendra un dictionnaire modifiable et versionne.
 *
 *             Le comportement du bloc est dans js/modules/psychosocialScope.js, son style dans
 *             css/scss/page/_page-rps.scss.
 *
 * Variables attendues : $rpsScale, $rpsSubCategories (definis par le perimetre)
 */

global $langs;

if (empty($rpsScale)) {
    return;
}

$rpsGridCriteria = Risk::getPsychosocialRiskGrid();
$rpsGridFamily   = '';

?>
<!-- psychosocialRiskGrid.json devient un dictionnaire modifiable et versionne -->
<div class="wpeo-modal" id="rps_dict_modal">
    <div class="modal-container rps-modal-container">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-sliders-h"></i> Critères d'évaluation des risques psychosociaux</h2>
            <div class="modal-close"><i class="fas fa-times"></i></div>
        </div>

        <div class="modal-content">
            <div class="rps-modal-sub">
                <?php echo dol_escape_htmltag($langs->trans('PsychosocialGridSource')); ?> &middot;
                <?php echo count($rpsGridCriteria); ?> critères, <?php echo count($rpsSubCategories); ?> facteurs.
                Aujourd'hui figés dans <code>js/json/psychosocialRiskGrid.json</code> : la proposition est d'en faire un
                dictionnaire modifiable et <b>versionné</b>, chaque évaluation conservant la version qui l'a produite.
            </div>
            <table class="rps-dict-table">
                <thead>
                <tr>
                    <th class="rps-dict-position">N°</th>
                    <th>Critère</th>
                    <th class="rps-dict-factor">Facteur</th>
                    <th class="rps-dict-direction">Sens</th>
                    <th class="rps-dict-active">Active</th>
                    <th class="rps-dict-edit"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rpsGridCriteria as $criterion) : ?>
                    <?php if ($criterion['family'] != $rpsGridFamily) :
                        $rpsGridFamily = $criterion['family']; ?>
                        <tr class="rps-dict-family"><td colspan="6"><?php echo dol_escape_htmltag($rpsGridFamily); ?></td></tr>
                    <?php endif; ?>
                    <tr>
                        <td class="rps-dict-position"><?php echo (int) $criterion['position']; ?></td>
                        <td>
                            <?php echo dol_escape_htmltag($criterion['name']); ?>
                            <span class="rps-dict-question"><?php echo dol_escape_htmltag($criterion['question']); ?></span>
                        </td>
                        <td class="rps-dict-factor rps-dict-muted">
                            <?php
                            $subPosition = (int) $criterion['subCategory'];
                            echo isset($rpsSubCategories[$subPosition]) ? dol_escape_htmltag($langs->trans($rpsSubCategories[$subPosition]['label'])) : '&mdash;';
                            ?>
                        </td>
                        <td class="rps-dict-direction rps-dict-muted" title="<?php echo !empty($criterion['reversed']) ? 'Ressource : « Jamais » est la réponse la plus grave' : 'Contrainte : « Jamais » est la réponse la plus faible'; ?>">
                            <?php echo !empty($criterion['reversed']) ? 'ressource' : 'contrainte'; ?>
                        </td>
                        <td class="rps-dict-active"><i class="fas fa-toggle-on"></i></td>
                        <td class="rps-dict-edit">
                            <button type="button" class="wpeo-button button-transparent button-square-30"><i class="fas fa-pencil-alt button-icon"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="modal-footer">
            <span class="rps-dict-foot-note">
                Désactiver un critère le retire des futures évaluations sans effacer les réponses déjà collectées :
                les évaluations signées restent lisibles avec le libellé en vigueur à leur date.
            </span>
            <div class="rps-modal-actions">
                <button type="button" class="wpeo-button button-grey"><i class="fas fa-undo-alt"></i> Restaurer la grille INRS</button>
                <button type="button" class="wpeo-button button-blue"><i class="fas fa-plus"></i> Nouveau critère</button>
            </div>
        </div>
    </div>
</div>
