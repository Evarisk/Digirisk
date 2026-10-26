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
 *             La persistance de la qualification n est pas encore branchee.
 *
 * Variables attendues : $rpsLines, $rpsScale, $rpsSubCategories, $rpsExistingTotal,
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
<div class="rps-scope rps-scope-top">

    <div class="rps-head">
        <span class="rps-head-title">
            <i class="fas fa-brain"></i>
            Risques psychosociaux
            <span class="rps-head-sub">toutes les unités de travail du document unique &mdash; une qualification par unité</span>
        </span>

        <span class="rps-head-metrics">
            <span class="rps-metric">
                <b class="rps-num" id="rps-average"><?php echo $rpsDone > 0 ? number_format($rpsAverage, 2, ',', ' ') : '—'; ?></b>
                <span class="rps-metric-label">/ 4 cotation</span>
            </span>
            <span class="rps-pill rps-pill-band" id="rps-band"
                  style="background:<?php echo $rpsDone > 0 ? $rpsScale[$rpsBand]['color'] : '#f1f2f5'; ?>;color:<?php echo $rpsDone > 0 ? $rpsScale[$rpsBand]['ink'] : '#9aa1ab'; ?>">
                <?php echo $rpsDone > 0 ? $rpsScale[$rpsBand]['range'] . ' · ' . $rpsScale[$rpsBand]['label'] : 'non cotée'; ?>
            </span>
            <span class="rps-metric rps-metric-coverage">
                <span class="rps-metric-label"><b id="rps-done"><?php echo $rpsDone; ?></b>/<b id="rps-concerned"><?php echo $rpsConcerned; ?></b> évaluées</span>
                <span class="rps-bar"><i id="rps-bar" style="width:<?php echo (int) $rpsCoverage; ?>%"></i></span>
            </span>
        </span>

        <button type="button" class="rps-icon-button rps-scope-toggle" title="Replier"><i class="fas fa-chevron-up"></i></button>
    </div>

    <div class="rps-body">
        <?php foreach ($rpsGroups as $rpsState => $rpsStateLines) :
            $rpsMeta = $rpsGroupMeta[$rpsState]; ?>
            <section class="rps-group" data-group="<?php echo $rpsMeta['key']; ?>" <?php echo empty($rpsStateLines) ? 'hidden' : ''; ?>>
                <div class="rps-group-head">
                    <span class="rps-group-title"><?php echo dol_escape_htmltag($rpsMeta['title']); ?></span>
                    <span class="rps-count"><?php echo count($rpsStateLines); ?></span>
                    <span class="rps-group-hint"><?php echo dol_escape_htmltag($rpsMeta['hint']); ?></span>
                    <?php if ($rpsMeta['key'] == 'yes') : ?>
                        <span class="rps-group-legend">
                            <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) : ?>
                                <abbr title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label'])); ?>">F<?php echo (int) $rpsPosition + 1; ?></abbr>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="rps-rows">
                    <?php foreach ($rpsStateLines as $rpsLine) : ?>
                        <div class="rps-row" data-element="<?php echo (int) $rpsLine['id']; ?>"
                             data-state="<?php echo (int) $rpsLine['state']; ?>"
                             data-average="<?php echo $rpsLine['done'] ? sprintf('%.4f', $rpsLine['average']) : ''; ?>">

                            <div class="rps-unit">
                                <span class="rps-unit-ref"><?php echo dol_escape_htmltag($rpsLine['ref']); ?></span>
                                <span class="rps-unit-label"><?php echo dol_escape_htmltag($rpsLine['label']); ?></span>
                                <span class="rps-unit-meta">
                                    <?php if ($rpsLine['state'] === 0) : ?>
                                        <i class="fas fa-circle-info"></i> <span class="rps-reason"><?php echo dol_escape_htmltag($rpsLine['reason']); ?></span>
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
                                <?php foreach ($rpsSubCategories as $rpsPosition => $rpsSub) : ?>
                                    <?php if (isset($rpsLine['levels'][$rpsPosition])) :
                                        $rpsLevel = $rpsLine['levels'][$rpsPosition]; ?>
                                        <span class="rps-cell" style="background:<?php echo $rpsScale[$rpsLevel]['color']; ?>;color:<?php echo $rpsScale[$rpsLevel]['ink']; ?>"
                                              title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label']) . ' — ' . $rpsScale[$rpsLevel]['label']); ?>"><?php echo $rpsLevel; ?></span>
                                    <?php else : ?>
                                        <span class="rps-cell rps-cell-empty" title="<?php echo dol_escape_htmltag($langs->trans($rpsSub['label']) . ' — non évalué'); ?>"></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="rps-score">
                                <?php if ($rpsLine['done']) : ?>
                                    <b class="rps-num"><?php echo number_format($rpsLine['average'], 2, ',', ' '); ?></b>
                                    <span class="rps-pill" style="background:<?php echo $rpsScale[$rpsLine['band']]['color']; ?>;color:<?php echo $rpsScale[$rpsLine['band']]['ink']; ?>"><?php echo $rpsScale[$rpsLine['band']]['range']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="rps-actions">
                                <span class="rps-switch" role="group">
                                    <button type="button" data-value="1" class="<?php echo $rpsLine['state'] === 1 ? 'is-yes' : ''; ?>">Concernée</button>
                                    <button type="button" data-value="0" class="<?php echo $rpsLine['state'] === 0 ? 'is-no' : ''; ?>">Non</button>
                                    <button type="button" data-value="-1" class="<?php echo $rpsLine['state'] === -1 ? 'is-tbd' : ''; ?>" title="À qualifier">?</button>
                                </span>
                                <a class="rps-evaluate" href="<?php echo dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . (int) $rpsLine['id']; ?>">
                                    <i class="fas fa-arrow-right"></i>
                                    <span>Ouvrir l'unité</span>
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
            <span class="rps-foot-keys">
                <?php foreach ($rpsScale as $rpsLevel => $rpsMetaScale) : ?>
                    <span><i style="background:<?php echo $rpsMetaScale['color']; ?>"></i><?php echo $rpsLevel . ' ' . $rpsMetaScale['label']; ?></span>
                <?php endforeach; ?>
            </span>
            <button type="button" class="rps-button rps-open-dict"><i class="fas fa-sliders"></i> Gérer les critères</button>
        </div>

        <?php if (!empty($rpsExistingTotal) && $rpsExistingTotal > 1) : ?>
            <div class="rps-note">
                <i class="fas fa-triangle-exclamation"></i>
                <b><?php echo (int) $rpsExistingTotal; ?> risques psychosociaux</b> sont aujourd'hui enregistrés séparément :
                la grille INRS en crée un par critère, la modale « Faire le point » huit par unité. La qualification
                ci-dessus les consolide en une ligne par unité de travail, sans rien sortir du document unique.
            </div>
        <?php endif; ?>
    </div>
</div>
