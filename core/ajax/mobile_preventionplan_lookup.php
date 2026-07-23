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
 * \file    core/ajax/mobile_preventionplan_lookup.php
 * \ingroup digiriskdolibarr
 * \brief   AJAX endpoint returning the exterior company, its responsible and the period of a
 *          prevention plan, so the mobile fire permit interface can pre-fill itself from its parent plan.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/preventionplan.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';

global $db, $moduleNameLowerCase, $user;

header('Content-Type: application/json');

if (empty($user->id)) {
    echo json_encode(['success' => false, 'error' => 'NotLoggedIn']);
    exit;
}

if (!$user->hasRight('digiriskdolibarr', 'mobilefirepermit', 'write')) {
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$preventionPlanId = GETPOSTINT('id');

$preventionPlan = new PreventionPlan($db);
if ($preventionPlanId <= 0 || $preventionPlan->fetch($preventionPlanId) <= 0) {
    echo json_encode(['success' => false, 'error' => 'NotFound']);
    exit;
}

$response = [
    'success'     => true,
    'societe'     => null,
    'responsible' => null,
    'date_start'  => $preventionPlan->date_start ? dol_print_date($preventionPlan->date_start, '%Y-%m-%dT%H:%M') : '',
    'date_end'    => $preventionPlan->date_end   ? dol_print_date($preventionPlan->date_end, '%Y-%m-%dT%H:%M')   : '',
];

// fetchResourcesFromObject() returns the resolved object itself (an already fetched Societe)
// for a single match, and 0 when there is none.
$digiriskResources = new DigiriskResources($db);
$extSociety        = $digiriskResources->fetchResourcesFromObject('ExtSociety', $preventionPlan);
if (is_object($extSociety) && $extSociety->id > 0) {
    $response['societe'] = [
        'id'    => (int) $extSociety->id,
        'name'  => $extSociety->name,
        'email' => $extSociety->email,
        'siren' => $extSociety->idprof1,
    ];
}

$signatory       = new SaturneSignature($db, $moduleNameLowerCase, $preventionPlan->element);
$extResponsibles = $signatory->fetchSignatory('ExtSocietyResponsible', $preventionPlan->id, 'preventionplan');
if (is_array($extResponsibles) && !empty($extResponsibles)) {
    $extResponsible             = array_shift($extResponsibles);
    $response['responsible'] = [
        'id'        => (int) $extResponsible->element_id,
        'lastname'  => $extResponsible->lastname,
        'firstname' => $extResponsible->firstname,
        'email'     => $extResponsible->email,
        'phone'     => $extResponsible->phone,
    ];
}

echo json_encode($response);
exit;
