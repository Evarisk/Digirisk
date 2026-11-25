<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiriskdolibarr_risklibrary.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Risk library of the entity (#5644): models of risks, with their danger category, their description and an
 *          indicative cotation, assigned to the GP/UT they concern. A GP/UT concerned gets a risk of its own, created
 *          from the model and assessed on its own: the exposure is not the same everywhere, and the risk lists, the
 *          DU, the public overview and the action plan keep reading the GP/UT of each risk.
 *
 * Tables: llx_digiriskdolibarr_risklibrary (the models) and llx_digiriskdolibarr_risklibrary_element (the assignment
 * of a model to a GP/UT: concerned 1, not concerned 0 with a reason, to qualify -1 or no row; fk_risk is
 * the risk created in the GP/UT).
 */

/**
 * Cotations a model of risk can suggest, those of the standard method: the lowest value of each level of the scale
 *
 * @return array<int, string> Translation key of the level, by cotation
 */
function digiriskdolibarr_risklibrary_cotations(): array
{
    return [0 => 'GreyRisk', 48 => 'OrangeRisk', 51 => 'RedRisk', 100 => 'BlackRisk'];
}

/**
 * Return the models of the risk library of the current entity, by label
 *
 * @param  DoliDB               $db Database handler
 * @return array<int, stdClass>     Models (rowid, label, category, description, cotation), by ID
 */
function digiriskdolibarr_risklibrary_fetch_all(DoliDB $db): array
{
    global $conf;

    $models = [];

    $sql  = 'SELECT rowid, label, category, description, cotation FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risklibrary';
    $sql .= ' WHERE entity = ' . (int) $conf->entity;
    $sql .= ' ORDER BY label ASC, rowid ASC';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return $models;
    }
    while ($obj = $db->fetch_object($resql)) {
        $obj->rowid    = (int) $obj->rowid;
        $obj->category = (int) $obj->category;
        $obj->cotation = (int) $obj->cotation;

        $models[$obj->rowid] = $obj;
    }
    $db->free($resql);

    return $models;
}

/**
 * Create or update a model of the risk library
 *
 * @param  DoliDB $db          Database handler
 * @param  User   $user        User saving the model
 * @param  int    $modelId     Model to update, 0 to create one
 * @param  string $label       Label, required
 * @param  int    $category    Danger category, its position in dangerCategories.json
 * @param  string $description Description
 * @param  int    $cotation    Indicative cotation, from digiriskdolibarr_risklibrary_cotations()
 * @return int                 ID of the model, <= 0 on error
 */
function digiriskdolibarr_risklibrary_save(DoliDB $db, User $user, int $modelId, string $label, int $category, string $description, int $cotation): int
{
    global $conf;

    if ($modelId > 0) {
        $sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risklibrary SET';
        $sql .= " label = '" . $db->escape($label) . "',";
        $sql .= ' category = ' . $category . ',';
        $sql .= " description = '" . $db->escape($description) . "',";
        $sql .= ' cotation = ' . $cotation . ',';
        $sql .= ' fk_user_modif = ' . (int) $user->id;
        $sql .= ' WHERE rowid = ' . $modelId . ' AND entity = ' . (int) $conf->entity;

        return $db->query($sql) ? $modelId : -1;
    }

    $sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risklibrary (entity, date_creation, label, category, description, cotation, fk_user_creat)';
    $sql .= ' VALUES (' . (int) $conf->entity . ", '" . $db->idate(dol_now()) . "', '" . $db->escape($label) . "', " . $category . ", '" . $db->escape($description) . "', " . $cotation . ', ' . (int) $user->id . ')';
    if (!$db->query($sql)) {
        return -1;
    }

    return (int) $db->last_insert_id(MAIN_DB_PREFIX . 'digiriskdolibarr_risklibrary');
}

/**
 * Delete a model of the risk library with its assignments: the risks created in the GP/UT stay, they are risks of
 * their own
 *
 * @param  DoliDB $db      Database handler
 * @param  int    $modelId Model to delete
 * @return int             1 if OK, < 0 on error
 */
function digiriskdolibarr_risklibrary_delete(DoliDB $db, int $modelId): int
{
    global $conf;

    $db->begin();

    $sqlScopes = 'DELETE FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risklibrary_element WHERE fk_risklibrary = ' . $modelId . ' AND entity = ' . (int) $conf->entity;
    $sqlModel  = 'DELETE FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risklibrary WHERE rowid = ' . $modelId . ' AND entity = ' . (int) $conf->entity;
    if (!$db->query($sqlScopes) || !$db->query($sqlModel)) {
        $db->rollback();
        return -1;
    }

    $db->commit();

    return 1;
}

/**
 * Return the assignments of the models to the GP/UT, with the risk created in each GP/UT concerned and the cotation of
 * its current assessment
 *
 * @param  DoliDB                           $db Database handler
 * @return array<int, array<int, stdClass>>     Assignment (concerned, reason, fk_risk, risk_ref, risk_status, cotation, method),
 *                                              by model ID then GP/UT ID; risk_ref is empty when the risk is gone
 */
function digiriskdolibarr_risklibrary_fetch_scopes(DoliDB $db): array
{
    global $conf;

    $scopes = [];

    // The current assessment of a risk is its active one (status 1), the older ones are set back to 0
    $sql  = 'SELECT s.fk_risklibrary, s.fk_element, s.concerned, s.reason, s.fk_risk, r.ref AS risk_ref, r.status AS risk_status, ra.cotation, ra.method';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risklibrary_element s';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk r ON r.rowid = s.fk_risk';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment ra ON ra.fk_risk = r.rowid AND ra.status = 1';
    $sql .= ' WHERE s.entity = ' . (int) $conf->entity;

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return $scopes;
    }
    while ($obj = $db->fetch_object($resql)) {
        $obj->concerned   = (int) $obj->concerned;
        $obj->fk_risk     = (int) $obj->fk_risk;
        $obj->risk_ref    = (string) $obj->risk_ref;
        $obj->risk_status = (int) $obj->risk_status;
        $obj->cotation    = $obj->cotation !== null ? (int) $obj->cotation : null;
        $obj->method      = (string) $obj->method;

        $scopes[(int) $obj->fk_risklibrary][(int) $obj->fk_element] = $obj;
    }
    $db->free($resql);

    return $scopes;
}

/**
 * Qualify a GP/UT for a model of the library
 *
 * Concerned, the GP/UT gets its risk: created from the model with a first assessment at its indicative cotation, or
 * restored from the archives when it had one before. Not concerned, its risk is archived, with its assessments and
 * its tasks: nothing is lost if it becomes concerned again. Back to qualify, the risk is archived the same way.
 *
 * @param  DoliDB            $db               Database handler
 * @param  User              $user             User qualifying the GP/UT
 * @param  stdClass          $model            Model, from digiriskdolibarr_risklibrary_fetch_all()
 * @param  int               $elementId        GP/UT
 * @param  int               $concerned        1 concerned, 0 not concerned, -1 to qualify
 * @param  string            $reason           Reason why the GP/UT is not concerned
 * @param  object            $refRiskMod       Numbering module of the risks
 * @param  object            $refEvaluationMod Numbering module of the risk assessments
 * @return int                                 1 if OK, < 0 on error with the error in $db or in the messages
 */
function digiriskdolibarr_risklibrary_set_scope(DoliDB $db, User $user, stdClass $model, int $elementId, int $concerned, string $reason, object $refRiskMod, object $refEvaluationMod): int
{
    global $conf, $langs;

    require_once __DIR__ . '/../class/riskanalysis/risk.class.php';
    require_once __DIR__ . '/../class/riskanalysis/riskassessment.class.php';

    $where = ' WHERE fk_risklibrary = ' . (int) $model->rowid . ' AND fk_element = ' . $elementId . ' AND entity = ' . (int) $conf->entity;

    $resql = $db->query('SELECT rowid, fk_risk FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risklibrary_element' . $where);
    $scope = $resql ? $db->fetch_object($resql) : null;

    // The risk of the GP/UT, if it still exists
    $risk = new Risk($db);
    if ($scope && (int) $scope->fk_risk > 0 && $risk->fetch((int) $scope->fk_risk) <= 0) {
        $risk = new Risk($db);
    }

    $db->begin();

    $riskId = $risk->id > 0 ? (int) $risk->id : 0;
    if ($concerned == 1) {
        if ($riskId > 0 && $risk->status == Risk::STATUS_ARCHIVED) {
            if ($risk->setUnarchived($user) <= 0) {
                setEventMessages($risk->error, $risk->errors, 'errors');
                $db->rollback();
                return -1;
            }
        } elseif ($riskId <= 0) {
            $risk              = new Risk($db);
            $risk->ref         = $refRiskMod->getNextValue($risk);
            $risk->fk_element  = $elementId;
            $risk->fk_projet   = getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT');
            $risk->category    = $model->category;
            $risk->description = (string) $model->description;
            $risk->status      = Risk::STATUS_VALIDATED;

            $riskId = $risk->create($user);
            if ($riskId <= 0) {
                setEventMessages($risk->error, $risk->errors, 'errors');
                $db->rollback();
                return -1;
            }

            // A first assessment at the indicative cotation of the model, to be reviewed in the GP/UT
            $assessment                      = new RiskAssessment($db);
            $assessment->ref                 = $refEvaluationMod->getNextValue($assessment);
            $assessment->fk_risk             = $riskId;
            $assessment->cotation            = $model->cotation;
            $assessment->method              = 'standard';
            $assessment->status              = 1;
            $assessment->comment             = $langs->transnoentities('RiskLibraryAssessmentComment', $model->label);
            $assessment->date_riskassessment = dol_now();
            if ($assessment->create($user, 0) <= 0) {
                setEventMessages($assessment->error, $assessment->errors, 'errors');
                $db->rollback();
                return -1;
            }
        }
    } elseif ($riskId > 0 && $risk->status != Risk::STATUS_ARCHIVED && $risk->setArchived($user) <= 0) {
        setEventMessages($risk->error, $risk->errors, 'errors');
        $db->rollback();
        return -1;
    }

    // The row stays when the GP/UT goes back to be qualified: it keeps the risk, archived, to be restored later
    if ($scope) {
        $sql    = 'UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risklibrary_element SET concerned = ' . $concerned . ',';
        $sql   .= " reason = '" . $db->escape($concerned == 0 ? $reason : '') . "',";
        $sql   .= ' fk_risk = ' . ($riskId > 0 ? $riskId : 'NULL') . ', fk_user_modif = ' . (int) $user->id . $where;
        $result = $db->query($sql);
    } else {
        $sql    = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_risklibrary_element (entity, date_creation, fk_risklibrary, fk_element, concerned, reason, fk_risk, fk_user_modif)';
        $sql   .= ' VALUES (' . (int) $conf->entity . ", '" . $db->idate(dol_now()) . "', " . (int) $model->rowid . ', ' . $elementId . ', ' . $concerned . ", '" . $db->escape($concerned == 0 ? $reason : '') . "', " . ($riskId > 0 ? $riskId : 'NULL') . ', ' . (int) $user->id . ')';
        $result = $db->query($sql);
    }
    if (!$result) {
        $db->rollback();
        return -1;
    }

    $db->commit();

    return 1;
}
