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
 *             La qualification, elle, se prend sur le groupement de tete : on la rappelle
 *             ici avec un lien, mais elle ne se modifie pas depuis l'unite. Une unite non
 *             concernee ou a qualifier n'a qu'un rappel de sa qualification.
 *
 *             Les facteurs affiches proviennent de la derniere evaluation consolidee, ou a
 *             defaut des risques psychosociaux deja enregistres sur l'unite.
 *
 * Variables attendues : $rpsUnitLine, $rpsScale, $rpsScaleLabels, $rpsSubCategories, $rpsRootGroupment,
 *                       $rpsRootGroupmentId, $rpsUsers, $object, $langs
 */

global $langs;

if (empty($rpsUnitLine)) {
    return;
}

$rpsUnitState = (int) $rpsUnitLine['state'];
$rpsRootUrl   = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . (int) $rpsRootGroupmentId . '#rps-unit-' . (int) $object->id;
$rpsRootRef   = !empty($rpsRootGroupment) ? $rpsRootGroupment->ref : '';

if ($rpsUnitState !== 1) : ?>
    <div class="rps-scope rps-unit-notice" data-state="<?php echo $rpsUnitState; ?>">
        <span class="rps-head-icon"><i class="fas fa-brain"></i></span>
        <div class="rps-unit-notice-text">
            <span class="rps-head-title">
                Risques psychosociaux
                <span class="rps-state" data-state="<?php echo $rpsUnitState; ?>"><?php echo $rpsUnitState === 0 ? 'Non concernée' : 'À qualifier'; ?></span>
            </span>
            <span class="rps-head-sub">
                <?php if ($rpsUnitState === 0) : ?>
                    Motif : <?php echo dol_escape_htmltag(trim($rpsUnitLine['reason']) !== '' ? $rpsUnitLine['reason'] : 'non renseigné'); ?>
                <?php else : ?>
                    Exposition pas encore tranchée : la ligne des risques psychosociaux apparaîtra ici une fois l'unité déclarée concernée.
                <?php endif; ?>
            </span>
        </div>
        <?php if (!empty($rpsRootGroupmentId)) : ?>
            <a class="wpeo-button button-grey button-size-small" href="<?php echo $rpsRootUrl; ?>">
                Qualifier sur <?php echo dol_escape_htmltag($rpsRootRef); ?> <i class="fas fa-arrow-right"></i>
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

?>
<div class="rps-scope rps-unit-card" data-element="<?php echo (int) $object->id; ?>" data-endpoint="<?php echo dol_buildpath('/digiriskdolibarr/core/ajax/save_rps_assessment.php', 1); ?>"
     data-scale-labels="<?php echo dol_escape_htmltag(json_encode($rpsScaleLabels)); ?>">

    <div class="rps-unit-line">
        <span class="rps-head-icon"><i class="fas fa-brain"></i></span>

        <div class="rps-unit-title">
            <span class="rps-head-title">Risques psychosociaux</span>
            <span class="rps-unit-sub">
                <span class="rps-state" data-state="1">Concernée</span>
                <?php if (!empty($rpsRootGroupmentId)) : ?>
                    qualifiée sur <a href="<?php echo $rpsRootUrl; ?>"><?php echo dol_escape_htmltag($rpsRootRef); ?></a>
                <?php endif; ?>
            </span>
        </div>

        <div class="rps-unit-factors">
            <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) :
                $rpsLevel = (int) ($rpsUnitLine['levels'][$rpsPosition] ?? 0); ?>
                <span class="rps-factor">
                    <span class="rps-cell" data-scale="<?php echo $rpsLevel; ?>"
                          title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label']) . ' — ' . ($rpsLevel ? $rpsScale[$rpsLevel]['label'] : 'non évalué')); ?>"><?php echo $rpsLevel ?: ''; ?></span>
                    <abbr class="rps-factor-key" title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?>">F<?php echo (int) $rpsPosition + 1; ?></abbr>
                </span>
            <?php endforeach; ?>
        </div>

        <div class="rps-unit-score">
            <?php if ($rpsUnitLine['done']) : ?>
                <b class="rps-num"><?php echo number_format($rpsUnitLine['average'], 2, ',', ' '); ?></b>
                <span class="rps-unit-max">/ 4</span>
                <span class="rps-band" data-scale="<?php echo (int) $rpsUnitLine['band']; ?>"><?php echo $rpsScaleLabels[$rpsUnitLine['band']]; ?></span>
            <?php else : ?>
                <span class="rps-unit-none">Non évaluée</span>
            <?php endif; ?>
        </div>

        <div class="rps-unit-date">
            <?php if (!empty($rpsUnitLine['date'])) : ?>
                Évaluée le<br><b><?php echo dol_print_date(strtotime($rpsUnitLine['date']), 'day'); ?></b>
            <?php endif; ?>
            <?php if ($rpsUnitFilled > 0 && $rpsUnitFilled < $rpsUnitTotal) : ?>
                <br><span class="rps-attention"><?php echo $rpsUnitTotal - $rpsUnitFilled; ?> facteur<?php echo ($rpsUnitTotal - $rpsUnitFilled) > 1 ? 's' : ''; ?> à coter</span>
            <?php endif; ?>
        </div>

        <div class="rps-unit-buttons">
            <?php if (!empty($rpsUntreated)) : ?>
                <span class="rps-warn-badge" title="<?php echo dol_escape_htmltag(implode(', ', $rpsUntreatedNames)); ?>">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo count($rpsUntreated); ?> sans action
                </span>
            <?php endif; ?>
            <button type="button" class="wpeo-button button-grey rps-detail-toggle" aria-expanded="false">
                <i class="fas fa-tasks"></i>
                <span class="rps-detail-count"><?php echo !empty($rpsTaskList) ? $rpsDoneCount . '/' . count($rpsTaskList) . ' actions' : 'Actions'; ?></span>
                <i class="fas fa-chevron-down rps-detail-chevron"></i>
            </button>
            <div class="wpeo-button button-blue modal-open rps-open-eval">
                <input type="hidden" class="modal-options" data-modal-to-open="rps_eval_modal">
                <i class="fas fa-clipboard-list"></i>
                <?php echo $rpsUnitFilled > 0 ? 'Revoir' : 'Évaluer'; ?>
            </div>
        </div>
    </div>

    <div class="rps-unit-detail" hidden>
        <div class="rps-unit-detail-factors">
            <h5 class="rps-detail-title">Facteurs</h5>
            <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) :
                $rpsLevel = (int) ($rpsUnitLine['levels'][$rpsPosition] ?? 0); ?>
                <div class="rps-factor-row">
                    <span class="rps-cell" data-scale="<?php echo $rpsLevel; ?>"><?php echo $rpsLevel ?: ''; ?></span>
                    <span class="rps-factor-name"><b>F<?php echo (int) $rpsPosition + 1; ?></b> <?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?></span>
                    <span class="rps-factor-level"><?php echo $rpsLevel ? $rpsScale[$rpsLevel]['label'] : 'non coté'; ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="rps-unit-detail-actions">
            <h5 class="rps-detail-title">Actions de prévention</h5>
            <?php if (empty($rpsTaskList)) : ?>
                <div class="rps-actions-empty">Aucune action de prévention pour l'instant.</div>
            <?php else : ?>
                <div class="rps-actions-list">
                    <?php foreach ($rpsTaskList as $rpsTask) : ?>
                        <div class="rps-action-item<?php echo $rpsTask['done'] ? ' is-done' : ''; ?>" data-task="<?php echo (int) $rpsTask['id']; ?>">
                            <input type="checkbox" class="rps-task-done" title="Marquer l'action comme faite" <?php echo $rpsTask['done'] ? 'checked' : ''; ?>>
                            <a class="rps-action-ref" href="<?php echo DOL_URL_ROOT . '/projet/tasks/task.php?id=' . (int) $rpsTask['id']; ?>"
                               title="Ouvrir la tâche"><?php echo dol_escape_htmltag($rpsTask['ref']); ?></a>
                            <span class="rps-action-label" title="<?php echo dol_escape_htmltag($rpsTask['label']); ?>"><?php echo dol_escape_htmltag($rpsTask['text']); ?></span>
                            <?php if ($rpsTask['factor'] !== null) : ?>
                                <abbr class="rps-task-factor" title="<?php echo dol_escape_htmltag($langs->trans($rpsSubCategories[$rpsTask['factor']]['label'])); ?>">F<?php echo (int) $rpsTask['factor'] + 1; ?></abbr>
                            <?php endif; ?>
                            <select class="rps-task-owner" title="Responsable de l'action">
                                <option value="">Sans responsable</option>
                                <?php foreach ($rpsUsers as $rpsUserId => $rpsUserName) : ?>
                                    <option value="<?php echo (int) $rpsUserId; ?>" <?php echo $rpsTask['owner_id'] == $rpsUserId ? 'selected' : ''; ?>>
                                        <?php echo dol_escape_htmltag($rpsUserName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" class="rps-task-due" title="Échéance"
                                   value="<?php echo !empty($rpsTask['due']) ? dol_print_date(strtotime($rpsTask['due']), '%Y-%m-%d') : ''; ?>">
                            <button type="button" class="wpeo-button button-transparent button-square-30 rps-task-delete" title="Supprimer l'action">
                                <i class="fas fa-trash button-icon"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($rpsUnitLine['assessed'])) : ?>
                <?php // Pas de <form> : le bloc est deja dans le formulaire de la liste des risques ?>
                <div class="rps-task-add">
                    <select class="rps-task-add-factor" title="Facteur visé par l'action">
                        <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) :
                            $rpsLevel = (int) ($rpsUnitLine['levels'][$rpsPosition] ?? 0); ?>
                            <option value="<?php echo (int) $rpsPosition; ?>"<?php echo $rpsPosition === $rpsDefaultFactor ? ' selected' : ''; ?>>
                                F<?php echo (int) $rpsPosition + 1; ?> · <?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?><?php echo $rpsLevel ? ' (' . $rpsScale[$rpsLevel]['label'] . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="rps-task-add-label" placeholder="Nouvelle action de prévention…" autocomplete="off" aria-label="Nouvelle action de prévention">
                    <button type="button" class="wpeo-button button-grey button-size-small rps-task-add-submit"><i class="fas fa-plus"></i> Ajouter</button>
                </div>
            <?php else : ?>
                <div class="rps-actions-empty">Les actions s'ajoutent une fois l'unité évaluée : elles se rattachent à son évaluation.</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (count($rpsUnitLine['orphans']) > 1) : ?>
        <div class="rps-note">
            <span>
                <i class="fas fa-info-circle"></i>
                L'évaluation de cette unité repose encore sur <b><?php echo count($rpsUnitLine['orphans']); ?> risques psychosociaux distincts</b>
                (<?php echo dol_escape_htmltag(implode(', ', array_slice($rpsUnitLine['orphans'], 0, 5))); ?><?php echo count($rpsUnitLine['orphans']) > 5 ? '…' : ''; ?>),
                un par critère. Ils ne figurent pas dans la liste des risques, leur détail se lit ci-dessus.
                Les consolider les remplace par une seule évaluation et range les originaux dans les archives de l'unité.
            </span>
            <button type="button" class="wpeo-button button-grey button-size-small rps-consolidate" data-element="<?php echo (int) $object->id; ?>">
                <i class="fas fa-compress"></i> Consolider en une ligne
            </button>
        </div>
    <?php endif; ?>
</div>
