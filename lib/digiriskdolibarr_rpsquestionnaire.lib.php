<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/digiriskdolibarr_rpsquestionnaire.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Psychosocial risk questionnaires (#5324): the employees answer the RPS-DU grid of the INRS brochure
 *          ED 6403 themselves, anonymously, through a public link.
 *
 * A session targets a job category (an exposure group of #5644, or every category, the respondent then gives
 * his) and some work units, with a number of answers accepted per work unit. Each answer is given for one of
 * those work units. The answers of a work unit are averaged criterion by criterion, then read as the grid of
 * the assessment modal reads them: a criterion takes the level of its average, a factor the worst of its
 * criteria, the work unit the average of its factors. Closing the session records that average as the
 * psychosocial assessment of each work unit that got answers, with the "rps" method of the other grids: the
 * risk list, the public overview and the risk assessment document read it like any other one, and the
 * document names the questionnaire as the method that gave it.
 *
 * Tables: llx_digiriskdolibarr_rpssession (the sessions), llx_digiriskdolibarr_rpssession_element (their
 * work units, with the assessment recorded at the closure) and llx_digiriskdolibarr_rpsresponse (the answers).
 */

require_once __DIR__ . '/../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/digiriskdolibarr_exposuregroup.lib.php';

/**
 * Status of a session that takes answers
 */
const DIGIRISK_RPS_SESSION_OPEN = 1;

/**
 * Status of a closed session: no more answers, its averages are recorded on the work units
 */
const DIGIRISK_RPS_SESSION_CLOSED = 2;

/**
 * Return the criteria of the questionnaire: the RPS-DU grid, by position
 *
 * The grid asks a working group about "the employees"; the questionnaire asks the employee himself, with the
 * same criterion, in the second person ("questionSelf" of the grid, the question of the grid otherwise).
 *
 * @return array<int, array> Criteria (position, family, subCategory, name, question, questionSelf, reversed), by position
 */
function digiriskRpsQuestionnaireGrid(): array
{
    $grid = [];
    foreach (Risk::getPsychosocialRiskGrid() as $criterion) {
        $criterion['questionSelf']                = !empty($criterion['questionSelf']) ? $criterion['questionSelf'] : $criterion['question'];
        $grid[(int) $criterion['position']] = $criterion;
    }

    return $grid;
}

/**
 * Level of an average of levels, from 1 to 4: the bands of the other psychosocial grids
 *
 * @param  float $average Average of levels from 1 to 4
 * @return int            Level from 1 to 4
 */
function digiriskRpsQuestionnaireBand(float $average): int
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
 * Return the sessions of the current entity, newest first, with their work units and number of answers
 *
 * @param  DoliDB $db        Database handler
 * @param  int    $sessionId Only this session, all of them when 0
 * @return array<int, array> Sessions by ID: rowid, label, fk_exposuregroup, max_answers, public_key, status, date_creation,
 *                           date_closure, total (answers), elements (by work unit ID: fk_riskassessment, count)
 */
function digiriskRpsSessionFetchAll(DoliDB $db, int $sessionId = 0): array
{
    global $conf;

    $sessions = [];

    $sql  = 'SELECT rowid, label, fk_exposuregroup, max_answers, public_key, status, date_creation, date_closure';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession';
    $sql .= ' WHERE entity = ' . (int) $conf->entity;
    if ($sessionId > 0) {
        $sql .= ' AND rowid = ' . $sessionId;
    }
    $sql .= ' ORDER BY rowid DESC';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return $sessions;
    }
    while ($obj = $db->fetch_object($resql)) {
        $sessions[(int) $obj->rowid] = [
            'rowid'            => (int) $obj->rowid,
            'label'            => (string) $obj->label,
            'fk_exposuregroup' => (int) $obj->fk_exposuregroup,
            'max_answers'      => (int) $obj->max_answers,
            'public_key'       => (string) $obj->public_key,
            'status'           => (int) $obj->status,
            'date_creation'    => $db->jdate($obj->date_creation),
            'date_closure'     => $obj->date_closure ? $db->jdate($obj->date_closure) : null,
            'total'            => 0,
            'elements'         => [],
        ];
    }
    $db->free($resql);

    if (empty($sessions)) {
        return $sessions;
    }

    // Work units of the sessions with their number of answers, in one query
    $sql  = 'SELECT se.fk_session, se.fk_element, se.fk_riskassessment, COUNT(r.rowid) AS nb';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession_element se';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsresponse r ON r.fk_session = se.fk_session AND r.fk_element = se.fk_element';
    $sql .= ' WHERE se.fk_session IN (' . implode(',', array_keys($sessions)) . ')';
    $sql .= ' GROUP BY se.fk_session, se.fk_element, se.fk_riskassessment';

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $sessions[(int) $obj->fk_session]['elements'][(int) $obj->fk_element] = [
                'fk_riskassessment' => (int) $obj->fk_riskassessment,
                'count'             => (int) $obj->nb,
            ];
            $sessions[(int) $obj->fk_session]['total'] += (int) $obj->nb;
        }
        $db->free($resql);
    }

    return $sessions;
}

/**
 * Return the session a public link opens
 *
 * @param  DoliDB     $db  Database handler
 * @param  string     $key Secret key of the link
 * @return array|null      Session as digiriskRpsSessionFetchAll() returns it, null when the key opens none
 */
function digiriskRpsSessionFetchByKey(DoliDB $db, string $key): ?array
{
    global $conf;

    // A key is 32 hexadecimal characters: anything else is not even looked for
    if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
        return null;
    }

    $sql  = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession';
    $sql .= " WHERE public_key = '" . $db->escape($key) . "' AND entity = " . (int) $conf->entity;

    $resql = $db->query($sql);
    $obj   = $resql ? $db->fetch_object($resql) : false;
    if (!$obj) {
        return null;
    }
    $sessionId = (int) $obj->rowid;
    $db->free($resql);

    return digiriskRpsSessionFetchAll($db, $sessionId)[$sessionId] ?? null;
}

/**
 * Create or update a session, with its work units
 *
 * A work unit that already got answers stays in the session: taking it out would leave its answers aside.
 *
 * @param  DoliDB $db         Database handler
 * @param  User   $user       User saving the session
 * @param  int    $sessionId  Session to update, 0 to create one
 * @param  string $label      Label, required
 * @param  int    $groupId    Job category (exposure group), 0 for every category
 * @param  int    $maxAnswers Answers accepted per work unit, 0 for no limit
 * @param  int[]  $elementIds Work units of the session
 * @return int                ID of the session, <= 0 on error
 */
function digiriskRpsSessionSave(DoliDB $db, User $user, int $sessionId, string $label, int $groupId, int $maxAnswers, array $elementIds): int
{
    global $conf;

    $db->begin();

    if ($sessionId > 0) {
        $sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession SET';
        $sql .= " label = '" . $db->escape($label) . "',";
        $sql .= ' fk_exposuregroup = ' . $groupId . ',';
        $sql .= ' max_answers = ' . $maxAnswers . ',';
        $sql .= ' fk_user_modif = ' . (int) $user->id;
        $sql .= ' WHERE rowid = ' . $sessionId . ' AND entity = ' . (int) $conf->entity;
        if (!$db->query($sql)) {
            $db->rollback();
            return -1;
        }
    } else {
        $sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession';
        $sql .= ' (entity, date_creation, label, fk_exposuregroup, max_answers, public_key, status, fk_user_creat) VALUES (';
        $sql .= (int) $conf->entity . ", '" . $db->idate(dol_now()) . "', '" . $db->escape($label) . "', " . $groupId . ', ' . $maxAnswers . ', ';
        $sql .= "'" . bin2hex(random_bytes(16)) . "', " . DIGIRISK_RPS_SESSION_OPEN . ', ' . (int) $user->id . ')';
        if (!$db->query($sql)) {
            $db->rollback();
            return -1;
        }
        $sessionId = (int) $db->last_insert_id(MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession');
    }

    $elementIds = array_values(array_unique(array_filter(array_map('intval', $elementIds), function ($id) {
        return $id > 0;
    })));

    // Work units taken out, unless they got answers
    $sql  = 'DELETE FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession_element';
    $sql .= ' WHERE fk_session = ' . $sessionId;
    if (!empty($elementIds)) {
        $sql .= ' AND fk_element NOT IN (' . implode(',', $elementIds) . ')';
    }
    $sql .= ' AND fk_element NOT IN (SELECT r.fk_element FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsresponse r WHERE r.fk_session = ' . $sessionId . ')';
    if (!$db->query($sql)) {
        $db->rollback();
        return -1;
    }

    $existingIds = [];
    $resql       = $db->query('SELECT fk_element FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession_element WHERE fk_session = ' . $sessionId);
    if (!$resql) {
        $db->rollback();
        return -1;
    }
    while ($obj = $db->fetch_object($resql)) {
        $existingIds[] = (int) $obj->fk_element;
    }

    foreach (array_diff($elementIds, $existingIds) as $elementId) {
        $sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession_element (entity, fk_session, fk_element)';
        $sql .= ' VALUES (' . (int) $conf->entity . ', ' . $sessionId . ', ' . (int) $elementId . ')';
        if (!$db->query($sql)) {
            $db->rollback();
            return -1;
        }
    }

    $db->commit();

    return $sessionId;
}

/**
 * Open or close a session
 *
 * @param  DoliDB $db        Database handler
 * @param  User   $user      User changing the status
 * @param  int    $sessionId Session
 * @param  int    $status    DIGIRISK_RPS_SESSION_OPEN or DIGIRISK_RPS_SESSION_CLOSED
 * @return int               1 if OK, < 0 on error
 */
function digiriskRpsSessionSetStatus(DoliDB $db, User $user, int $sessionId, int $status): int
{
    global $conf;

    $sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession SET status = ' . $status . ',';
    $sql .= ' date_closure = ' . ($status === DIGIRISK_RPS_SESSION_CLOSED ? "'" . $db->idate(dol_now()) . "'" : 'NULL') . ',';
    $sql .= ' fk_user_modif = ' . (int) $user->id;
    $sql .= ' WHERE rowid = ' . $sessionId . ' AND entity = ' . (int) $conf->entity;

    return $db->query($sql) ? 1 : -1;
}

/**
 * Delete a session with its work units and answers. The assessments it recorded stay on the work units.
 *
 * @param  DoliDB $db        Database handler
 * @param  int    $sessionId Session
 * @return int               1 if OK, < 0 on error
 */
function digiriskRpsSessionDelete(DoliDB $db, int $sessionId): int
{
    global $conf;

    $db->begin();

    foreach (['rpsresponse', 'rpssession_element'] as $table) {
        if (!$db->query('DELETE FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_' . $table . ' WHERE fk_session = ' . $sessionId . ' AND entity = ' . (int) $conf->entity)) {
            $db->rollback();
            return -1;
        }
    }
    if (!$db->query('DELETE FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession WHERE rowid = ' . $sessionId . ' AND entity = ' . (int) $conf->entity)) {
        $db->rollback();
        return -1;
    }

    $db->commit();

    return 1;
}

/**
 * Public link of a session, the one the employees open
 *
 * @param  array  $session Session
 * @return string          Absolute URL
 */
function digiriskRpsSessionPublicUrl(array $session): string
{
    global $conf;

    return dol_buildpath('/digiriskdolibarr/public/rps/rps_questionnaire.php', 2) . '?' . http_build_query(['entity' => $conf->entity, 'key' => $session['public_key']]);
}

/**
 * Whether a work unit of a session still takes answers
 *
 * @param  array $session   Session
 * @param  int   $elementId Work unit
 * @return bool             True while the session is open and the work unit has not got all its answers
 */
function digiriskRpsSessionElementOpen(array $session, int $elementId): bool
{
    if ($session['status'] !== DIGIRISK_RPS_SESSION_OPEN || !isset($session['elements'][$elementId])) {
        return false;
    }

    return $session['max_answers'] <= 0 || $session['elements'][$elementId]['count'] < $session['max_answers'];
}

/**
 * Save the answer of an employee
 *
 * The number of answers of the work unit is read under a lock of its row in the session: two answers sent at
 * the same time cannot both take the last place.
 *
 * @param  DoliDB          $db        Database handler
 * @param  array           $session   Session
 * @param  int             $elementId Work unit the employee answers for
 * @param  int             $groupId   Job category of the employee, 0 when not given
 * @param  array<int, int> $answers   Level from 1 to 4 of every criterion, by position
 * @param  string          $comment   Free comment
 * @return int                        ID of the answer, -1 on database error, -2 when the work unit has all its answers,
 *                                    -3 when the work unit is not in the session or the session is closed
 */
function digiriskRpsResponseAdd(DoliDB $db, array $session, int $elementId, int $groupId, array $answers, string $comment): int
{
    global $conf;

    if ($session['status'] !== DIGIRISK_RPS_SESSION_OPEN) {
        return -3;
    }

    $db->begin();

    $sql  = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession_element';
    $sql .= ' WHERE fk_session = ' . (int) $session['rowid'] . ' AND fk_element = ' . $elementId . ' FOR UPDATE';
    $resql = $db->query($sql);
    if (!$resql) {
        $db->rollback();
        return -1;
    }
    if ($db->num_rows($resql) == 0) {
        $db->rollback();
        return -3;
    }

    if ($session['max_answers'] > 0) {
        $sql  = 'SELECT COUNT(*) AS nb FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsresponse';
        $sql .= ' WHERE fk_session = ' . (int) $session['rowid'] . ' AND fk_element = ' . $elementId;
        $resql = $db->query($sql);
        $obj   = $resql ? $db->fetch_object($resql) : false;
        if (!$obj) {
            $db->rollback();
            return -1;
        }
        if ((int) $obj->nb >= $session['max_answers']) {
            $db->rollback();
            return -2;
        }
    }

    $sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsresponse (entity, date_creation, fk_session, fk_element, fk_exposuregroup, answers, comment) VALUES (';
    $sql .= (int) $conf->entity . ", '" . $db->idate(dol_now()) . "', " . (int) $session['rowid'] . ', ' . $elementId . ', ' . $groupId . ', ';
    $sql .= "'" . $db->escape(json_encode($answers)) . "', " . (trim($comment) !== '' ? "'" . $db->escape(dol_trunc(trim($comment), 2000, 'right', 'UTF-8', 1)) . "'" : 'NULL') . ')';
    if (!$db->query($sql)) {
        $db->rollback();
        return -1;
    }
    $responseId = (int) $db->last_insert_id(MAIN_DB_PREFIX . 'digiriskdolibarr_rpsresponse');

    $db->commit();

    return $responseId;
}

/**
 * Averages of the answers of a session, work unit by work unit
 *
 * A criterion takes the level of the average of its answers, a factor the worst level of its criteria and the
 * work unit the average of its factors, as the assessment modal reads its grid: once recorded, the work unit
 * shows the same levels everywhere.
 *
 * @param  DoliDB $db      Database handler
 * @param  array  $session Session
 * @param  array  $grid    Criteria of digiriskRpsQuestionnaireGrid()
 * @return array<int, array> By work unit ID: count, first, last (dates of the answers), groups (answers by job category),
 *                           criteria (by position: mean, level, count, spread of the levels), factors (level by factor),
 *                           average (of the factors, null without answer), band (level of the average), comments
 */
function digiriskRpsSessionResults(DoliDB $db, array $session, array $grid): array
{
    $results = [];

    $sql  = 'SELECT fk_element, fk_exposuregroup, date_creation, answers, comment FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsresponse';
    $sql .= ' WHERE fk_session = ' . (int) $session['rowid'];
    $sql .= ' ORDER BY rowid ASC';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return $results;
    }

    while ($obj = $db->fetch_object($resql)) {
        $elementId = (int) $obj->fk_element;
        if (!isset($results[$elementId])) {
            $results[$elementId] = ['count' => 0, 'first' => null, 'last' => null, 'groups' => [], 'sums' => [], 'criteria' => [], 'factors' => [], 'average' => null, 'band' => 0, 'comments' => []];
        }

        $date = $db->jdate($obj->date_creation);

        $results[$elementId]['count']++;
        $results[$elementId]['first'] = $results[$elementId]['first'] ?? $date;
        $results[$elementId]['last']  = $date;
        $results[$elementId]['groups'][(int) $obj->fk_exposuregroup] = ($results[$elementId]['groups'][(int) $obj->fk_exposuregroup] ?? 0) + 1;
        if (trim((string) $obj->comment) !== '') {
            $results[$elementId]['comments'][] = ['date' => $date, 'text' => (string) $obj->comment];
        }

        $answers = json_decode((string) $obj->answers, true);
        foreach ((is_array($answers) ? $answers : []) as $position => $level) {
            $position = (int) $position;
            $level    = (int) $level;
            if (!isset($grid[$position]) || $level < 1 || $level > 4) {
                continue;
            }
            if (!isset($results[$elementId]['sums'][$position])) {
                $results[$elementId]['sums'][$position] = ['sum' => 0, 'count' => 0, 'spread' => [1 => 0, 2 => 0, 3 => 0, 4 => 0]];
            }
            $results[$elementId]['sums'][$position]['sum'] += $level;
            $results[$elementId]['sums'][$position]['count']++;
            $results[$elementId]['sums'][$position]['spread'][$level]++;
        }
    }
    $db->free($resql);

    foreach ($results as $elementId => $result) {
        foreach ($grid as $position => $criterion) {
            if (empty($result['sums'][$position]['count'])) {
                continue;
            }
            $mean  = $result['sums'][$position]['sum'] / $result['sums'][$position]['count'];
            $level = digiriskRpsQuestionnaireBand($mean);

            $results[$elementId]['criteria'][$position] = [
                'mean'   => $mean,
                'level'  => $level,
                'count'  => $result['sums'][$position]['count'],
                'spread' => $result['sums'][$position]['spread'],
            ];

            $factor = (int) $criterion['subCategory'];
            $results[$elementId]['factors'][$factor] = max($results[$elementId]['factors'][$factor] ?? 0, $level);
        }
        unset($results[$elementId]['sums']);

        if (!empty($results[$elementId]['factors'])) {
            ksort($results[$elementId]['factors']);
            $results[$elementId]['average'] = array_sum($results[$elementId]['factors']) / count($results[$elementId]['factors']);
            $results[$elementId]['band']    = digiriskRpsQuestionnaireBand($results[$elementId]['average']);
        }
    }

    return $results;
}

/**
 * Record the averages of a session as the psychosocial assessment of each of its work units that got answers
 *
 * Same record as an assessment typed in the modal (core/ajax/save_rps_assessment.php): the consolidated
 * psychosocial risk of the work unit, created when missing, gets an assessment with the "rps" method, the level
 * of each criterion goes to llx_digiriskdolibarr_rpsanswer and the work unit is declared concerned.
 *
 * @param  DoliDB    $db      Database handler
 * @param  User      $user    User closing the session
 * @param  array     $session Session
 * @param  array     $results Averages of digiriskRpsSessionResults()
 * @param  array     $grid    Criteria of digiriskRpsQuestionnaireGrid()
 * @param  Translate $langs   Language of the labels recorded
 * @return int                Number of work units assessed, < 0 on error
 */
function digiriskRpsSessionRecordAssessments(DoliDB $db, User $user, array $session, array $results, array $grid, Translate $langs): int
{
    global $conf;

    $scaleToCotation = [1 => 0, 2 => 48, 3 => 51, 4 => 100];

    $risk           = new Risk($db);
    $riskAssessment = new RiskAssessment($db);

    $numberingModuleName = [
        'riskanalysis/' . $risk->element           => $conf->global->DIGIRISKDOLIBARR_RISK_ADDON,
        'riskanalysis/' . $riskAssessment->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON,
    ];
    list($refRiskMod, $refEvaluationMod) = saturne_require_objects_mod($numberingModuleName, 'digiriskdolibarr');

    $groupLabel = '';
    if ($session['fk_exposuregroup'] > 0) {
        $groups     = digiriskdolibarr_exposuregroup_fetch_all($db);
        $groupLabel = isset($groups[$session['fk_exposuregroup']]) ? $groups[$session['fk_exposuregroup']]->label : '';
    }

    $assessed = 0;
    $now      = $db->idate(dol_now());

    $db->begin();

    foreach ($results as $elementId => $result) {
        if ($result['count'] <= 0 || $result['average'] === null || !isset($session['elements'][$elementId])) {
            continue;
        }

        // The consolidated psychosocial risk of the work unit, as the modal finds it
        $sql  = 'SELECT r.rowid FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk as r';
        $sql .= ' WHERE r.fk_element = ' . (int) $elementId;
        $sql .= ' AND r.category = ' . (int) Risk::PSYCHOSOCIAL_CATEGORY_POSITION;
        $sql .= ' AND r.status > 0 AND (r.sub_category IS NULL OR r.sub_category = -1)';
        $sql .= ' AND ' . Risk::getConsolidatedPsychosocialSqlCondition('r');
        $sql .= ' AND r.entity IN (' . getEntity('digiriskelement') . ')';
        $sql .= ' ORDER BY r.rowid ASC LIMIT 1';

        $resql  = $db->query($sql);
        $obj    = $resql ? $db->fetch_object($resql) : false;
        $riskId = $obj ? (int) $obj->rowid : 0;

        if ($riskId <= 0) {
            $risk               = new Risk($db);
            $risk->fk_element   = (int) $elementId;
            $risk->ref          = $refRiskMod->getNextValue($risk);
            $risk->category     = Risk::PSYCHOSOCIAL_CATEGORY_POSITION;
            $risk->sub_category = Risk::SUB_CATEGORY_NOT_SPECIFIED;
            $risk->description  = $langs->transnoentities('PsychosocialRisks');
            $risk->status       = 1;
            $risk->fk_projet    = getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT');

            if ($risk->create($user) <= 0) {
                $db->rollback();
                return -1;
            }
            $riskId = (int) $risk->id;
        }

        $comment = $langs->transnoentities('PsychosocialQuestionnaireAssessmentComment', $session['label'], (string) $result['count']);
        if ($groupLabel !== '') {
            $comment .= ' - ' . $groupLabel;
        }

        $evaluation                      = new RiskAssessment($db);
        $evaluation->fk_risk             = $riskId;
        $evaluation->ref                 = $refEvaluationMod->getNextValue($evaluation);
        $evaluation->cotation            = $scaleToCotation[$result['band']];
        $evaluation->status              = 1;
        $evaluation->method              = 'rps';
        $evaluation->comment             = $comment;
        $evaluation->date_riskassessment = dol_now();

        if ($evaluation->create($user) <= 0) {
            $db->rollback();
            return -1;
        }

        foreach ($result['criteria'] as $position => $criterionResult) {
            $answerComment = $langs->transnoentities('PsychosocialQuestionnaireCriterionComment', price(round($criterionResult['mean'], 1), 0, $langs, 0, 1, 1), (string) $criterionResult['count']);

            $sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsanswer';
            $sql .= ' (entity, date_creation, fk_riskassessment, criterion, criterion_label, sub_category, scale, cotation, comment, fk_user_creat)';
            $sql .= ' VALUES (' . (int) $conf->entity . ", '" . $now . "', " . (int) $evaluation->id . ', ' . (int) $position . ', ';
            $sql .= "'" . $db->escape(dol_trunc((string) $grid[$position]['name'], 255, 'right', 'UTF-8', 1)) . "', ";
            $sql .= (int) $grid[$position]['subCategory'] . ', ' . (int) $criterionResult['level'] . ', ' . $scaleToCotation[$criterionResult['level']] . ', ';
            $sql .= "'" . $db->escape($answerComment) . "', " . (int) $user->id . ')';
            if (!$db->query($sql)) {
                $db->rollback();
                return -1;
            }
        }

        // Assessing a work unit is declaring it concerned, as the modal does
        $sql   = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope WHERE fk_element = ' . (int) $elementId . ' AND entity = ' . (int) $conf->entity;
        $resql = $db->query($sql);
        $obj   = $resql ? $db->fetch_object($resql) : false;
        if ($obj) {
            $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope SET concerned = 1, fk_user_modif = ' . (int) $user->id . ' WHERE rowid = ' . (int) $obj->rowid;
        } else {
            $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope (entity, date_creation, fk_element, concerned, fk_user_modif) VALUES (' . (int) $conf->entity . ", '" . $now . "', " . (int) $elementId . ', 1, ' . (int) $user->id . ')';
        }
        if (!$db->query($sql)) {
            $db->rollback();
            return -1;
        }

        $sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpssession_element SET fk_riskassessment = ' . (int) $evaluation->id;
        $sql .= ' WHERE fk_session = ' . (int) $session['rowid'] . ' AND fk_element = ' . (int) $elementId;
        if (!$db->query($sql)) {
            $db->rollback();
            return -1;
        }

        $assessed++;
    }

    $db->commit();

    return $assessed;
}

/**
 * Sessions of a work unit that still take answers, for the notice of its psychosocial block
 *
 * @param  DoliDB $db        Database handler
 * @param  int    $elementId Work unit
 * @return array             Open sessions of the work unit, as digiriskRpsSessionFetchAll() returns them
 */
function digiriskRpsSessionsOfElement(DoliDB $db, int $elementId): array
{
    $sessions = [];
    foreach (digiriskRpsSessionFetchAll($db) as $sessionId => $session) {
        if ($session['status'] === DIGIRISK_RPS_SESSION_OPEN && isset($session['elements'][$elementId])) {
            $sessions[$sessionId] = $session;
        }
    }

    return $sessions;
}

/**
 * Rows of the risk assessment document: one per work unit of a session that got answers
 *
 * @param  DoliDB    $db          Database handler
 * @param  Translate $outputLangs Language of the document
 * @return array                  Rows: element (ref - label), group, session, count, answers (n, or n / max), period, average
 *                                (null when no level), band, status (label), recorded (whether the average was recorded)
 */
function digiriskRpsQuestionnaireDocumentRows(DoliDB $db, Translate $outputLangs): array
{
    $rows = [];

    $sessions = digiriskRpsSessionFetchAll($db);
    if (empty($sessions)) {
        return $rows;
    }

    require_once __DIR__ . '/../class/digiriskelement.class.php';

    $grid            = digiriskRpsQuestionnaireGrid();
    $groups          = digiriskdolibarr_exposuregroup_fetch_all($db);
    $digiriskElement = new DigiriskElement($db);
    $elements        = $digiriskElement->getActiveDigiriskElements();
    if (!is_array($elements)) {
        $elements = [];
    }

    // Oldest session first, as a story of the assessments
    foreach (array_reverse($sessions, true) as $session) {
        $results = digiriskRpsSessionResults($db, $session, $grid);
        foreach ($session['elements'] as $elementId => $sessionElement) {
            if (empty($results[$elementId]['count']) || !isset($elements[$elementId])) {
                continue;
            }
            $result = $results[$elementId];

            $group = $outputLangs->transnoentities('PsychosocialQuestionnaireAllGroups');
            if ($session['fk_exposuregroup'] > 0) {
                $group = isset($groups[$session['fk_exposuregroup']]) ? $groups[$session['fk_exposuregroup']]->label : '-';
            }

            $period = dol_print_date($result['first'], 'day', 'tzuser', $outputLangs);
            if (dol_print_date($result['last'], 'day', 'tzuser', $outputLangs) !== $period) {
                $period .= ' - ' . dol_print_date($result['last'], 'day', 'tzuser', $outputLangs);
            }

            $rows[] = [
                'element'  => $elements[$elementId]->ref . ' - ' . $elements[$elementId]->label,
                'group'    => $group,
                'session'  => $session['label'],
                'count'    => $result['count'],
                'answers'  => $session['max_answers'] > 0 ? $result['count'] . ' / ' . $session['max_answers'] : (string) $result['count'],
                'period'   => $period,
                'average'  => $result['average'],
                'band'     => $result['band'],
                'status'   => $outputLangs->transnoentities($session['status'] === DIGIRISK_RPS_SESSION_OPEN ? 'PsychosocialQuestionnaireOpen' : 'PsychosocialQuestionnaireClosed'),
                'recorded' => $sessionElement['fk_riskassessment'] > 0,
            ];
        }
    }

    return $rows;
}
