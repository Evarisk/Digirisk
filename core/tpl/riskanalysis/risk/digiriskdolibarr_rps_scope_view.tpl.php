<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       core/tpl/riskanalysis/risk/digiriskdolibarr_rps_scope_view.tpl.php
 * \ingroup    digiriskdolibarr
 * \brief      Bloc de qualification des risques psychosociaux, affiche en haut
 *             de la liste des risques du groupement de tete - l'entreprise sur la plupart
 *             des documents uniques - et seulement a ce niveau.
 *
 *             Un tableau de toutes les unites de travail, dans l'ordre de l'arborescence et
 *             groupees sous leur groupement : filtres par etat, recherche, et selection
 *             multiple pour qualifier plusieurs unites d'un coup (js/modules/psychosocialScope.js).
 *
 *             La qualification se prend une seule fois par unite de travail, ici. Elle
 *             commande ensuite la liste des risques de chaque unite : une unite non
 *             concernee n'affiche pas la ligne du risque psychosocial, une unite concernee
 *             la retrouve a sa place, parmi ses autres risques.
 *
 * Variables attendues : $rpsLines, $rpsScale, $rpsScaleLabels, $rpsSubCategories, $rpsExistingTotal,
 *                       $rpsScopeElementId, $activeDigiriskElementList, $object, $langs
 */

global $langs;

if (empty($rpsLines)) {
    return;
}

$rpsUnitUrl = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=';

// Les unites suivent l'arborescence de la navigation, groupees sous leur groupement direct
$rpsLineById = [];
foreach ($rpsLines as $rpsLine) {
    $rpsLineById[(int) $rpsLine['id']] = $rpsLine;
}

$rpsTableGroups = [];
foreach (flatten_tree(recurse_tree(0, 0, $activeDigiriskElementList)) as $rpsNodeId => $rpsNode) {
    if (!isset($rpsLineById[$rpsNodeId])) {
        continue;
    }
    $rpsParentId = (int) $rpsNode['object']->fk_parent;
    if (!isset($rpsTableGroups[$rpsParentId])) {
        // Chemin du groupement sous le groupement de tete, pour situer un groupement imbrique
        $rpsPath     = [];
        $rpsAncestor = $activeDigiriskElementList[$rpsParentId] ?? null;
        $rpsGuard    = 0;
        while ($rpsAncestor && !empty($rpsAncestor->fk_parent) && $rpsGuard++ < 50) {
            $rpsAncestor = $activeDigiriskElementList[$rpsAncestor->fk_parent] ?? null;
            if ($rpsAncestor && !empty($rpsAncestor->fk_parent)) {
                array_unshift($rpsPath, $rpsAncestor->ref);
            }
        }
        $rpsTableGroups[$rpsParentId] = [
            'element' => $activeDigiriskElementList[$rpsParentId] ?? null,
            'path'    => implode(' › ', $rpsPath),
            'lines'   => [],
        ];
    }
    $rpsTableGroups[$rpsParentId]['lines'][] = $rpsLineById[$rpsNodeId];
}

$rpsCounts = [1 => 0, 0 => 0, -1 => 0];
$rpsDone   = 0;
$rpsTotal  = 0.0;
$rpsBand   = 0;
foreach ($rpsLines as $rpsLine) {
    $rpsCounts[$rpsLine['state']]++;
    if ($rpsLine['done']) {
        $rpsDone++;
        $rpsTotal += $rpsLine['average'];
        // Cotation du risque = la plus severe des unites concernees : a ce niveau, une
        // unite en rouge ne doit pas disparaitre derriere la moyenne des autres unites.
        $rpsBand = max($rpsBand, $rpsLine['band']);
    }
}
$rpsConcerned = $rpsCounts[1];
$rpsAverage   = $rpsDone > 0 ? $rpsTotal / $rpsDone : 0;
$rpsCoverage  = $rpsConcerned > 0 ? round(100 * $rpsDone / $rpsConcerned) : 0;

$rpsStateLabels = [1 => 'Concernée', 0 => 'Non concernée', -1 => 'À qualifier'];
$rpsTabs        = ['all' => ['Toutes', count($rpsLines)], '-1' => ['À qualifier', $rpsCounts[-1]], '1' => ['Concernées', $rpsCounts[1]], '0' => ['Non concernées', $rpsCounts[0]]];

?>
<div class="rps-scope rps-scope-table" data-endpoint="<?php echo dol_buildpath('/digiriskdolibarr/core/ajax/save_rps_assessment.php', 1); ?>"
     data-scale-labels="<?php echo dol_escape_htmltag(json_encode($rpsScaleLabels)); ?>">

    <div class="rps-head">
        <span class="rps-head-icon"><i class="fas fa-brain"></i></span>
        <div class="rps-head-text">
            <span class="rps-head-title">Risques psychosociaux</span>
            <span class="rps-head-sub">Une qualification par unité de travail, pour tout le document unique</span>
        </div>

        <div class="rps-head-metrics">
            <div class="rps-metric">
                <span class="rps-metric-label">Moyenne</span>
                <span class="rps-metric-value">
                    <b class="rps-num" id="rps-average"><?php echo $rpsDone > 0 ? number_format($rpsAverage, 2, ',', ' ') : '—'; ?></b> / 4
                </span>
            </div>
            <span class="rps-band" id="rps-band" data-scale="<?php echo $rpsDone > 0 ? (int) $rpsBand : 0; ?>" title="Niveau de l'unité la plus exposée">
                <?php echo $rpsDone > 0 ? $rpsScaleLabels[$rpsBand] : 'non cotée'; ?>
            </span>
            <div class="rps-metric">
                <span class="rps-metric-label"><b id="rps-done"><?php echo $rpsDone; ?></b>/<b id="rps-concerned"><?php echo $rpsConcerned; ?></b> évaluées</span>
                <progress class="rps-progress" id="rps-bar" max="100" value="<?php echo (int) $rpsCoverage; ?>"></progress>
            </div>
        </div>
    </div>

    <div class="rps-toolbar">
        <div class="rps-tabs" role="tablist">
            <?php foreach ($rpsTabs as $rpsFilter => $rpsTab) : ?>
                <button type="button" class="rps-tab<?php echo $rpsFilter === 'all' ? ' is-on' : ''; ?>" data-filter="<?php echo $rpsFilter; ?>" role="tab">
                    <?php echo $rpsTab[0]; ?> <span class="rps-tab-count"><?php echo (int) $rpsTab[1]; ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        <label class="rps-search">
            <i class="fas fa-search"></i>
            <input type="search" class="rps-search-input" placeholder="Rechercher une unité…" aria-label="Rechercher une unité">
        </label>
        <label class="rps-group-toggle"><input type="checkbox" class="rps-group-input" checked> Grouper par GP</label>
    </div>

    <div class="rps-table-wrap">
        <table class="rps-table">
            <thead>
                <tr>
                    <th class="rps-col-check"><input type="checkbox" class="rps-select-all" title="Tout sélectionner" aria-label="Tout sélectionner"></th>
                    <th class="rps-col-unit">Unité</th>
                    <th class="rps-col-state">Qualification</th>
                    <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) : ?>
                        <th class="rps-col-factor"><abbr title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?>">F<?php echo (int) $rpsPosition + 1; ?></abbr></th>
                    <?php endforeach; ?>
                    <th class="rps-col-score">Moy.</th>
                    <th class="rps-col-date">Évaluée le</th>
                    <th class="rps-col-go"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rpsTableGroups as $rpsGroupId => $rpsGroup) : ?>
                    <tr class="rps-group-row" data-group="<?php echo (int) $rpsGroupId; ?>">
                        <td class="rps-col-check">
                            <input type="checkbox" class="rps-select-group" title="Sélectionner les unités du groupement" aria-label="Sélectionner les unités du groupement">
                        </td>
                        <td colspan="11">
                            <?php if (!empty($rpsGroup['element'])) : ?>
                                <span class="rps-unit-ref is-groupment"><?php echo dol_escape_htmltag($rpsGroup['element']->ref); ?></span>
                                <?php echo dol_escape_htmltag($rpsGroup['element']->label); ?>
                                <?php if ($rpsGroup['path'] !== '') : ?>
                                    <span class="rps-group-path">· dans <?php echo dol_escape_htmltag($rpsGroup['path']); ?></span>
                                <?php endif; ?>
                            <?php else : ?>
                                Hors groupement
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php foreach ($rpsGroup['lines'] as $rpsLine) :
                        $rpsState   = (int) $rpsLine['state'];
                        $rpsLevels  = $rpsLine['levels'];
                        $rpsHasEval = count($rpsLevels) > 0;
                        // La moyenne est gardee meme hors perimetre : repasser l'unite en concernee la retrouve
                        $rpsLineAverage = $rpsHasEval ? array_sum($rpsLevels) / count($rpsLevels) : null; ?>
                        <tr class="rps-row" id="rps-unit-<?php echo (int) $rpsLine['id']; ?>"
                            data-element="<?php echo (int) $rpsLine['id']; ?>"
                            data-group="<?php echo (int) $rpsLine['parent']; ?>"
                            data-state="<?php echo $rpsState; ?>"
                            data-done="<?php echo $rpsHasEval ? 1 : 0; ?>"
                            data-average="<?php echo $rpsHasEval ? sprintf('%.4f', $rpsLineAverage) : ''; ?>"
                            data-search="<?php echo dol_escape_htmltag($rpsLine['ref'] . ' ' . $rpsLine['label']); ?>">
                            <td class="rps-col-check">
                                <input type="checkbox" class="rps-select" aria-label="Sélectionner <?php echo dol_escape_htmltag($rpsLine['ref']); ?>">
                            </td>
                            <td class="rps-col-unit">
                                <a class="rps-unit" href="<?php echo $rpsUnitUrl . (int) $rpsLine['id']; ?>">
                                    <span class="rps-unit-ref"><?php echo dol_escape_htmltag($rpsLine['ref']); ?></span>
                                    <span class="rps-unit-label" title="<?php echo dol_escape_htmltag($rpsLine['label']); ?>"><?php echo dol_escape_htmltag($rpsLine['label']); ?></span>
                                </a>
                            </td>
                            <td class="rps-col-state">
                                <span class="rps-state-select" data-state="<?php echo $rpsState; ?>">
                                    <select class="rps-state-input" aria-label="Qualification de <?php echo dol_escape_htmltag($rpsLine['ref']); ?>">
                                        <?php foreach ($rpsStateLabels as $rpsStateValue => $rpsStateLabel) : ?>
                                            <option value="<?php echo $rpsStateValue; ?>"<?php echo $rpsStateValue === $rpsState ? ' selected' : ''; ?>><?php echo $rpsStateLabel; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-chevron-down"></i>
                                </span>
                            </td>
                            <?php // Facteurs d'une unite evaluee, ou a la place une seule case selon son etat : la feuille de style montre la bonne ?>
                            <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) :
                                $rpsLevel = (int) ($rpsLevels[$rpsPosition] ?? 0); ?>
                                <td class="rps-col-factor">
                                    <span class="rps-cell" data-scale="<?php echo $rpsLevel; ?>"
                                          title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label']) . ' — ' . ($rpsLevel ? $rpsScale[$rpsLevel]['label'] : 'non évalué')); ?>"><?php echo $rpsLevel ?: ''; ?></span>
                                </td>
                            <?php endforeach; ?>
                            <td class="rps-col-span" colspan="6">
                                <a class="rps-when-yes" href="<?php echo $rpsUnitUrl . (int) $rpsLine['id']; ?>#rps-eval"><i class="fas fa-clipboard-list"></i> Évaluer cette unité</a>
                                <button type="button" class="rps-when-no rps-reason" title="Modifier le motif">
                                    <i class="fas fa-info-circle"></i> <span class="rps-reason-text"><?php echo dol_escape_htmltag($rpsLine['reason']); ?></span>
                                </button>
                                <span class="rps-when-tbd">Exposition à trancher</span>
                            </td>
                            <td class="rps-col-score">
                                <?php if ($rpsHasEval) : ?>
                                    <span class="rps-score"><i class="rps-dot" data-scale="<?php echo rps_scope_band($rpsLineAverage); ?>"></i><b><?php echo number_format($rpsLineAverage, 2, ',', ' '); ?></b></span>
                                <?php endif; ?>
                            </td>
                            <td class="rps-col-date">
                                <?php if (!empty($rpsLine['date'])) : ?>
                                    <span class="rps-date"><?php echo dol_print_date(strtotime($rpsLine['date']), 'day'); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($rpsLine['untreated'])) : ?>
                                    <i class="fas fa-exclamation-triangle rps-untreated" title="<?php echo count($rpsLine['untreated']); ?> facteur<?php echo count($rpsLine['untreated']) > 1 ? 's' : ''; ?> élevé<?php echo count($rpsLine['untreated']) > 1 ? 's' : ''; ?> sans action"></i>
                                <?php endif; ?>
                            </td>
                            <td class="rps-col-go">
                                <a class="wpeo-button button-transparent button-square-30" href="<?php echo $rpsUnitUrl . (int) $rpsLine['id']; ?>" title="Ouvrir l'unité" aria-label="Ouvrir l'unité">
                                    <i class="fas fa-arrow-right button-icon"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <tr class="rps-empty rps-hidden"><td colspan="12">Aucune unité ne correspond.</td></tr>
            </tbody>
        </table>
    </div>

    <div class="rps-bulk rps-hidden">
        <b class="rps-bulk-count"></b>
        <span class="rps-bulk-label">Passer en :</span>
        <button type="button" class="wpeo-button button-size-small rps-bulk-apply" data-state="1"><i class="fas fa-check"></i> Concernées</button>
        <button type="button" class="wpeo-button button-size-small rps-bulk-apply" data-state="0"><i class="fas fa-times"></i> Non concernées…</button>
        <button type="button" class="wpeo-button button-size-small rps-bulk-apply" data-state="-1"><i class="fas fa-question"></i> À qualifier</button>
        <button type="button" class="wpeo-button button-transparent button-square-30 rps-bulk-clear" title="Désélectionner" aria-label="Désélectionner">
            <i class="fas fa-times button-icon"></i>
        </button>
    </div>

    <div class="rps-foot">
        <span class="rps-keys">
            <?php foreach ($rpsScale as $rpsLevel => $rpsMetaScale) : ?>
                <span class="rps-key"><i class="rps-key-swatch" data-scale="<?php echo (int) $rpsLevel; ?>"></i><?php echo $rpsLevel . ' ' . $rpsMetaScale['label']; ?></span>
            <?php endforeach; ?>
        </span>
        <span class="rps-factor-keys">
            <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) : ?>
                <span><b>F<?php echo (int) $rpsPosition + 1; ?></b> <?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?></span>
            <?php endforeach; ?>
        </span>
        <div class="wpeo-button button-grey button-size-small modal-open">
            <input type="hidden" class="modal-options" data-modal-to-open="rps_dict_modal">
            <i class="fas fa-sliders-h"></i> Gérer les critères
        </div>
    </div>

    <?php if (!empty($rpsExistingTotal) && $rpsExistingTotal > 1) : ?>
        <div class="rps-note">
            <span>
                <i class="fas fa-exclamation-triangle"></i>
                <b><?php echo (int) $rpsExistingTotal; ?> risques psychosociaux</b> sont aujourd'hui enregistrés séparément :
                la grille INRS en crée un par critère, la modale « Faire le point » huit par unité. La qualification
                ci-dessus les consolide en une ligne par unité de travail, sans rien sortir du document unique.
            </span>
        </div>
    <?php endif; ?>
</div>
