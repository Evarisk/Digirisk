<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/frontend/preventionplan_mobile_success.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Success screen of the mobile prevention plan interface.
 *          Expects: $langs, $object (fetched prevention plan).
 */

global $langs;

$successTitle = $langs->trans('MobilePPSuccessTitle');
$successRef   = $object->ref;
$successLabel = $object->label;
$successFacts = [
    $langs->trans('MobileSuccessInteriorSigned'),
    $langs->trans('MobileSuccessExteriorNotified'),
];

// Public spread page, shareable straight away with the people who have to join and sign this plan.
// Only relevant when DoliLetter (which serves the spread public page) is enabled.
$successShareUrl = isModEnabled('doliletter')
    ? dol_buildpath('/custom/doliletter/public/spread/add_spread.php', 3) . '?id=' . $object->id . '&object_type=digiriskdolibarr_preventionplan'
    : '';

$successViewUrl   = dol_buildpath('/custom/digiriskdolibarr/view/preventionplan/preventionplan_card.php', 1) . '?id=' . $object->id;
$successViewLabel  = $langs->trans('MobilePPViewPlan');
$successAgainUrl   = $_SERVER['PHP_SELF'];
$successAgainLabel = $langs->trans('MobilePPCreateAnother');

require __DIR__ . '/digiriskdolibarr_mobile_success.tpl.php';
