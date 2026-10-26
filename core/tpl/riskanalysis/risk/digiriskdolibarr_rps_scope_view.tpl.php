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

// La qualification est prise une fois pour tout le document unique : le bloc reprend
// toutes les unites de travail, y compris celles rattachees a un autre groupement de tete.
// Il s'affiche sur le groupement de tete, le niveau entreprise sur la plupart des DU.
$rpsScopeLines = $rpsLines;

if (empty($rpsScopeLines)) {
    return;
}

$rpsGroups = [1 => [], -1 => [], 0 => []];
foreach ($rpsScopeLines as $rpsLine) {
    $rpsGroups[$rpsLine['state']][] = $rpsLine;
}

$rpsDone  = 0;
$rpsTotal = 0.0;
$rpsBand  = 0;
foreach ($rpsGroups[1] as $rpsLine) {
    if ($rpsLine['done']) {
        $rpsDone++;
        $rpsTotal += $rpsLine['average'];
        // Cotation du risque = la plus severe des unites concernees : a ce niveau, une
        // unite en rouge ne doit pas disparaitre derriere la moyenne des autres unites.
        $rpsBand = max($rpsBand, $rpsLine['band']);
    }
}
$rpsConcerned = count($rpsGroups[1]);
$rpsAverage   = $rpsDone > 0 ? $rpsTotal / $rpsDone : 0;
$rpsCoverage  = $rpsConcerned > 0 ? round(100 * $rpsDone / $rpsConcerned) : 0;

$rpsGroupMeta = [
    1  => ['key' => 'yes', 'title' => 'Unités concernées',     'hint' => 'le risque psychosocial apparaît dans leurs risques et entre dans la cotation'],
    -1 => ['key' => 'tbd', 'title' => 'Unités à qualifier',    'hint' => 'exposition à trancher avant la prochaine mise à jour du document unique'],
    0  => ['key' => 'no',  'title' => 'Unités non concernées', 'hint' => 'hors périmètre : motif conservé, aucune ligne de risque, aucune cotation'],
];

?>
<div class="rps-scope" data-endpoint="<?php echo dol_buildpath('/digiriskdolibarr/core/ajax/save_rps_assessment.php', 1); ?>"
     data-scale-labels="<?php echo dol_escape_htmltag(json_encode($rpsScaleLabels)); ?>">

    <div class="rps-head">
        <span class="rps-head-icon"><i class="fas fa-brain"></i></span>
        <div class="rps-head-text">
            <span class="rps-head-title">Risques psychosociaux</span>
            <span class="rps-head-sub">Toutes les unités de travail du document unique &mdash; une qualification par unité</span>
        </div>

        <div class="rps-head-metrics">
            <div class="rps-metric">
                <span class="rps-metric-label">Cotation</span>
                <span class="rps-metric-value">
                    <b class="rps-num" id="rps-average"><?php echo $rpsDone > 0 ? number_format($rpsAverage, 2, ',', ' ') : '—'; ?></b> / 4
                </span>
            </div>
            <span class="rps-band" id="rps-band" data-scale="<?php echo $rpsDone > 0 ? (int) $rpsBand : 0; ?>">
                <?php echo $rpsDone > 0 ? $rpsScaleLabels[$rpsBand] : 'non cotée'; ?>
            </span>
            <div class="rps-metric">
                <span class="rps-metric-label"><b id="rps-done"><?php echo $rpsDone; ?></b>/<b id="rps-concerned"><?php echo $rpsConcerned; ?></b> évaluées</span>
                <progress class="rps-progress" id="rps-bar" max="100" value="<?php echo (int) $rpsCoverage; ?>"></progress>
            </div>
        </div>

        <button type="button" class="wpeo-button button-transparent button-square-30 rps-scope-toggle" title="Replier">
            <i class="fas fa-chevron-up button-icon"></i>
        </button>
    </div>

    <div class="rps-body">
        <?php foreach ($rpsGroups as $rpsState => $rpsStateLines) :
            $rpsMeta = $rpsGroupMeta[$rpsState]; ?>
            <section class="rps-group" data-group="<?php echo $rpsMeta['key']; ?>" <?php echo empty($rpsStateLines) ? 'hidden' : ''; ?>>
                <div class="rps-group-head">
                    <span class="rps-group-title"><?php echo dol_escape_htmltag($rpsMeta['title']); ?></span>
                    <span class="rps-count"><?php echo count($rpsStateLines); ?></span>
                    <span class="rps-group-hint"><?php echo dol_escape_htmltag($rpsMeta['hint']); ?></span>
                </div>

                <div class="rps-rows">
                    <?php if ($rpsMeta['key'] == 'yes') : ?>
                        <div class="rps-row-keys">
                            <span class="rps-factors">
                                <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) : ?>
                                    <abbr class="rps-factor-key" title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?>">F<?php echo (int) $rpsPosition + 1; ?></abbr>
                                <?php endforeach; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($rpsStateLines as $rpsLine) : ?>
                        <div class="rps-row" data-element="<?php echo (int) $rpsLine['id']; ?>"
                             data-state="<?php echo (int) $rpsLine['state']; ?>"
                             data-average="<?php echo $rpsLine['done'] ? sprintf('%.4f', $rpsLine['average']) : ''; ?>">

                            <div class="rps-unit">
                                <span class="rps-unit-ref"><?php echo dol_escape_htmltag($rpsLine['ref']); ?></span>
                                <span class="rps-unit-label"><?php echo dol_escape_htmltag($rpsLine['label']); ?></span>
                                <span class="rps-unit-meta">
                                    <?php if ($rpsLine['state'] === 0) : ?>
                                        <i class="fas fa-info-circle"></i> <span class="rps-reason"><?php echo dol_escape_htmltag($rpsLine['reason']); ?></span>
                                        <?php if (!empty($rpsLine['orphans'])) : ?>
                                            <span class="rps-attention">&middot; <?php echo count($rpsLine['orphans']); ?> risque<?php echo count($rpsLine['orphans']) > 1 ? 's' : ''; ?> à archiver</span>
                                        <?php endif; ?>
                                    <?php elseif ($rpsLine['state'] === -1) : ?>
                                        exposition non tranchée
                                    <?php elseif ($rpsLine['done']) : ?>
                                        évaluée le <?php echo dol_print_date(strtotime($rpsLine['date']), 'day'); ?>
                                        <?php if (!empty($rpsLine['toTreat'])) : ?>
                                            &middot; <?php echo count($rpsLine['toTreat']); ?> facteur<?php echo count($rpsLine['toTreat']) > 1 ? 's' : ''; ?> à traiter,
                                            <?php if (empty($rpsLine['tasks'])) : ?>
                                                <span class="rps-attention">aucune action</span>
                                            <?php else :
                                                $rpsDoneCount = 0;
                                                foreach ($rpsLine['tasks'] as $rpsTask) {
                                                    if ($rpsTask['done']) {
                                                        $rpsDoneCount++;
                                                    }
                                                } ?>
                                                <?php echo $rpsDoneCount; ?>/<?php echo count($rpsLine['tasks']); ?> action<?php echo count($rpsLine['tasks']) > 1 ? 's' : ''; ?> faite<?php echo $rpsDoneCount > 1 ? 's' : ''; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (count($rpsLine['orphans']) > 6) : ?>
                                            <span class="rps-attention">&middot; <?php echo count($rpsLine['orphans']); ?> risques RPS à consolider</span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="rps-attention">aucun facteur coté</span>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="rps-factors">
                                <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) :
                                    $rpsLevel = (int) ($rpsLine['levels'][$rpsPosition] ?? 0); ?>
                                    <span class="rps-cell" data-scale="<?php echo $rpsLevel; ?>"
                                          title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label']) . ' — ' . ($rpsLevel ? $rpsScale[$rpsLevel]['label'] : 'non évalué')); ?>"><?php echo $rpsLevel ?: ''; ?></span>
                                <?php endforeach; ?>
                            </div>

                            <div class="rps-score">
                                <?php if ($rpsLine['done']) : ?>
                                    <b class="rps-num"><?php echo number_format($rpsLine['average'], 2, ',', ' '); ?></b>
                                    <span class="rps-band" data-scale="<?php echo (int) $rpsLine['band']; ?>"><?php echo $rpsScale[$rpsLine['band']]['range']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="rps-actions">
                                <span class="rps-switch" role="group">
                                    <button type="button" data-value="1" class="<?php echo $rpsLine['state'] === 1 ? 'is-yes' : ''; ?>">Concernée</button>
                                    <button type="button" data-value="0" class="<?php echo $rpsLine['state'] === 0 ? 'is-no' : ''; ?>">Non</button>
                                    <button type="button" data-value="-1" class="<?php echo $rpsLine['state'] === -1 ? 'is-tbd' : ''; ?>" title="À qualifier">?</button>
                                </span>
                                <a class="wpeo-button button-grey button-square-30 wpeo-tooltip-event" aria-label="Ouvrir l'unité" data-direction="left"
                                   href="<?php echo dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . (int) $rpsLine['id']; ?>">
                                    <i class="fas fa-arrow-right button-icon"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="rps-foot">
            <span class="rps-foot-note">
                <i class="fas fa-sitemap"></i>
                La qualification se prend ici, une fois. Elle commande la liste des risques de chaque unité :
                non concernée, l'unité n'affiche plus de ligne « risques psychosociaux ».
            </span>
            <span class="rps-keys">
                <?php foreach ($rpsScale as $rpsLevel => $rpsMetaScale) : ?>
                    <span class="rps-key"><i class="rps-key-swatch" data-scale="<?php echo (int) $rpsLevel; ?>"></i><?php echo $rpsLevel . ' ' . $rpsMetaScale['label']; ?></span>
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
</div>
