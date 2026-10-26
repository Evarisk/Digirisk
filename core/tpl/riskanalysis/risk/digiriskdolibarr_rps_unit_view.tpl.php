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
 *             haut de ses risques quand elle est declaree concernee.
 *
 *             La qualification, elle, se prend sur le groupement de tete : on la rappelle
 *             ici avec un lien, mais elle ne se modifie pas depuis l'unite.
 *
 *             Les facteurs affiches proviennent de la derniere evaluation consolidee, ou a
 *             defaut des risques psychosociaux deja enregistres sur l'unite.
 *
 * Variables attendues : $rpsUnitLine, $rpsScale, $rpsScaleLabels, $rpsSubCategories, $rpsRootGroupment,
 *                       $object, $langs
 */

global $langs;

if (empty($rpsUnitLine)) {
    return;
}

$rpsUnitFilled = count($rpsUnitLine['levels']);
$rpsUnitTotal  = count($rpsSubCategories);

?>
<div class="rps-scope rps-unit-card" data-element="<?php echo (int) $object->id; ?>" data-endpoint="<?php echo dol_buildpath('/digiriskdolibarr/core/ajax/save_rps_assessment.php', 1); ?>"
     data-scale-labels="<?php echo dol_escape_htmltag(json_encode($rpsScaleLabels)); ?>">

    <div class="rps-head">
        <span class="rps-head-icon"><i class="fas fa-brain"></i></span>
        <div class="rps-head-text">
            <span class="rps-head-title">
                Risques psychosociaux
                <span class="rps-tag">Unité concernée</span>
            </span>
            <span class="rps-head-sub"><?php echo dol_escape_htmltag($rpsUnitLine['ref'] . ' · ' . $rpsUnitLine['label']); ?></span>
        </div>

        <div class="rps-head-metrics">
            <div class="rps-metric">
                <span class="rps-metric-label">Cotation</span>
                <span class="rps-metric-value">
                    <b class="rps-num"><?php echo $rpsUnitLine['done'] ? number_format($rpsUnitLine['average'], 2, ',', ' ') : '—'; ?></b> / 4
                </span>
            </div>
            <span class="rps-band" data-scale="<?php echo $rpsUnitLine['done'] ? (int) $rpsUnitLine['band'] : 0; ?>">
                <?php echo $rpsUnitLine['done'] ? $rpsScaleLabels[$rpsUnitLine['band']] : 'non cotée'; ?>
            </span>
            <div class="rps-metric">
                <span class="rps-metric-label"><b><?php echo $rpsUnitFilled; ?></b>/<b><?php echo $rpsUnitTotal; ?></b> facteurs cotés</span>
                <progress class="rps-progress" max="100" value="<?php echo $rpsUnitTotal > 0 ? round(100 * $rpsUnitFilled / $rpsUnitTotal) : 0; ?>"></progress>
            </div>
        </div>
    </div>

    <div class="rps-body">
        <div class="rps-unit-line">
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

            <div class="rps-unit-state">
                <?php if ($rpsUnitLine['done'] && !empty($rpsUnitLine['date'])) : ?>
                    Dernière évaluation le <b><?php echo dol_print_date(strtotime($rpsUnitLine['date']), 'day'); ?></b>
                <?php elseif ($rpsUnitLine['done']) : ?>
                    Facteurs cotés, date d'évaluation non renseignée
                <?php else : ?>
                    <span class="rps-attention">Aucun facteur coté : l'unité est concernée mais pas encore évaluée.</span>
                <?php endif; ?>
                <?php if ($rpsUnitFilled > 0 && $rpsUnitFilled < $rpsUnitTotal) : ?>
                    <span class="rps-attention">&middot; <?php echo $rpsUnitTotal - $rpsUnitFilled; ?> facteur<?php echo ($rpsUnitTotal - $rpsUnitFilled) > 1 ? 's' : ''; ?> restant<?php echo ($rpsUnitTotal - $rpsUnitFilled) > 1 ? 's' : ''; ?> à coter</span>
                <?php endif; ?>
            </div>

            <div class="rps-unit-actions">
                <div class="wpeo-button button-grey modal-open rps-open-actions">
                    <input type="hidden" class="modal-options" data-modal-to-open="rps_eval_modal">
                    <i class="fas fa-tasks"></i>
                    Actions<?php echo !empty($rpsUnitLine['tasks']) ? ' (' . count($rpsUnitLine['tasks']) . ')' : ''; ?>
                </div>
                <div class="wpeo-button button-blue modal-open rps-open-eval">
                    <input type="hidden" class="modal-options" data-modal-to-open="rps_eval_modal">
                    <i class="fas fa-clipboard-list"></i>
                    <?php echo $rpsUnitFilled > 0 ? 'Revoir l\'évaluation' : 'Évaluer'; ?>
                </div>
            </div>
        </div>

        <?php
        $rpsToTreat   = $rpsUnitLine['toTreat'] ?? [];
        $rpsTaskList  = $rpsUnitLine['tasks'] ?? [];
        ?>
        <?php if (!empty($rpsUnitLine['done']) || !empty($rpsTaskList)) : ?>
            <div class="rps-actions-strip<?php echo (!empty($rpsToTreat) && empty($rpsTaskList)) ? ' is-missing' : ''; ?>">
                <?php
                // Les facteurs a traiter se lisent deja sur les cases F1 a F6 : l'en-tete ne
                // garde que l'etat du plan d'action et le moyen d'en ajouter une.
                $rpsDoneCount = 0;
                foreach ($rpsTaskList as $rpsTask) {
                    if ($rpsTask['done']) {
                        $rpsDoneCount++;
                    }
                }
                ?>
                <div class="rps-actions-strip-head">
                    <?php if (empty($rpsTaskList)) : ?>
                        <span class="<?php echo !empty($rpsToTreat) ? 'rps-attention' : ''; ?>">Aucune action de prévention</span>
                    <?php else : ?>
                        <span class="rps-actions-count"><b><?php echo $rpsDoneCount; ?></b>/<?php echo count($rpsTaskList); ?> action<?php echo count($rpsTaskList) > 1 ? 's' : ''; ?> faite<?php echo count($rpsTaskList) > 1 ? 's' : ''; ?></span>
                    <?php endif; ?>
                    <div class="wpeo-button button-grey button-size-small modal-open rps-open-actions">
                        <input type="hidden" class="modal-options" data-modal-to-open="rps_eval_modal">
                        <i class="fas fa-plus"></i> Ajouter une action
                    </div>
                </div>
                <?php if (!empty($rpsTaskList)) : ?>
                    <div class="rps-actions-list">
                        <?php foreach ($rpsTaskList as $rpsTask) : ?>
                            <div class="rps-action-item<?php echo $rpsTask['done'] ? ' is-done' : ''; ?>" data-task="<?php echo (int) $rpsTask['id']; ?>">
                                <input type="checkbox" class="rps-task-done" title="Marquer l'action comme faite" <?php echo $rpsTask['done'] ? 'checked' : ''; ?>>
                                <a class="rps-action-ref" href="<?php echo DOL_URL_ROOT . '/projet/tasks/task.php?id=' . (int) $rpsTask['id']; ?>"
                                   title="Ouvrir la tâche"><?php echo dol_escape_htmltag($rpsTask['ref']); ?></a>
                                <span class="rps-action-label"><?php echo dol_escape_htmltag($rpsTask['label']); ?></span>
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
            </div>
        <?php endif; ?>

        <div class="rps-foot">
            <span class="rps-foot-note">
                <i class="fas fa-sitemap"></i>
                Unité déclarée concernée
                <?php if (!empty($rpsRootGroupment)) : ?>
                    sur <a href="<?php echo dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . (int) $rpsRootGroupmentId; ?>"><?php echo dol_escape_htmltag($rpsRootGroupment->ref . ' · ' . $rpsRootGroupment->label); ?></a>
                <?php endif; ?>
                : la qualification se modifie là-bas, l'évaluation se fait ici.
            </span>
            <span class="rps-keys">
                <?php foreach ($rpsScale as $rpsLevel => $rpsMetaScale) : ?>
                    <span class="rps-key"><i class="rps-key-swatch" data-scale="<?php echo (int) $rpsLevel; ?>"></i><?php echo $rpsLevel . ' ' . $rpsMetaScale['label']; ?></span>
                <?php endforeach; ?>
            </span>
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
</div>
