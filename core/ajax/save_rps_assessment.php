<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    core/ajax/save_rps_assessment.php
 * \ingroup digiriskdolibarr
 * \brief   Enregistrement consolide des risques psychosociaux.
 *
 *          Contrairement a create_risk.php, qui cree un risque par critere coté et fait
 *          donc gonfler la liste des risques d'une unite de 26 lignes a chaque grille,
 *          cet appel maintient UN SEUL risque psychosocial par unite de travail :
 *          - le risque de categorie 17 de l'unite est reutilise s'il existe, cree sinon,
 *          - une evaluation porte la cotation consolidee de la grille,
 *          - le detail par critere part dans llx_digiriskdolibarr_rpsanswer.
 *
 *          Gere aussi la qualification du perimetre (concernee / non concernee).
 */

if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_risk.lib.php';

global $conf, $db, $langs, $user;

header('Content-Type: application/json');

if (empty($user->id) || !$user->hasRight('digiriskdolibarr', 'risk', 'write')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

/**
 * Traduit une cotation Digirisk (0 a 100) en palier de la grille RPS (1 a 4).
 *
 * @param  int $cotation Cotation enregistree sur l'evaluation
 * @return int           Palier de 1 a 4
 */
function rps_cotation_to_scale(int $cotation): int
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
function rps_average_to_scale(float $average): int
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

$action    = $data['action'] ?? '';
$elementId = (int) ($data['fk_element'] ?? 0);

// La qualification peut viser plusieurs unites d'un coup : la selection du tableau du groupement de tete
$elementIds = $elementId > 0 ? [$elementId] : [];
if ($action == 'scope' && isset($data['fk_elements']) && is_array($data['fk_elements'])) {
    $elementIds = array_values(array_unique(array_filter(array_map('intval', $data['fk_elements']), function ($id) {
        return $id > 0;
    })));
}

if (empty($elementIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing work unit']);
    exit;
}

/* --------------------------------------------------------------------------
 * Qualification du perimetre : une ligne par unite de travail
 * ----------------------------------------------------------------------- */

if ($action == 'scope') {
    $concerned = (int) ($data['concerned'] ?? -1);
    if (!in_array($concerned, [-1, 0, 1], true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid state']);
        exit;
    }

    $reason = trim((string) ($data['reason'] ?? ''));

    $db->begin();

    // Toutes les unites ou aucune : une selection a moitie qualifiee ne se verrait pas dans le tableau
    foreach ($elementIds as $scopeElementId) {
        $sql  = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope';
        $sql .= ' WHERE fk_element = ' . $scopeElementId . ' AND entity = ' . (int) $conf->entity;
        $resql = $db->query($sql);
        $existing = ($resql && $db->num_rows($resql) > 0) ? $db->fetch_object($resql)->rowid : 0;

        if ($existing > 0) {
            $sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope';
            $sql .= " SET concerned = " . $concerned . ", reason = '" . $db->escape($reason) . "'";
            $sql .= ', fk_user_modif = ' . (int) $user->id;
            $sql .= ' WHERE rowid = ' . (int) $existing;
        } else {
            $sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope';
            $sql .= ' (entity, date_creation, fk_element, concerned, reason, fk_user_modif) VALUES (';
            $sql .= (int) $conf->entity . ", '" . $db->idate(dol_now()) . "', " . $scopeElementId . ', ';
            $sql .= $concerned . ", '" . $db->escape($reason) . "', " . (int) $user->id . ')';
        }

        if (!$db->query($sql)) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(['error' => $db->lasterror()]);
            exit;
        }
    }

    $db->commit();
    echo json_encode(['success' => 1, 'concerned' => $concerned, 'elements' => $elementIds]);
    exit;
}

/* --------------------------------------------------------------------------
 * Ajout d'une action de prevention, sans repasser par l'evaluation
 * ----------------------------------------------------------------------- */

if ($action == 'task_create') {
    $taskLabel = trim((string) ($data['label'] ?? ''));
    if ($taskLabel === '') {
        http_response_code(400);
        echo json_encode(['error' => "Libellé de l'action manquant"]);
        exit;
    }

    // L'action se rattache au risque de l'evaluation consolidee, comme celles saisies dans l'evaluation
    $sql  = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk';
    $sql .= ' WHERE fk_element = ' . $elementId;
    $sql .= ' AND category = ' . (int) Risk::PSYCHOSOCIAL_CATEGORY_POSITION;
    $sql .= ' AND status > 0 AND (sub_category IS NULL OR sub_category = -1)';
    $sql .= ' AND entity IN (' . getEntity('digiriskelement') . ')';
    $sql .= ' ORDER BY rowid ASC LIMIT 1';

    $resql  = $db->query($sql);
    $riskId = ($resql && $db->num_rows($resql) > 0) ? (int) $db->fetch_object($resql)->rowid : 0;
    if ($riskId <= 0) {
        http_response_code(409);
        echo json_encode(['error' => "Évaluez l'unité avant de lui ajouter une action : l'action se rattache à son évaluation."]);
        exit;
    }

    // Le facteur vise part en tete du libelle, comme pour les actions saisies dans l'evaluation
    $factor = (int) ($data['factor'] ?? -1);
    foreach ((Risk::getDangerSubCategories()[Risk::PSYCHOSOCIAL_CATEGORY_POSITION] ?? []) as $subCategory) {
        if ((int) $subCategory['position'] === $factor && $factor <= 5) {
            $taskLabel = $langs->transnoentities($subCategory['label']) . ' : ' . $taskLabel;
        }
    }

    list($refTaskMod) = saturne_require_objects_mod(['project/task' => $conf->global->PROJECT_TASK_ADDON], 'digiriskdolibarr');

    $db->begin();

    $task                                   = new Task($db);
    $task->ref                              = $refTaskMod->getNextValue('', $task);
    $task->label                            = $taskLabel;
    $task->description                      = '';
    $task->status                           = 1;
    $task->progress                         = 0;
    $task->fk_project                       = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
    $task->array_options['options_fk_risk'] = $riskId;
    $task->date_start                       = dol_now();
    $task->date_end                         = dol_now() + 30 * 86400;

    if ($task->create($user) <= 0) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $task->errorsToString() ?: $task->error]);
        exit;
    }

    $db->commit();
    echo json_encode(['success' => 1, 'task' => $task->id, 'ref' => $task->ref]);
    exit;
}

/* --------------------------------------------------------------------------
 * Pilotage d'une action : responsable, echeance, etat, suppression.
 * Le bloc psychosocial affiche le plan d'action de l'unite ; il doit aussi
 * permettre de le tenir, sans ouvrir chaque tache une par une.
 * ----------------------------------------------------------------------- */

if ($action == 'task_update' || $action == 'task_delete') {
    $taskId = (int) ($data['task_id'] ?? 0);
    if ($taskId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing task']);
        exit;
    }

    // La tache doit bien etre une action du risque psychosocial de cette unite :
    // cet appel ne doit pas servir a modifier n'importe quelle tache du projet.
    $sql  = 'SELECT t.rowid FROM ' . MAIN_DB_PREFIX . 'projet_task t';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'projet_task_extrafields te ON te.fk_object = t.rowid';
    $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk r ON r.rowid = te.fk_risk';
    $sql .= ' WHERE t.rowid = ' . $taskId;
    $sql .= ' AND r.fk_element = ' . $elementId;
    $sql .= ' AND r.category = ' . (int) Risk::PSYCHOSOCIAL_CATEGORY_POSITION;
    $sql .= ' AND r.entity IN (' . getEntity('digiriskelement') . ')';

    $resql = $db->query($sql);
    if (!$resql || $db->num_rows($resql) == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Action introuvable sur cette unite']);
        exit;
    }

    $task = new Task($db);
    if ($task->fetch($taskId) <= 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Action introuvable']);
        exit;
    }

    $db->begin();

    if ($action == 'task_delete') {
        if ($task->delete($user) <= 0) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(['error' => $task->errorsToString() ?: $task->error]);
            exit;
        }

        $db->commit();
        echo json_encode(['success' => 1, 'deleted' => $taskId]);
        exit;
    }

    if (array_key_exists('done', $data)) {
        // Une action faite est a 100 % : c'est ce que lit la fiche de tache et le plan d'action
        $task->progress = !empty($data['done']) ? 100 : 0;
    }

    if (array_key_exists('due', $data)) {
        $due              = trim((string) $data['due']);
        $task->date_end   = $due !== '' ? strtotime($due) : null;
    }

    if ($task->update($user) <= 0) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $task->errorsToString() ?: $task->error]);
        exit;
    }

    if (array_key_exists('owner', $data)) {
        $owner = (int) $data['owner'];

        // Un seul responsable : les contacts internes precedents sont retires avant d'ajouter
        foreach (($task->liste_contact(-1, 'internal') ?: []) as $contact) {
            if ($contact['code'] == 'TASKEXECUTIVE') {
                $task->delete_contact($contact['rowid']);
            }
        }

        if ($owner > 0 && $task->add_contact($owner, 'TASKEXECUTIVE', 'internal') < 0) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(['error' => $task->errorsToString() ?: $task->error]);
            exit;
        }
    }

    $db->commit();
    echo json_encode(['success' => 1, 'task' => $taskId]);
    exit;
}
/* --------------------------------------------------------------------------
 * Consolidation : les risques psychosociaux eclates d'une unite deviennent
 * une evaluation unique, et les originaux partent aux archives.
 * ----------------------------------------------------------------------- */

if ($action == 'consolidate') {
    $criteria   = Risk::getPsychosocialRiskGrid();
    $byName     = [];
    foreach ($criteria as $criterion) {
        $byName[mb_strtolower(trim($criterion['name']))] = $criterion;
    }

    $needles = [
        'exigences' => 0, 'motionnel' => 0,
        'intensit'  => 1, 'horaires'  => 1,
        'autonomie' => 2,
        'rapports'  => 3,
        'sens'      => 4, 'valeurs'   => 4,
        'curit'     => 5,
    ];

    $sql  = 'SELECT r.rowid, r.sub_category, r.description,';
    $sql .= ' (SELECT ra.cotation FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment ra';
    $sql .= '   WHERE ra.fk_risk = r.rowid AND ra.status > 0 ORDER BY ra.rowid DESC LIMIT 1) AS cotation,';
    $sql .= ' (SELECT ra.date_riskassessment FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment ra';
    $sql .= '   WHERE ra.fk_risk = r.rowid AND ra.status > 0 ORDER BY ra.rowid DESC LIMIT 1) AS dt';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk r';
    $sql .= ' WHERE r.fk_element = ' . $elementId;
    $sql .= ' AND r.category = ' . (int) Risk::PSYCHOSOCIAL_CATEGORY_POSITION;
    $sql .= ' AND r.status NOT IN (' . Risk::STATUS_DELETED . ', ' . Risk::STATUS_ARCHIVED . ')';
    $sql .= ' AND r.entity IN (' . getEntity('digiriskelement') . ')';
    $sql .= ' ORDER BY r.rowid ASC';

    $resql = $db->query($sql);
    if (!$resql) {
        http_response_code(500);
        echo json_encode(['error' => $db->lasterror()]);
        exit;
    }

    $sources    = [];
    $extraIds   = [];
    $keepRiskId = 0;
    $latestDate = '';
    while ($o = $db->fetch_object($resql)) {
        $sub = ($o->sub_category === null || $o->sub_category === '') ? null : (int) $o->sub_category;

        // Le risque consolide est celui qui ne porte pas de facteur : il est conserve
        if ($sub === null || $sub < 0) {
            if ($keepRiskId <= 0) {
                $keepRiskId = (int) $o->rowid;
                continue;
            }
        }

        if ($sub === null || $sub > 5) {
            $description = mb_strtolower(trim((string) $o->description));
            foreach ($needles as $needle => $position) {
                if (mb_strpos($description, $needle) !== false) {
                    $sub = $position;
                    break;
                }
            }
        }

        // Les positions 6 et 7 decrivent le contexte de prevention et l'impact des RPS : ce ne
        // sont pas des facteurs d'exposition, mais elles viennent du meme lot de risques et
        // n'ont rien a faire dans la liste une fois l'unite consolidee.
        if ($sub === 6 || $sub === 7) {
            $extraIds[] = (int) $o->rowid;
            continue;
        }

        if ($sub === null || $sub < 0 || $sub > 5) {
            continue;
        }

        $matched = $byName[mb_strtolower(trim((string) $o->description))] ?? null;

        $sources[] = [
            'rowid'     => (int) $o->rowid,
            'sub'       => $sub,
            'scale'     => rps_cotation_to_scale((int) $o->cotation),
            'criterion' => $matched ? (int) $matched['position'] : 0,
            'label'     => (string) $o->description,
        ];

        if (!empty($o->dt)) {
            $latestDate = max($latestDate, (string) $o->dt);
        }
    }

    if (empty($sources)) {
        echo json_encode(['success' => 0, 'message' => 'Rien a consolider']);
        exit;
    }

    $db->begin();

    $risk           = new Risk($db);
    $riskAssessment = new RiskAssessment($db);
    $project        = new Project($db);

    $numberingModuleName = [
        'riskanalysis/' . $risk->element           => $conf->global->DIGIRISKDOLIBARR_RISK_ADDON,
        'riskanalysis/' . $riskAssessment->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON,
        $project->element                          => $conf->global->PROJECT_ADDON,
        'project/task'                             => $conf->global->PROJECT_TASK_ADDON,
    ];
    list($refRiskMod, $refEvaluationMod) = saturne_require_objects_mod($numberingModuleName, 'digiriskdolibarr');

    if ($keepRiskId <= 0) {
        $risk               = new Risk($db);
        $risk->fk_element   = $elementId;
        $risk->ref          = $refRiskMod->getNextValue($risk);
        $risk->category     = Risk::PSYCHOSOCIAL_CATEGORY_POSITION;
        $risk->sub_category = Risk::SUB_CATEGORY_NOT_SPECIFIED;
        $risk->description  = 'Risques psychosociaux';
        $risk->status       = 1;
        $risk->fk_projet    = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

        if ($risk->create($user) <= 0) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(['error' => $risk->errorsToString() ?: $risk->error]);
            exit;
        }
        $keepRiskId = $risk->id;
    }

    $factors = [];
    foreach ($sources as $source) {
        $factors[$source['sub']] = max($factors[$source['sub']] ?? 0, $source['scale']);
    }
    $scaleToCotation = [1 => 0, 2 => 48, 3 => 51, 4 => 100];

    $evaluation                      = new RiskAssessment($db);
    $evaluation->fk_risk             = $keepRiskId;
    $evaluation->ref                 = $refEvaluationMod->getNextValue($evaluation);
    $evaluation->cotation            = $scaleToCotation[rps_average_to_scale(array_sum($factors) / count($factors))];
    $evaluation->status              = 1;
    $evaluation->method              = 'rps';
    $evaluation->comment             = 'Consolidation de ' . count($sources) . ' risques psychosociaux';
    $evaluation->date_riskassessment = $latestDate ? $db->jdate($latestDate) : dol_now();

    if ($evaluation->create($user) <= 0) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $evaluation->errorsToString() ?: $evaluation->error]);
        exit;
    }

    $now  = $db->idate(dol_now());
    $seen = [];
    foreach ($sources as $source) {
        // La contrainte d'unicite porte sur (evaluation, critere) : un critere vu deux fois
        // ne doit pas faire echouer toute la consolidation
        $criterion = $source['criterion'];
        while (isset($seen[$criterion])) {
            $criterion += 1000;
        }
        $seen[$criterion] = true;

        $sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsanswer';
        $sql .= ' (entity, date_creation, fk_riskassessment, criterion, criterion_label, sub_category, scale, cotation, fk_user_creat)';
        $sql .= ' VALUES (' . (int) $conf->entity . ", '" . $now . "', " . (int) $evaluation->id . ', ';
        $sql .= (int) $criterion . ", '" . $db->escape(dol_trunc($source['label'], 255, 'right', 'UTF-8', 1)) . "', ";
        $sql .= (int) $source['sub'] . ', ' . (int) $source['scale'] . ', ' . $scaleToCotation[$source['scale']] . ', ' . (int) $user->id . ')';

        if (!$db->query($sql)) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(['error' => $db->lasterror()]);
            exit;
        }
    }

    // Les originaux partent aux archives : ils sortent de la liste active sans etre detruits
    $archivedIds = $extraIds;
    foreach ($sources as $source) {
        $archivedIds[] = $source['rowid'];
    }
    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk SET status = ' . Risk::STATUS_ARCHIVED;
    $sql .= ' WHERE rowid IN (' . implode(',', $archivedIds) . ')';
    if (!$db->query($sql)) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $db->lasterror()]);
        exit;
    }

    $db->commit();

    echo json_encode([
        'success'  => 1,
        'archived' => count($archivedIds),
        'fk_risk'  => $keepRiskId,
        'cotation' => $evaluation->cotation,
    ]);
    exit;
}

/* --------------------------------------------------------------------------
 * Evaluation : un risque, une evaluation, N reponses
 * ----------------------------------------------------------------------- */

if ($action != 'assessment') {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

$answers = (isset($data['answers']) && is_array($data['answers'])) ? $data['answers'] : [];
if (empty($answers)) {
    http_response_code(400);
    echo json_encode(['error' => 'No answer']);
    exit;
}

// La cotation de l'unite est la plus severe de ses facteurs : un facteur rouge ne doit pas
// etre noye par cinq facteurs verts. La moyenne reste lisible sur le detail des reponses.
$byFactor = [];
foreach ($answers as $answer) {
    $factor = (int) ($answer['sub_category'] ?? 0);
    $scale  = (int) ($answer['scale'] ?? 0);
    if ($scale < 1 || $scale > 4) {
        continue;
    }
    // Les positions 6 et 7 decrivent le contexte de prevention et l'impact des RPS :
    // elles sont conservees avec l'evaluation mais n'entrent pas dans la cotation.
    if ($factor > 5) {
        continue;
    }
    $byFactor[$factor] = max($byFactor[$factor] ?? 0, $scale);
}

if (empty($byFactor)) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid answer']);
    exit;
}

$scaleToCotation   = [1 => 0, 2 => 48, 3 => 51, 4 => 100];
$consolidatedValue = $scaleToCotation[rps_average_to_scale(array_sum($byFactor) / count($byFactor))];

$assessmentDate = !empty($data['date']) ? strtotime($data['date']) : dol_now();

$db->begin();

$risk           = new Risk($db);
$riskAssessment = new RiskAssessment($db);
$project        = new Project($db);

$numberingModuleName = [
    'riskanalysis/' . $risk->element           => $conf->global->DIGIRISKDOLIBARR_RISK_ADDON,
    'riskanalysis/' . $riskAssessment->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON,
    $project->element                          => $conf->global->PROJECT_ADDON,
    'project/task'                             => $conf->global->PROJECT_TASK_ADDON,
];
list($refRiskMod, $refEvaluationMod, $refProjectMod, $refTaskMod) = saturne_require_objects_mod($numberingModuleName, 'digiriskdolibarr');

// Le risque psychosocial consolide de l'unite : celui qui ne porte pas de facteur.
// Les risques de l'ancienne methode, un par facteur, ne sont pas repris ici.
$sql  = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk';
$sql .= ' WHERE fk_element = ' . $elementId;
$sql .= ' AND category = ' . (int) Risk::PSYCHOSOCIAL_CATEGORY_POSITION;
$sql .= ' AND status > 0 AND (sub_category IS NULL OR sub_category = -1)';
$sql .= ' AND entity IN (' . getEntity('digiriskelement') . ')';
$sql .= ' ORDER BY rowid ASC LIMIT 1';

$resql  = $db->query($sql);
$riskId = ($resql && $db->num_rows($resql) > 0) ? (int) $db->fetch_object($resql)->rowid : 0;

if ($riskId <= 0) {
    $risk               = new Risk($db);
    $risk->fk_element   = $elementId;
    $risk->ref          = $refRiskMod->getNextValue($risk);
    $risk->category     = Risk::PSYCHOSOCIAL_CATEGORY_POSITION;
    $risk->sub_category = Risk::SUB_CATEGORY_NOT_SPECIFIED;
    $risk->description  = $langs->transnoentities('PsychosocialRisks') != 'PsychosocialRisks'
        ? $langs->transnoentities('PsychosocialRisks')
        : 'Risques psychosociaux';
    $risk->status       = 1;
    $risk->fk_projet    = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;

    if ($risk->create($user) <= 0) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $risk->errorsToString() ?: $risk->error]);
        exit;
    }
    $riskId = $risk->id;
}

$evaluation                      = new RiskAssessment($db);
$evaluation->fk_risk             = $riskId;
$evaluation->ref                 = $refEvaluationMod->getNextValue($evaluation);
$evaluation->cotation            = $consolidatedValue;
$evaluation->status              = 1;
$evaluation->method              = 'rps';
$evaluation->comment             = trim((string) ($data['comment'] ?? ''));
$evaluation->date_riskassessment = $assessmentDate;

// Le rapport joint pendant la saisie (photos et documents) rejoint le dossier de l'evaluation
digiriskRiskAssessmentMovePhotos($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . digiriskRiskAssessmentPsychosocialWorkDir($elementId), $evaluation);

if ($evaluation->create($user) <= 0) {
    $db->rollback();
    http_response_code(500);
    echo json_encode(['error' => $evaluation->errorsToString() ?: $evaluation->error]);
    exit;
}

$now = $db->idate(dol_now());
foreach ($answers as $answer) {
    $scale = (int) ($answer['scale'] ?? 0);
    if ($scale < 1 || $scale > 4) {
        continue;
    }

    $sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsanswer';
    $sql .= ' (entity, date_creation, fk_riskassessment, criterion, criterion_label, sub_category, scale, cotation, comment, fk_user_creat)';
    $sql .= ' VALUES (' . (int) $conf->entity . ", '" . $now . "', " . (int) $evaluation->id . ', ';
    $sql .= (int) ($answer['criterion'] ?? 0) . ", '" . $db->escape(dol_trunc((string) ($answer['label'] ?? ''), 255, 'right', 'UTF-8', 1)) . "', ";
    $sql .= (int) ($answer['sub_category'] ?? 0) . ', ' . $scale . ', ' . $scaleToCotation[$scale] . ', ';
    $sql .= "'" . $db->escape((string) ($answer['comment'] ?? '')) . "', " . (int) $user->id . ')';

    if (!$db->query($sql)) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $db->lasterror()]);
        exit;
    }
}

// Une action de prevention par facteur demande, rattachee au risque consolide.
// Le responsable est pose comme contact interne TASKEXECUTIVE : c'est ce que lit la
// fiche de la tache, et ce qui fait apparaitre l'action dans "mes taches".
foreach (($data['tasks'] ?? []) as $taskData) {
    $taskLabel = trim((string) (is_array($taskData) ? ($taskData['label'] ?? '') : $taskData));
    if ($taskLabel === '') {
        continue;
    }

    $taskOwner = is_array($taskData) ? (int) ($taskData['owner'] ?? 0) : 0;
    $taskDue   = is_array($taskData) ? trim((string) ($taskData['due'] ?? '')) : '';

    $task                                   = new Task($db);
    $task->ref                              = $refTaskMod->getNextValue('', $task);
    $task->label                            = $taskLabel;
    $task->description                      = '';
    $task->status                           = 1;
    $task->progress                         = 0;
    $task->fk_project                       = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
    $task->array_options['options_fk_risk'] = $riskId;
    $task->date_start                       = dol_now();
    $task->date_end                         = $taskDue !== '' ? strtotime($taskDue) : (dol_now() + 30 * 86400);

    if ($task->create($user) <= 0) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $task->errorsToString() ?: $task->error]);
        exit;
    }

    if ($taskOwner > 0 && $task->add_contact($taskOwner, 'TASKEXECUTIVE', 'internal') < 0) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $task->errorsToString() ?: $task->error]);
        exit;
    }
}

// La qualification suit l'evaluation : evaluer une unite, c'est la declarer concernee
$sql  = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope';
$sql .= ' WHERE fk_element = ' . $elementId . ' AND entity = ' . (int) $conf->entity;
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    $db->query('UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope SET concerned = 1, fk_user_modif = ' . (int) $user->id
        . ' WHERE rowid = ' . (int) $db->fetch_object($resql)->rowid);
} else {
    $db->query('INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope (entity, date_creation, fk_element, concerned, fk_user_modif) VALUES ('
        . (int) $conf->entity . ", '" . $now . "', " . $elementId . ', 1, ' . (int) $user->id . ')');
}

$db->commit();

echo json_encode([
    'success'    => 1,
    'fk_risk'    => $riskId,
    'assessment' => $evaluation->ref,
    'cotation'   => $consolidatedValue,
    'factors'    => count($byFactor),
]);
