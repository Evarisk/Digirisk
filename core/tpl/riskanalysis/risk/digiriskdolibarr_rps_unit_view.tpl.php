<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/tpl/riskanalysis/risk/digiriskdolibarr_rps_unit_view.tpl.php
 * \ingroup    digiriskdolibarr
 * \brief      Ligne des risques psychosociaux de l'unite de travail consultee, affichee en
 *             haut de ses risques : un bandeau d'une ligne quand elle est declaree concernee,
 *             dont le detail (facteurs et actions de prevention) se deplie.
 *
 *             La qualification se prend ici, sur la pastille de l'unite, ou pour plusieurs unites
 *             a la fois au niveau 0, sur l'onglet Risques psychosociaux de la societe, vers lequel
 *             renvoie un lien. Une unite non concernee ou a qualifier n'a qu'un bandeau de sa
 *             qualification.
 *
 *             Les facteurs affiches proviennent de la derniere evaluation consolidee, ou a
 *             defaut des risques psychosociaux deja enregistres sur l'unite.
 *
 * Variables attendues : $rpsUnitLine, $rpsLines, $rpsScale, $rpsScaleLabels, $rpsSubCategories, $rpsUsers,
 *                       $rpsAssessmentByElement, $rpsJsLabels, $object, $langs
 */

global $db, $langs, $user;

// The actions open in the Digirisk task card
require_once __DIR__ . '/../../../../lib/digiriskdolibarr_task.lib.php';

if (empty($rpsUnitLine)) {
    return;
}

$rpsUnitState = (int) $rpsUnitLine['state'];
// Le lien vers le tableau de toutes les unites suit les droits de l'onglet du niveau 0
$rpsRootAccess = $user->hasRight('digiriskdolibarr', 'digiriskstandard', 'read');
$rpsRootUrl    = dol_buildpath('/digiriskdolibarr/view/digiriskstandard/digiriskstandard_psychosocial.php', 1) . '#rps-unit-' . (int) $object->id;
$rpsRootRef    = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');
$rpsEndpoint   = dol_buildpath('/digiriskdolibarr/core/ajax/save_rps_assessment.php', 1);

// La pastille de l'etat devient la liste du niveau 0 avec le droit qu'exige son enregistrement.
// Une unite evaluee, ou qui porte encore des risques psychosociaux, reste concernee tant qu'elle
// n'est pas declaree non concernee : "a qualifier" ne lui est pas propose, l'affichage l'ignorerait
// (digiriskdolibarr_rps_scope_data.tpl.php)
$rpsCanQualify    = $user->hasRight('digiriskdolibarr', 'risk', 'write');
$rpsQualifyStates = [1 => $langs->trans('PsychosocialStateConcerned'), 0 => $langs->trans('PsychosocialStateNotConcerned')];
if (empty($rpsUnitLine['assessed']) && empty($rpsUnitLine['orphans'])) {
    $rpsQualifyStates[-1] = $langs->trans('PsychosocialStateToQualify');
}

if ($rpsCanQualify) {
    $rpsStateControl  = '<span class="rps-state-select" data-state="' . $rpsUnitState . '">';
    $rpsStateControl .= '<select class="rps-state-input" aria-label="' . dol_escape_htmltag($langs->transnoentities('PsychosocialQualificationOf', $object->ref)) . '">';
    foreach ($rpsQualifyStates as $rpsStateValue => $rpsStateLabel) {
        $rpsStateControl .= '<option value="' . $rpsStateValue . '"' . ($rpsStateValue === $rpsUnitState ? ' selected' : '') . '>' . $rpsStateLabel . '</option>';
    }
    $rpsStateControl .= '</select><i class="fas fa-chevron-down"></i></span>';
} else {
    $rpsStateLabels  = [1 => 'PsychosocialStateConcerned', 0 => 'PsychosocialStateNotConcerned', -1 => 'PsychosocialStateToQualify'];
    $rpsStateControl = '<span class="rps-state" data-state="' . $rpsUnitState . '">' . $langs->trans($rpsStateLabels[$rpsUnitState]) . '</span>';
}

// Motifs deja donnes aux autres unites, proposes d'un clic par la bulle du motif
$rpsKnownReasons = [];
foreach ($rpsLines as $rpsLine) {
    if ($rpsLine['state'] === 0 && trim($rpsLine['reason']) !== '') {
        $rpsKnownReasons[trim($rpsLine['reason'])] = true;
    }
}
$rpsKnownReasons = array_keys($rpsKnownReasons);

// Attributs lus par js/modules/psychosocialScope.js pour enregistrer la qualification et l'evaluation
$rpsBlockData  = ' data-element="' . (int) $object->id . '" data-ref="' . dol_escape_htmltag($object->ref) . '" data-endpoint="' . $rpsEndpoint . '"';
$rpsBlockData .= ' data-scale-labels="' . dol_escape_htmltag(json_encode($rpsScaleLabels)) . '"';
$rpsBlockData .= ' data-labels="' . dol_escape_htmltag(json_encode($rpsJsLabels)) . '"';
$rpsBlockData .= ' data-reasons="' . dol_escape_htmltag(json_encode($rpsKnownReasons)) . '"';

if ($rpsUnitState !== 1) : ?>
    <div class="rps-scope rps-unit-notice" data-state="<?php echo $rpsUnitState; ?>"<?php echo $rpsBlockData; ?>>
        <span class="rps-head-icon"><i class="fas fa-brain"></i></span>
        <div class="rps-unit-notice-text">
            <span class="rps-head-title">
                <?php echo $langs->trans('PsychosocialRisks'); ?>
                <?php echo $rpsStateControl; ?>
            </span>
            <span class="rps-head-sub">
                <?php if ($rpsUnitState === 0) :
                    $rpsReasonText = trim($rpsUnitLine['reason']) !== '' ? dol_escape_htmltag($rpsUnitLine['reason']) : $langs->trans('PsychosocialReasonMissing'); ?>
                    <?php echo $langs->trans('PsychosocialReasonLabel'); ?>
                    <?php if ($rpsCanQualify) : ?>
                        <button type="button" class="rps-reason" data-reason="<?php echo dol_escape_htmltag(trim($rpsUnitLine['reason'])); ?>" title="<?php echo dol_escape_htmltag($langs->trans('PsychosocialEditReason')); ?>">
                            <span class="rps-reason-text"><?php echo $rpsReasonText; ?></span> <i class="fas fa-pen"></i>
                        </button>
                    <?php else : ?>
                        <?php echo $rpsReasonText; ?>
                    <?php endif; ?>
                <?php else : ?>
                    <?php echo $langs->trans('PsychosocialUnitToQualifyNotice'); ?>
                <?php endif; ?>
            </span>
        </div>
        <?php if ($rpsRootAccess) : ?>
            <a class="wpeo-button button-grey button-size-small" href="<?php echo $rpsRootUrl; ?>">
                <?php echo dol_escape_htmltag($langs->transnoentities('PsychosocialAllUnitsOn', $rpsRootRef)); ?> <i class="fas fa-arrow-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php
    return;
endif;

$rpsUnitFilled = count($rpsUnitLine['levels']);
$rpsUnitTotal  = count($rpsSubCategories);
$rpsTaskList   = $rpsUnitLine['tasks'] ?? [];
$rpsUntreated  = $rpsUnitLine['untreated'] ?? [];
$rpsDoneCount  = 0;
foreach ($rpsTaskList as $rpsTask) {
    if ($rpsTask['done']) {
        $rpsDoneCount++;
    }
}

// Facteur propose d'emblee pour une nouvelle action : le premier a traiter sans action, sinon le plus severe
$rpsDefaultFactor = $rpsUntreated[0] ?? null;
if ($rpsDefaultFactor === null && $rpsUnitFilled > 0) {
    $rpsDefaultFactor = array_search(max($rpsUnitLine['levels']), $rpsUnitLine['levels']);
}

$rpsUntreatedNames = [];
foreach ($rpsUntreated as $rpsPosition) {
    $rpsUntreatedNames[] = 'F' . ((int) $rpsPosition + 1) . ' ' . $langs->trans($rpsSubCategories[$rpsPosition]['label']);
}

// Rapport de la derniere evaluation consolidee (photos et documents joints a sa saisie)
$rpsLastAssessment = null;
if (!empty($rpsAssessmentByElement[(int) $object->id]['id'])) {
    $rpsLastAssessment = new RiskAssessment($db);
    if ($rpsLastAssessment->fetch($rpsAssessmentByElement[(int) $object->id]['id']) <= 0) {
        $rpsLastAssessment = null;
    }
}

?>
<div class="rps-scope rps-unit-card"<?php echo $rpsBlockData; ?>>

    <div class="rps-unit-line">
        <span class="rps-head-icon"><i class="fas fa-brain"></i></span>

        <div class="rps-unit-title">
            <span class="rps-head-title">
                <?php echo $langs->trans('PsychosocialRisks'); ?>
                <?php echo $rpsStateControl; ?>
            </span>
            <?php if ($rpsRootAccess) : ?>
                <span class="rps-unit-sub">
                    <a href="<?php echo $rpsRootUrl; ?>"><?php echo dol_escape_htmltag($langs->transnoentities('PsychosocialAllUnitsOn', $rpsRootRef)); ?></a>
                </span>
            <?php endif; ?>
        </div>

        <div class="rps-unit-factors">
            <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) :
                $rpsLevel = (int) ($rpsUnitLine['levels'][$rpsPosition] ?? 0); ?>
                <span class="rps-factor">
                    <span class="rps-cell" data-scale="<?php echo $rpsLevel; ?>"
                          title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label']) . ' — ' . ($rpsLevel ? $rpsScale[$rpsLevel]['label'] : $langs->trans('PsychosocialFactorNotAssessed'))); ?>"><?php echo $rpsLevel ?: ''; ?></span>
                    <abbr class="rps-factor-key" title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?>">F<?php echo (int) $rpsPosition + 1; ?></abbr>
                </span>
            <?php endforeach; ?>
        </div>

        <div class="rps-unit-score">
            <?php if ($rpsUnitLine['done']) : ?>
                <b class="rps-num"><?php echo price($rpsUnitLine['average'], 0, $langs, 0, 2, 2); ?></b>
                <span class="rps-unit-max">/ 4</span>
                <span class="rps-band" data-scale="<?php echo (int) $rpsUnitLine['band']; ?>"><?php echo $rpsScaleLabels[$rpsUnitLine['band']]; ?></span>
            <?php else : ?>
                <span class="rps-unit-none"><?php echo $langs->trans('PsychosocialUnitNotAssessed'); ?></span>
            <?php endif; ?>
        </div>

        <div class="rps-unit-date">
            <?php if (!empty($rpsUnitLine['date'])) : ?>
                <?php echo $langs->trans('PsychosocialAssessedOn'); ?><br><b><?php echo dol_print_date(strtotime($rpsUnitLine['date']), 'day'); ?></b>
            <?php endif; ?>
            <?php if ($rpsUnitFilled > 0 && $rpsUnitFilled < $rpsUnitTotal) : ?>
                <br><span class="rps-attention"><?php echo $langs->trans(($rpsUnitTotal - $rpsUnitFilled) > 1 ? 'PsychosocialFactorsToRate' : 'PsychosocialFactorToRate', $rpsUnitTotal - $rpsUnitFilled); ?></span>
            <?php endif; ?>
        </div>

        <div class="rps-unit-buttons">
            <?php if (!empty($rpsUntreated)) : ?>
                <span class="rps-warn-badge" title="<?php echo dol_escape_htmltag(implode(', ', $rpsUntreatedNames)); ?>">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $langs->trans('PsychosocialWithoutAction', count($rpsUntreated)); ?>
                </span>
            <?php endif; ?>
            <button type="button" class="wpeo-button button-grey rps-detail-toggle" aria-expanded="false">
                <i class="fas fa-tasks"></i>
                <span class="rps-detail-count"><?php echo !empty($rpsTaskList) ? $langs->trans('PsychosocialActionsDone', $rpsDoneCount, count($rpsTaskList)) : $langs->trans('PsychosocialActions'); ?></span>
                <i class="fas fa-chevron-down rps-detail-chevron"></i>
            </button>
            <div class="wpeo-button button-blue modal-open rps-open-eval">
                <input type="hidden" class="modal-options" data-modal-to-open="rps_eval_modal">
                <i class="fas fa-clipboard-list"></i>
                <?php echo $langs->trans($rpsUnitFilled > 0 ? 'PsychosocialReview' : 'PsychosocialAssess'); ?>
            </div>
        </div>
    </div>

    <div class="rps-unit-detail" hidden>
        <div class="rps-unit-detail-factors">
            <h5 class="rps-detail-title"><?php echo $langs->trans('PsychosocialFactors'); ?></h5>
            <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) :
                $rpsLevel = (int) ($rpsUnitLine['levels'][$rpsPosition] ?? 0); ?>
                <div class="rps-factor-row">
                    <span class="rps-cell" data-scale="<?php echo $rpsLevel; ?>"><?php echo $rpsLevel ?: ''; ?></span>
                    <span class="rps-factor-name"><b>F<?php echo (int) $rpsPosition + 1; ?></b> <?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?></span>
                    <span class="rps-factor-level"><?php echo $rpsLevel ? $rpsScale[$rpsLevel]['label'] : $langs->trans('PsychosocialFactorNotRated'); ?></span>
                </div>
            <?php endforeach; ?>

            <?php if ($rpsLastAssessment !== null) : ?>
                <h5 class="rps-detail-title rps-report-title"><?php echo $langs->trans('PsychosocialAssessmentReportOf', dol_print_date($rpsLastAssessment->date_riskassessment, 'day')); ?></h5>
                <div class="rps-report">
                    <?php // The report unfolds in the page, not in a modal: its thumbnail loads with it
                    print digiriskRiskAssessmentMediaBlock('riskassessment/' . $rpsLastAssessment->ref, $rpsLastAssessment, true, false); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="rps-unit-detail-actions">
            <h5 class="rps-detail-title"><?php echo $langs->trans('PreventionActions'); ?></h5>
            <?php if (empty($rpsTaskList)) : ?>
                <div class="rps-actions-empty"><?php echo $langs->trans('PsychosocialNoPreventionAction'); ?></div>
            <?php else : ?>
                <div class="rps-actions-list">
                    <?php foreach ($rpsTaskList as $rpsTask) : ?>
                        <div class="rps-action-item<?php echo $rpsTask['done'] ? ' is-done' : ''; ?>" data-task="<?php echo (int) $rpsTask['id']; ?>">
                            <input type="checkbox" class="rps-task-done" title="<?php echo dol_escape_htmltag($langs->trans('PsychosocialMarkActionDone')); ?>" <?php echo $rpsTask['done'] ? 'checked' : ''; ?>>
                            <a class="rps-action-ref" href="<?php echo digiriskdolibarr_task_card_url((int) $rpsTask['id']); ?>"
                               title="<?php echo dol_escape_htmltag($langs->trans('PsychosocialOpenTask')); ?>"><?php echo dol_escape_htmltag($rpsTask['ref']); ?></a>
                            <span class="rps-action-label" title="<?php echo dol_escape_htmltag($rpsTask['label']); ?>"><?php echo dol_escape_htmltag($rpsTask['text']); ?></span>
                            <?php if ($rpsTask['factor'] !== null) : ?>
                                <abbr class="rps-task-factor" title="<?php echo dol_escape_htmltag($langs->trans($rpsSubCategories[$rpsTask['factor']]['label'])); ?>">F<?php echo (int) $rpsTask['factor'] + 1; ?></abbr>
                            <?php endif; ?>
                            <select class="rps-task-owner" title="<?php echo dol_escape_htmltag($langs->trans('PsychosocialActionOwner')); ?>">
                                <option value=""><?php echo $langs->trans('PsychosocialNoOwner'); ?></option>
                                <?php foreach ($rpsUsers as $rpsUserId => $rpsUserName) : ?>
                                    <option value="<?php echo (int) $rpsUserId; ?>" <?php echo $rpsTask['owner_id'] == $rpsUserId ? 'selected' : ''; ?>>
                                        <?php echo dol_escape_htmltag($rpsUserName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" class="rps-task-due" title="<?php echo dol_escape_htmltag($langs->trans('PsychosocialDueDate')); ?>"
                                   value="<?php echo !empty($rpsTask['due']) ? dol_print_date(strtotime($rpsTask['due']), '%Y-%m-%d') : ''; ?>">
                            <button type="button" class="wpeo-button button-transparent button-square-30 rps-task-delete" title="<?php echo dol_escape_htmltag($langs->trans('PsychosocialDeleteAction')); ?>">
                                <i class="fas fa-trash button-icon"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($rpsUnitLine['assessed'])) : ?>
                <?php // Pas de <form> : le bloc est deja dans le formulaire de la liste des risques ?>
                <div class="rps-task-add">
                    <select class="rps-task-add-factor" title="<?php echo dol_escape_htmltag($langs->trans('PsychosocialActionFactor')); ?>">
                        <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) :
                            $rpsLevel = (int) ($rpsUnitLine['levels'][$rpsPosition] ?? 0); ?>
                            <option value="<?php echo (int) $rpsPosition; ?>"<?php echo $rpsPosition === $rpsDefaultFactor ? ' selected' : ''; ?>>
                                F<?php echo (int) $rpsPosition + 1; ?> · <?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?><?php echo $rpsLevel ? ' (' . $rpsScale[$rpsLevel]['label'] . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="rps-task-add-label" placeholder="<?php echo dol_escape_htmltag($langs->trans('PsychosocialNewPreventionAction')); ?>…" autocomplete="off" aria-label="<?php echo dol_escape_htmltag($langs->trans('PsychosocialNewPreventionAction')); ?>">
                    <button type="button" class="wpeo-button button-grey button-size-small rps-task-add-submit"><i class="fas fa-plus"></i> <?php echo $langs->trans('Add'); ?></button>
                </div>
            <?php else : ?>
                <div class="rps-actions-empty"><?php echo $langs->trans('PsychosocialActionsAfterAssessment'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (count($rpsUnitLine['orphans']) > 1) :
        // Les references des risques sont des donnees : echappees a part, puis glissees a la place du second %s
        $rpsOrphanRefs = implode(', ', array_slice($rpsUnitLine['orphans'], 0, 5)) . (count($rpsUnitLine['orphans']) > 5 ? '…' : ''); ?>
        <div class="rps-note">
            <span>
                <i class="fas fa-info-circle"></i>
                <?php echo str_replace('%s', dol_escape_htmltag($rpsOrphanRefs), $langs->trans('PsychosocialOrphansNote', count($rpsUnitLine['orphans']), '%s')); ?>
            </span>
            <button type="button" class="wpeo-button button-grey button-size-small rps-consolidate" data-element="<?php echo (int) $object->id; ?>">
                <i class="fas fa-compress"></i> <?php echo $langs->trans('PsychosocialConsolidateOneLine'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
