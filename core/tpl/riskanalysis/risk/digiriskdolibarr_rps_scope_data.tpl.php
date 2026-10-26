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
 * \brief      Donnees du perimetre RPS : referentiels, risques psychosociaux
 *             deja enregistres et qualification de chaque unite de travail.
 *
 *             Inclus par la liste des risques avant le tableau : le bloc de qualification
 *             ne s affiche qu au niveau de l entite, et la ligne du risque psychosocial
 *             n apparait sur une unite de travail que si elle est declaree concernee.
 *
 *             La persistance de la qualification n est pas encore branchee.
 *             Referentiels : Risk::getDangerSubCategories() et Risk::getPsychosocialRiskGrid()
 *             (outil RPS-DU de l INRS, ED 6403).
 *
 * Variables attendues : $activeDigiriskElementList, $langs, $db
 * Variables produites  : $rpsLines, $rpsStateByUnit, $rpsScale, $rpsSubCategories
 */

global $conf, $db, $langs;

// Les 6 facteurs de risque psychosocial (positions 0 a 5 du referentiel du module).
// Les positions 6 et 7 decrivent le contexte de prevention et l'impact, pas une exposition.
$rpsSubCategories    = [];
$rpsSubCategoriesAll = [];
$rpsAllSub           = Risk::getDangerSubCategories();
foreach (($rpsAllSub[Risk::PSYCHOSOCIAL_CATEGORY_POSITION] ?? []) as $sub) {
    $rpsSubCategoriesAll[(int) $sub['position']] = $sub;
    if ($sub['position'] <= 5) {
        $rpsSubCategories[(int) $sub['position']] = $sub;
    }
}

// Les 4 reponses de la grille tombent sur les 4 paliers de cotation Digirisk. La rampe va du
// vert au rouge sombre plutot que du gris au noir : sur une grille de 26 criteres, le gris du
// palier le plus faible et le noir du plus grave se lisaient tous les deux comme "neutre".
// Les bornes, elles, restent celles de Risk::$cotations.
$rpsScale = [
    1 => ['label' => $langs->trans('Weak'),     'color' => '#3a9d5d', 'ink' => '#ffffff', 'range' => '0-47'],
    2 => ['label' => $langs->trans('Moderate'), 'color' => '#e9ad4f', 'ink' => '#3a2a00', 'range' => '48-50'],
    3 => ['label' => $langs->trans('High'),     'color' => '#e05353', 'ink' => '#ffffff', 'range' => '51-79'],
    4 => ['label' => $langs->trans('Extreme'),  'color' => '#8f2222', 'ink' => '#ffffff', 'range' => '80-100'],
];

if (!function_exists('rps_scope_level')) {
    /**
     * Traduit une cotation Digirisk (0 a 100) en palier de la grille RPS (1 a 4).
     *
     * @param  int $cotation Cotation enregistree sur l'evaluation
     * @return int           Palier de 1 a 4
     */
    function rps_scope_level(int $cotation): int
    {
        if ($cotation >= 80) {
            return 4;
        }
        if ($cotation >= 51) {
            return 3;
        }
        if ($cotation >= 48) {
            return 2;
        }
        return 1;
    }

    /**
     * Traduit une moyenne de paliers (1 a 4) en palier consolide.
     *
     * @param  float $average Moyenne des facteurs evalues
     * @return int            Palier de 1 a 4
     */
    function rps_scope_band(float $average): int
    {
        if ($average >= 3.5) {
            return 4;
        }
        if ($average >= 2.5) {
            return 3;
        }
        if ($average >= 1.5) {
            return 2;
        }
        return 1;
    }

    /**
     * Dit si un element descend d'un autre, en remontant les parents.
     *
     * @param  array $elements   Elements actifs indexes par identifiant
     * @param  int   $childId    Element teste
     * @param  int   $ancestorId Ancetre recherche
     * @return bool              Vrai si l'element descend de l'ancetre
     */
    function rps_is_descendant(array $elements, int $childId, int $ancestorId): bool
    {
        $guard   = 0;
        $current = $elements[$childId] ?? null;
        while ($current && $guard++ < 50) {
            if ((int) $current->fk_parent === $ancestorId) {
                return true;
            }
            $current = $elements[$current->fk_parent] ?? null;
        }
        return false;
    }
}

/* --------------------------------------------------------------------------
 * Risques psychosociaux deja enregistres, regroupes par element puis par facteur
 * ----------------------------------------------------------------------- */

if (!isset($rpsExistingByElement)) {
    $rpsExistingByElement = [];
    $rpsExistingRefs      = [];
    $rpsExistingDate      = [];
    $rpsExistingTotal     = 0;

    // Rattrapage des risques crees avant que sub_category soit renseignee : le facteur
    // est alors retrouve sur le libelle, comme le fait deja le document unique.
    $rpsNeedles = [
        'exigences' => 0, 'motionnel' => 0,
        'intensit'  => 1, 'horaires'  => 1,
        'autonomie' => 2,
        'rapports'  => 3,
        'sens'      => 4, 'valeurs'   => 4,
        'curit'     => 5,
    ];

    $sqlRps  = 'SELECT r.rowid, r.ref, r.fk_element, r.sub_category, r.description,';
    $sqlRps .= ' (SELECT ra.cotation FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment ra';
    $sqlRps .= '   WHERE ra.fk_risk = r.rowid AND ra.status > 0 ORDER BY ra.rowid DESC LIMIT 1) AS cotation,';
    $sqlRps .= ' (SELECT ra.date_riskassessment FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment ra';
    $sqlRps .= '   WHERE ra.fk_risk = r.rowid AND ra.status > 0 ORDER BY ra.rowid DESC LIMIT 1) AS dt';
    $sqlRps .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk r';
    $sqlRps .= ' WHERE r.category = ' . (int) Risk::PSYCHOSOCIAL_CATEGORY_POSITION;
    $sqlRps .= ' AND r.status > 0 AND r.entity IN (' . getEntity('digiriskelement') . ')';

    $resRps = $db->query($sqlRps);
    if ($resRps) {
        while ($o = $db->fetch_object($resRps)) {
            $rpsExistingTotal++;
            $rpsExistingRefs[$o->fk_element][] = $o->ref;
            $rpsExistingDate[$o->fk_element]   = max($rpsExistingDate[$o->fk_element] ?? '', (string) $o->dt);

            $subCategory = null;
            if ($o->sub_category !== null && $o->sub_category !== '') {
                $subCategory = (int) $o->sub_category;
            } else {
                $desc = mb_strtolower((string) $o->description);
                foreach ($rpsNeedles as $needle => $position) {
                    if (mb_strpos($desc, $needle) !== false) {
                        $subCategory = $position;
                        break;
                    }
                }
            }

            if ($subCategory === null || $subCategory > 5) {
                continue;
            }

            // Plusieurs risques peuvent porter le meme facteur : on retient le plus severe,
            // comme le fait loadRiskAssessmentDocumentInfos pour le document unique.
            $previous = $rpsExistingByElement[$o->fk_element][$subCategory] ?? null;
            if (is_null($previous) || (int) $o->cotation > $previous) {
                $rpsExistingByElement[$o->fk_element][$subCategory] = (int) $o->cotation;
            }
        }
    }
}

/* --------------------------------------------------------------------------
 * Evaluations consolidees : la derniere grille saisie par unite de travail
 * ----------------------------------------------------------------------- */

if (!isset($rpsAssessmentByElement)) {
    $rpsAssessmentByElement = [];

    $sqlAnswers  = 'SELECT r.fk_element, ra.rowid AS fk_riskassessment, ra.date_riskassessment,';
    $sqlAnswers .= ' a.criterion, a.sub_category, a.scale';
    $sqlAnswers .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsanswer a';
    $sqlAnswers .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment ra ON ra.rowid = a.fk_riskassessment';
    $sqlAnswers .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk r ON r.rowid = ra.fk_risk';
    $sqlAnswers .= ' WHERE ra.status > 0 AND r.status > 0 AND r.entity IN (' . getEntity('digiriskelement') . ')';
    $sqlAnswers .= ' ORDER BY r.fk_element ASC, ra.rowid ASC';

    $resAnswers = $db->query($sqlAnswers);
    if ($resAnswers) {
        while ($o = $db->fetch_object($resAnswers)) {
            $element = (int) $o->fk_element;

            // Les reponses arrivent triees : une evaluation plus recente remplace la precedente
            if (($rpsAssessmentByElement[$element]['id'] ?? 0) != (int) $o->fk_riskassessment) {
                if (($rpsAssessmentByElement[$element]['id'] ?? 0) > (int) $o->fk_riskassessment) {
                    continue;
                }
                $rpsAssessmentByElement[$element] = [
                    'id'       => (int) $o->fk_riskassessment,
                    'date'     => $o->date_riskassessment,
                    'criteria' => [],
                    'factors'  => [],
                ];
            }

            $scale = (int) $o->scale;
            $rpsAssessmentByElement[$element]['criteria'][(int) $o->criterion] = $scale;

            // Un facteur prend le plus severe de ses criteres. Les positions 6 et 7 decrivent
            // le contexte de prevention et l'impact : conservees, mais hors cotation.
            $factor = (int) $o->sub_category;
            if ($factor <= 5) {
                $rpsAssessmentByElement[$element]['factors'][$factor] = max($rpsAssessmentByElement[$element]['factors'][$factor] ?? 0, $scale);
            }
        }
    }
}

/* --------------------------------------------------------------------------
 * Actions de prevention rattachees au risque psychosocial de chaque unite
 * ----------------------------------------------------------------------- */

if (!isset($rpsTasksByElement)) {
    $rpsTasksByElement = [];

    // Le responsable d'une tache est son contact interne "TASKEXECUTIVE" : c'est la
    // convention Dolibarr, celle qu'affiche la fiche de la tache.
    $sqlTasks  = 'SELECT r.fk_element, t.rowid, t.ref, t.label, t.fk_statut, t.progress, t.datee,';
    $sqlTasks .= ' u.rowid AS fk_user, u.lastname, u.firstname';
    $sqlTasks .= ' FROM ' . MAIN_DB_PREFIX . 'projet_task_extrafields te';
    $sqlTasks .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk r ON r.rowid = te.fk_risk';
    $sqlTasks .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'projet_task t ON t.rowid = te.fk_object';
    $sqlTasks .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_contact ec ON ec.element_id = t.rowid';
    $sqlTasks .= '   AND ec.fk_c_type_contact = (SELECT rowid FROM ' . MAIN_DB_PREFIX . 'c_type_contact';
    $sqlTasks .= "      WHERE element = 'project_task' AND source = 'internal' AND code = 'TASKEXECUTIVE' LIMIT 1)";
    $sqlTasks .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user u ON u.rowid = ec.fk_socpeople';
    $sqlTasks .= ' WHERE r.category = ' . (int) Risk::PSYCHOSOCIAL_CATEGORY_POSITION;
    $sqlTasks .= ' AND r.status > 0 AND r.entity IN (' . getEntity('digiriskelement') . ')';
    $sqlTasks .= ' ORDER BY t.rowid ASC';

    $resTasks = $db->query($sqlTasks);
    if ($resTasks) {
        while ($o = $db->fetch_object($resTasks)) {
            $rpsTasksByElement[(int) $o->fk_element][] = [
                'id'       => (int) $o->rowid,
                'ref'      => (string) $o->ref,
                'label'    => (string) $o->label,
                'progress' => (int) $o->progress,
                'done'     => ((int) $o->progress >= 100 || (int) $o->fk_statut == 3),
                'due'      => $o->datee,
                'owner'    => $o->fk_user ? trim($o->firstname . ' ' . $o->lastname) : '',
                'owner_id' => (int) $o->fk_user,
            ];
        }
    }
}

/* --------------------------------------------------------------------------
 * Utilisateurs proposables comme responsable d'une action
 * ----------------------------------------------------------------------- */

if (!isset($rpsUsers)) {
    $rpsUsers = [];

    $sqlUsers  = 'SELECT rowid, lastname, firstname FROM ' . MAIN_DB_PREFIX . 'user';
    $sqlUsers .= ' WHERE statut = 1 AND entity IN (0, ' . (int) $conf->entity . ')';
    $sqlUsers .= ' ORDER BY lastname ASC, firstname ASC';

    $resUsers = $db->query($sqlUsers);
    if ($resUsers) {
        while ($o = $db->fetch_object($resUsers)) {
            $rpsUsers[(int) $o->rowid] = trim($o->firstname . ' ' . $o->lastname);
        }
    }
}

/* --------------------------------------------------------------------------
 * Qualification du perimetre : une ligne par unite de travail dans rpsscope.
 * -1 = a qualifier (defaut), 0 = non concernee (motif obligatoire), 1 = concernee.
 * ----------------------------------------------------------------------- */

if (!isset($rpsScopeRows)) {
    $rpsScopeRows = [];

    $sqlScope  = 'SELECT fk_element, concerned, reason FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope';
    $sqlScope .= ' WHERE entity = ' . (int) $conf->entity;

    $resScope = $db->query($sqlScope);
    if ($resScope) {
        while ($o = $db->fetch_object($resScope)) {
            $rpsScopeRows[(int) $o->fk_element] = [
                'state'  => (int) $o->concerned,
                'reason' => (string) $o->reason,
            ];
        }
    }
}

/* --------------------------------------------------------------------------
 * Perimetre : toutes les unites de travail de l'entite.
 * La qualification est prise une seule fois, sur le groupement de tete, et commande
 * ensuite l'affichage du risque psychosocial sur chaque unite.
 * ----------------------------------------------------------------------- */

$rpsLines       = [];
$rpsStateByUnit = [];
if (is_array($activeDigiriskElementList)) {
    foreach ($activeDigiriskElementList as $elementId => $element) {
        if ($element->element_type != 'workunit') {
            continue;
        }

        $scope = $rpsScopeRows[$elementId] ?? ['state' => -1, 'reason' => ''];
        $line  = [
            'id'      => $elementId,
            'ref'     => $element->ref,
            'label'   => $element->label,
            'state'   => $scope['state'],
            'reason'  => $scope['reason'],
            'date'    => '',
            'levels'  => [],
            'orphans' => $rpsExistingRefs[$elementId] ?? [],
            'tasks'   => $rpsTasksByElement[$elementId] ?? [],
        ];

        if (!empty($rpsAssessmentByElement[$elementId])) {
            // Grille consolidee : une evaluation, un niveau par facteur
            $line['levels'] = $rpsAssessmentByElement[$elementId]['factors'];
            $line['date']   = $rpsAssessmentByElement[$elementId]['date'];
            if ($line['state'] !== 0) {
                $line['state'] = 1;
            }
        } elseif ($line['state'] !== 0 && !empty($rpsExistingRefs[$elementId])) {
            // Ancienne methode : un risque par facteur, repris en lecture le temps de la migration.
            // Une unite declaree non concernee le reste, ses risques sont signales comme a archiver.
            $line['state'] = 1;
            $line['date']  = $rpsExistingDate[$elementId];
            foreach (($rpsExistingByElement[$elementId] ?? []) as $position => $cotation) {
                $line['levels'][$position] = rps_scope_level($cotation);
            }
        }

        $line['done'] = $line['state'] === 1 && count($line['levels']) > 0;
        if ($line['done']) {
            // La cotation suit la moyenne des facteurs : c'est le chiffre affiche, le palier
            // doit lui correspondre. Le facteur le plus severe est signale a part plutot que
            // de tirer toute l'unite vers le haut.
            $line['average'] = array_sum($line['levels']) / count($line['levels']);
            $line['band']    = rps_scope_band($line['average']);
            $line['worst']   = max($line['levels']);

            // Un facteur cote Eleve ou Extreme appelle une action de prevention
            $line['toTreat'] = [];
            foreach ($line['levels'] as $rpsFactorPosition => $rpsFactorLevel) {
                if ($rpsFactorLevel >= 3) {
                    $line['toTreat'][] = $rpsFactorPosition;
                }
            }
        }

        $rpsStateByUnit[(int) $elementId] = $line['state'];

        $rpsLines[] = $line;
    }
}

usort($rpsLines, function ($a, $b) {
    return strnatcmp($a['ref'], $b['ref']);
});

