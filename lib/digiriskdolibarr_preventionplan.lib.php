<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/digiriskdolibarr_preventionplan.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for prevention plan
 */

/**
 * Prepare prevention plan pages header
 *
 * @param  PreventionPlan $object Prevention plan
 * @return array          $head   Array of tabs
 * @throws Exception
 */
function preventionplan_prepare_head(PreventionPlan $object): array
{
    // Global variables definitions
    global $conf, $langs;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $head = [];

    $head[1][0] = dol_buildpath('/saturne/view/saturne_schedules.php', 1) . '?id=' . $object->id . '&element_type=preventionplan&module_name=DigiriskDolibarr';
    $head[1][1] = $conf->browser->layout != 'phone' ? '<i class="fas fa-calendar-times pictofixedwidth"></i>' . $langs->trans('Schedules') : '<i class="fas fa-calendar-times"></i>';
    $head[1][2] = 'schedules';

    $moreParams['documentType'] = 'PreventionPlanDocument';

    return saturne_object_prepare_head($object, $head, $moreParams, true);
}
