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
 * \file    lib/digiriskdolibarr_risk.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for risk
 */

/**
 * Prepare risk pages header
 *
 * A risk carries neither note nor document nor agenda event: the card is its only tab, the other
 * modules of the module adding theirs through the hook saturne_object_prepare_head() calls.
 *
 * @param  Risk  $object Risk
 * @return array $head   Array of tabs
 * @throws Exception
 */
function risk_prepare_head(Risk $object): array
{
    // Load translation files required by the page
    saturne_load_langs();

    $head = [];

    $moreParams = [
        'specialName' => 'RiskCard'
    ];

    return saturne_object_prepare_head($object, $head, $moreParams, false, false, false, false);
}
