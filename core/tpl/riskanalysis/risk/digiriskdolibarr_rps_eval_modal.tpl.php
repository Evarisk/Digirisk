<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/tpl/riskanalysis/risk/digiriskdolibarr_rps_eval_modal.tpl.php
 * \ingroup    digiriskdolibarr
 * \brief      Evaluation des risques psychosociaux d'une unite de travail.
 *
 *             Une seule fenetre, dans laquelle se choisit la methode parmi celles activees
 *             en configuration :
 *             - "Faire le point" : 8 facteurs, 3 niveaux de cotation,
 *             - grille RPS-DU de l'INRS (ED 6403) : 26 criteres, 4 niveaux.
 *
 *             Les deux aboutissent au meme enregistrement : une evaluation unique sur le
 *             risque psychosocial de l'unite, le detail partant dans rpsanswer. La liste des
 *             risques ne gagne jamais de ligne, quelle que soit la methode.
 *
 *             Les sous-categories 6 et 7 decrivent le contexte de prevention et l'impact :
 *             saisissables avec "Faire le point", conservees, mais hors cotation.
 *
 * Variables attendues : $object, $rpsSubCategories, $rpsSubCategoriesAll, $rpsScale,
 *                       $rpsAssessmentByElement, $langs
 */

global $langs;

$rpsPointEnabled = getDolGlobalInt('DIGIRISKDOLIBARR_PSYCHOSOCIAL_RISK_METHOD', 1);
$rpsGridEnabled  = getDolGlobalInt('DIGIRISKDOLIBARR_PSYCHOSOCIAL_RISK_GRID_METHOD', 1);
$rpsGridCriteria = $rpsGridEnabled ? Risk::getPsychosocialRiskGrid() : [];

if (!$rpsPointEnabled && empty($rpsGridCriteria)) {
    return;
}

$rpsPrevious = $rpsAssessmentByElement[(int) $object->id]['criteria'] ?? [];

// Les reponses de "Faire le point" sont numerotees a partir de 900 pour ne pas entrer en
// collision avec les 26 criteres de la grille, qui vivent dans la meme table.
$rpsPointOffset = 900;

// La fenetre s'ouvre sur la methode de la derniere evaluation, sinon sur la grille
$rpsUsedPoint    = false;
foreach (array_keys($rpsPrevious) as $rpsCriterion) {
    if ($rpsCriterion >= $rpsPointOffset) {
        $rpsUsedPoint = true;
    }
}
$rpsActiveMethod = ($rpsUsedPoint || empty($rpsGridCriteria)) ? 'point' : 'grid';
if ($rpsActiveMethod == 'point' && !$rpsPointEnabled) {
    $rpsActiveMethod = 'grid';
}

// Les 4 reponses de la grille imprimee, dans son ordre de lecture
$rpsGridAnswers = ['PsychosocialGridAnswerNever', 'PsychosocialGridAnswerSometimes', 'PsychosocialGridAnswerOften', 'PsychosocialGridAnswerAlways'];
$rpsGridFamily  = '';

// "Faire le point" cote sur trois niveaux : le quatrieme palier n'est pas propose
$rpsPointScale = [1 => $rpsScale[1], 2 => $rpsScale[2], 3 => $rpsScale[3]];

?>
<div class="rps-modal-overlay" id="rps-eval-overlay" data-element="<?php echo (int) $object->id; ?>" data-method="<?php echo $rpsActiveMethod; ?>">
    <div class="rps-modal rps-modal-wide">

        <div class="rps-modal-head">
            <div>
                <h2><i class="fas fa-brain"></i> Évaluation des risques psychosociaux</h2>
                <div class="sub">
                    <?php echo dol_escape_htmltag($object->ref . ' · ' . $object->label); ?> &middot;
                    l'enregistrement produit <b>une seule évaluation</b> sur le risque psychosocial de l'unité,
                    quelle que soit la méthode.
                </div>
            </div>
            <button type="button" class="rps-modal-close" aria-label="Fermer">&times;</button>
        </div>

        <?php if ($rpsPointEnabled && !empty($rpsGridCriteria)) : ?>
            <div class="rps-method-bar">
                <span class="rps-method-label">Méthode</span>
                <span class="rps-method-switch">
                    <button type="button" class="rps-method<?php echo $rpsActiveMethod == 'point' ? ' is-on' : ''; ?>" data-method="point">
                        <?php echo dol_escape_htmltag($langs->trans('PsychosocialRiskMethod')); ?>
                        <small><?php echo count($rpsSubCategoriesAll); ?> facteurs · 3 niveaux</small>
                    </button>
                    <button type="button" class="rps-method<?php echo $rpsActiveMethod == 'grid' ? ' is-on' : ''; ?>" data-method="grid">
                        <?php echo dol_escape_htmltag($langs->trans('PsychosocialRiskGridMethod')); ?>
                        <small><?php echo count($rpsGridCriteria); ?> critères · 4 niveaux</small>
                    </button>
                </span>
                <span class="rps-method-hint">Changer de méthode remet la saisie à zéro.</span>
            </div>
        <?php endif; ?>

        <div class="rps-modal-toolbar">
            <label>Date de l'évaluation
                <input type="date" class="rps-input" id="rps-eval-date" value="<?php echo dol_print_date(dol_now('tzuser'), '%Y-%m-%d'); ?>">
            </label>
            <label class="rps-grow">Observation
                <input type="text" class="rps-input" id="rps-eval-comment" placeholder="Participants, contexte, éléments factuels relevés...">
            </label>
            <span class="rps-grid-progress"><b id="rps-eval-answered">0</b>/<span id="rps-eval-total">0</span> cotés</span>
        </div>

        <div class="rps-modal-body">

            <?php if ($rpsPointEnabled) : ?>
                <div class="rps-pane" data-method="point">
                    <?php foreach ($rpsSubCategoriesAll as $rpsPosition => $rpsSub) :
                        $rpsCriterion = $rpsPointOffset + (int) $rpsPosition;
                        $rpsPicked    = $rpsPrevious[$rpsCriterion] ?? 0;
                        $rpsIsContext = $rpsPosition > 5; ?>
                        <div class="rps-grid-row<?php echo $rpsIsContext ? ' is-context' : ''; ?>"
                             data-criterion="<?php echo $rpsCriterion; ?>"
                             data-sub-category="<?php echo (int) $rpsPosition; ?>"
                             data-label="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?>">
                            <div class="rps-grid-criterion">
                                <span class="rps-grid-name"><?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?></span>
                                <span class="rps-grid-question">
                                    <?php echo $rpsIsContext ? 'Contexte : relevé avec l\'évaluation, sans entrer dans la cotation' : 'Facteur d\'exposition'; ?>
                                </span>
                            </div>
                            <div class="rps-grid-answers">
                                <?php foreach ($rpsPointScale as $rpsLevel => $rpsMeta) : ?>
                                    <button type="button" class="rps-grid-answer scale-<?php echo $rpsLevel; ?><?php echo $rpsPicked == $rpsLevel ? ' is-picked' : ''; ?>"
                                            data-scale="<?php echo $rpsLevel; ?>"
                                            title="<?php echo dol_escape_htmltag($rpsMeta['range'] . ' · ' . $rpsMeta['label']); ?>">
                                        <?php echo dol_escape_htmltag($rpsMeta['label']); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($rpsGridCriteria)) : ?>
                <div class="rps-pane" data-method="grid">
                    <div class="rps-pane-source"><i class="fas fa-circle-info"></i> <?php echo dol_escape_htmltag($langs->trans('PsychosocialGridSource')); ?></div>
                    <?php foreach ($rpsGridCriteria as $rpsCriterionData) : ?>
                        <?php if ($rpsCriterionData['family'] != $rpsGridFamily) :
                            $rpsGridFamily = $rpsCriterionData['family']; ?>
                            <div class="rps-grid-family"><?php echo dol_escape_htmltag($rpsGridFamily); ?></div>
                        <?php endif; ?>

                        <?php
                        $rpsPosition = (int) $rpsCriterionData['position'];
                        $rpsPicked   = $rpsPrevious[$rpsPosition] ?? 0;
                        // Un critere qui decrit une ressource se lit a l'envers : repondre
                        // "Jamais" y est la reponse rouge, alors que c'est la verte sur une contrainte.
                        $rpsScales = !empty($rpsCriterionData['reversed']) ? [4, 3, 2, 1] : [1, 2, 3, 4];
                        ?>
                        <div class="rps-grid-row" data-criterion="<?php echo $rpsPosition; ?>"
                             data-sub-category="<?php echo (int) $rpsCriterionData['subCategory']; ?>"
                             data-label="<?php echo dol_escape_htmltag($rpsCriterionData['name']); ?>">
                            <div class="rps-grid-criterion">
                                <span class="rps-grid-name"><?php echo $rpsPosition . '. ' . dol_escape_htmltag($rpsCriterionData['name']); ?></span>
                                <span class="rps-grid-question"><?php echo dol_escape_htmltag($rpsCriterionData['question']); ?></span>
                            </div>
                            <div class="rps-grid-answers">
                                <?php foreach ($rpsGridAnswers as $rpsIndex => $rpsAnswerLabel) :
                                    $rpsLevel = $rpsScales[$rpsIndex]; ?>
                                    <button type="button" class="rps-grid-answer scale-<?php echo $rpsLevel; ?><?php echo $rpsPicked == $rpsLevel ? ' is-picked' : ''; ?>"
                                            data-scale="<?php echo $rpsLevel; ?>"
                                            title="<?php echo dol_escape_htmltag($rpsScale[$rpsLevel]['range'] . ' · ' . $rpsScale[$rpsLevel]['label']); ?>">
                                        <?php echo dol_escape_htmltag($langs->trans($rpsAnswerLabel)); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="rps-actions-block" id="rps-eval-actions">
                <div class="rps-actions-head">
                    <span class="rps-actions-title">Actions de prévention</span>
                    <span class="rps-actions-hint">
                        Une action peut être engagée sur n'importe quel facteur ; ceux cotés Élevé ou Extrême
                        sont signalés comme à traiter. Elles sont créées comme tâches du projet du document
                        unique, rattachées au risque psychosocial de l'unité.
                    </span>
                </div>
                <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) : ?>
                    <div class="rps-action-row" data-factor="<?php echo (int) $rpsPosition; ?>">
                        <span class="rps-cell" data-action-cell></span>
                        <span class="rps-action-factor"><?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?></span>
                        <input type="text" class="rps-input rps-action-input"
                               data-factor-label="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?>"
                               placeholder="Action de prévention à engager...">
                        <select class="rps-input rps-action-owner" title="Responsable de l'action">
                            <option value="">Responsable…</option>
                            <?php foreach ($rpsUsers as $rpsUserId => $rpsUserName) : ?>
                                <option value="<?php echo (int) $rpsUserId; ?>"><?php echo dol_escape_htmltag($rpsUserName); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" class="rps-input rps-action-due" title="Échéance de l'action">
                    </div>
                <?php endforeach; ?>
                <button type="button" class="rps-actions-more" id="rps-actions-more">
                    <i class="fas fa-chevron-down"></i> <span>Agir aussi sur un facteur en dessous du seuil</span>
                </button>

            </div>

        </div>

        <div class="rps-modal-foot">
            <div class="rps-grid-summary">
                <div class="rps-grid-factors">
                    <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) : ?>
                        <span class="rps-factor">
                            <span class="rps-cell rps-cell-empty" data-factor="<?php echo (int) $rpsPosition; ?>"></span>
                            <abbr title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?>">F<?php echo (int) $rpsPosition + 1; ?></abbr>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div>
                    <div class="rps-metric-label">Cotation retenue pour l'unité</div>
                    <div><span class="rps-pill rps-pill-band" id="rps-eval-band" style="background:#f1f2f5;color:#9aa1ab">non cotée</span></div>
                </div>
            </div>
            <div class="rps-grid-error" id="rps-eval-error" hidden></div>
            <div class="rps-modal-actions">
                <button type="button" class="rps-button rps-modal-close">Annuler</button>
                <button type="button" class="rps-button rps-button-primary" id="rps-eval-save" disabled>
                    <i class="fas fa-check"></i> Enregistrer l'évaluation
                </button>
            </div>
        </div>
    </div>
</div>
