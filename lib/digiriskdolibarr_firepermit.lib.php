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
 * \file    lib/digiriskdolibarr_firepermit.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for Firepermit.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/lib/object.lib.php';

/**
 * Prepare array of tabs for firepermit.
 *
 * @param  FirePermit $object Firepermit object.
 * @return array              Array of tabs.
 * @throws Exception
 */
function firepermit_prepare_head(FirePermit $object): array
{
    // Global variables definitions.
    global $langs;

    $head = [];

	$head[1][0] = dol_buildpath("/saturne/view/saturne_schedules.php", 1) . '?id=' . $object->id . '&element_type=firepermit&module_name=DigiriskDolibarr';
    $head[1][1] = '<i class="fas fa-calendar-times"></i> ' . $langs->trans("Schedule");
    $head[1][2] = 'schedules';

    $moreParams['documentType'] = 'FirePermitDocument';

    return saturne_object_prepare_head($object, $head, $moreParams, true);
}
