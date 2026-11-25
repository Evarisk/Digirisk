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
 * \file    lib/digiriskdolibarr_exposuregroup.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Exposure groups (#5644): people who hold the same jobs, such as the collection agents, with their headcount,
 *          spread over the GP/UT in percentages of their working time. The people exposed in a GP/UT are the sum, over
 *          the groups, of the headcount of the group times its share in the GP/UT.
 *
 * Tables: llx_digiriskdolibarr_exposuregroup (the groups) and llx_digiriskdolibarr_exposuregroup_element (the share of
 * a group in a GP/UT, one row per group and GP/UT, no row for a zero share). A share given to a GP counts for the GP
 * itself, it is not spread over its work units.
 */

/**
 * Return the exposure groups of the current entity, by label
 *
 * @param  DoliDB                $db Database handler
 * @return array<int, stdClass>      Groups (rowid, label, description, nb_people), by ID
 */
function digiriskdolibarr_exposuregroup_fetch_all(DoliDB $db): array
{
    global $conf;

    $groups = [];

    $sql  = 'SELECT rowid, label, description, nb_people FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup';
    $sql .= ' WHERE entity = ' . (int) $conf->entity;
    $sql .= ' ORDER BY label ASC, rowid ASC';

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return $groups;
    }
    while ($obj = $db->fetch_object($resql)) {
        $obj->rowid     = (int) $obj->rowid;
        $obj->nb_people = (int) $obj->nb_people;

        $groups[$obj->rowid] = $obj;
    }
    $db->free($resql);

    return $groups;
}

/**
 * Return the shares of the exposure groups in the GP/UT, of the current entity
 *
 * @param  DoliDB                         $db        Database handler
 * @param  int                            $elementId Only the shares in this GP/UT, all of them when 0
 * @return array<int, array<int, float>>             Share in percent, by group ID then GP/UT ID
 */
function digiriskdolibarr_exposuregroup_fetch_shares(DoliDB $db, int $elementId = 0): array
{
    global $conf;

    $shares = [];

    $sql  = 'SELECT fk_exposuregroup, fk_element, percentage FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup_element';
    $sql .= ' WHERE entity = ' . (int) $conf->entity;
    if ($elementId > 0) {
        $sql .= ' AND fk_element = ' . $elementId;
    }

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ': ' . $db->lasterror(), LOG_ERR);
        return $shares;
    }
    while ($obj = $db->fetch_object($resql)) {
        $shares[(int) $obj->fk_exposuregroup][(int) $obj->fk_element] = (float) $obj->percentage;
    }
    $db->free($resql);

    return $shares;
}

/**
 * Create or update an exposure group
 *
 * @param  DoliDB $db          Database handler
 * @param  User   $user        User saving the group
 * @param  int    $groupId     Group to update, 0 to create one
 * @param  string $label       Label, required
 * @param  string $description Description
 * @param  int    $nbPeople    Headcount of the group
 * @return int                 ID of the group, <= 0 on error
 */
function digiriskdolibarr_exposuregroup_save(DoliDB $db, User $user, int $groupId, string $label, string $description, int $nbPeople): int
{
    global $conf;

    if ($groupId > 0) {
        $sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup SET';
        $sql .= " label = '" . $db->escape($label) . "',";
        $sql .= " description = '" . $db->escape($description) . "',";
        $sql .= ' nb_people = ' . $nbPeople . ',';
        $sql .= ' fk_user_modif = ' . (int) $user->id;
        $sql .= ' WHERE rowid = ' . $groupId . ' AND entity = ' . (int) $conf->entity;

        return $db->query($sql) ? $groupId : -1;
    }

    $sql  = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup (entity, date_creation, label, description, nb_people, fk_user_creat)';
    $sql .= ' VALUES (' . (int) $conf->entity . ", '" . $db->idate(dol_now()) . "', '" . $db->escape($label) . "', '" . $db->escape($description) . "', " . $nbPeople . ', ' . (int) $user->id . ')';
    if (!$db->query($sql)) {
        return -1;
    }

    return (int) $db->last_insert_id(MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup');
}

/**
 * Delete an exposure group, with its shares in the GP/UT
 *
 * @param  DoliDB $db      Database handler
 * @param  int    $groupId Group to delete
 * @return int             1 if OK, < 0 on error
 */
function digiriskdolibarr_exposuregroup_delete(DoliDB $db, int $groupId): int
{
    global $conf;

    $db->begin();

    $sqlShares = 'DELETE FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup_element WHERE fk_exposuregroup = ' . $groupId . ' AND entity = ' . (int) $conf->entity;
    $sqlGroup  = 'DELETE FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup WHERE rowid = ' . $groupId . ' AND entity = ' . (int) $conf->entity;
    if (!$db->query($sqlShares) || !$db->query($sqlGroup)) {
        $db->rollback();
        return -1;
    }

    $db->commit();

    return 1;
}

/**
 * Save the shares of the exposure groups in the GP/UT, as entered in the grid of the groups: an empty or zero share
 * takes the group out of the GP/UT
 *
 * @param  DoliDB                         $db     Database handler
 * @param  User                           $user   User saving the shares
 * @param  array<int, array<int, float>>  $shares Share in percent, by group ID then GP/UT ID, from 0 to 100
 * @return int                                    Number of shares saved, < 0 on error
 */
function digiriskdolibarr_exposuregroup_save_shares(DoliDB $db, User $user, array $shares): int
{
    global $conf;

    $db->begin();

    $saved = 0;
    foreach ($shares as $groupId => $elementShares) {
        foreach ($elementShares as $elementId => $percentage) {
            $where = ' WHERE fk_exposuregroup = ' . (int) $groupId . ' AND fk_element = ' . (int) $elementId . ' AND entity = ' . (int) $conf->entity;
            if ($percentage <= 0) {
                $result = $db->query('DELETE FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup_element' . $where);
            } else {
                $resql  = $db->query('SELECT rowid FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup_element' . $where);
                $exists = $resql && $db->num_rows($resql) > 0;
                if ($exists) {
                    $result = $db->query('UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup_element SET percentage = ' . price2num($percentage) . ', fk_user_modif = ' . (int) $user->id . $where);
                } else {
                    $sql    = 'INSERT INTO ' . MAIN_DB_PREFIX . 'digiriskdolibarr_exposuregroup_element (entity, date_creation, fk_exposuregroup, fk_element, percentage, fk_user_modif)';
                    $sql   .= ' VALUES (' . (int) $conf->entity . ", '" . $db->idate(dol_now()) . "', " . (int) $groupId . ', ' . (int) $elementId . ', ' . price2num($percentage) . ', ' . (int) $user->id . ')';
                    $result = $db->query($sql);
                }
                $saved++;
            }
            if (!$result) {
                $db->rollback();
                return -1;
            }
        }
    }

    $db->commit();

    return $saved;
}

/**
 * Number of people a share of a group stands for
 *
 * @param  int   $nbPeople   Headcount of the group
 * @param  float $percentage Share of the group, in percent
 * @return float             Number of people, as a full-time equivalent
 */
function digiriskdolibarr_exposuregroup_people(int $nbPeople, float $percentage): float
{
    return $nbPeople * $percentage / 100;
}

/**
 * Format a share or a number of people with the decimals it needs, as the grid does while typing
 * (js/modules/exposureGroup.js): 100, 37,5, 33,33
 *
 * @param  float $number      Number
 * @param  int   $maxDecimals Number of decimals it is rounded to
 * @return string             Formatted number
 */
function digiriskdolibarr_exposuregroup_format_number(float $number, int $maxDecimals): string
{
    global $langs;

    // Rounded first, price() then shows the decimals left: none for a whole number
    return price(round($number, $maxDecimals), 0, $langs, 1, 0);
}
